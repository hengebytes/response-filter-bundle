<?php

namespace ResponseFilterBundle\Handler;

use ResponseFilterBundle\Entity\ResponseFilterRule;
use ResponseFilterBundle\Enum\ResponseFilterRuleTypeEnum;
use WebserviceCoreAsyncBundle\Response\ParsedResponse;
use Doctrine\ORM\EntityManagerInterface;

class ResponseFilterHandler
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws \Exception
     */
    private function filterArray(
        array &$array, array $pathParts, string $operator, array|string|int $value, ResponseFilterRule $responseFilterRule
    ): void {
        $targetKey = array_shift($pathParts);
        if (!$pathParts) {
            if (isset($array[$targetKey])) {
                $match = $this->isMatchCriteria($operator, $array[$targetKey], $value);
                if (!$match) {
                    return;
                }
                if ($responseFilterRule->type !== ResponseFilterRuleTypeEnum::STR_REPLACE) {
                    throw new \Exception('match found');
                }
                $this->replaceFieldValue($responseFilterRule, $array);
            }

            return;
        }

        $isApplyToThisLevel = $targetKey === '*';

        if ($targetKey === '*' || $targetKey === '!*') {
            foreach ($array as $k => &$innerValue) {
                try {
                    $this->filterArray($innerValue, $pathParts, $operator, $value, $responseFilterRule);
                } catch (\Exception $e) {
                    if (!$isApplyToThisLevel) {
                        throw $e;
                    }
                    if ($responseFilterRule->type === ResponseFilterRuleTypeEnum::REMOVE) {
                        unset($array[$k]);
                    } elseif ($responseFilterRule->type === ResponseFilterRuleTypeEnum::SET) {
                        $keyValuePath = explode('/', $responseFilterRule->field);
                        $innerValuePath = &$array[$k];
                        foreach ($keyValuePath as $keyValuePathPart) {
                            $innerValuePath = &$innerValuePath[$keyValuePathPart];
                        }
                        $innerValuePath = $responseFilterRule->value;
                    }
                }
            }
            unset($innerValue);
            if ($isApplyToThisLevel && $responseFilterRule->type === ResponseFilterRuleTypeEnum::REMOVE) {
                $array = array_values($array);
            }
        } elseif (isset($array[$targetKey])) {
            $this->filterArray($array[$targetKey], $pathParts, $operator, $value, $responseFilterRule);
        }
    }

    public function applyFiltersToResponse(ParsedResponse $parsedResponse): void
    {
        $WSRequest = $parsedResponse->mainAsyncResponse->WSRequest;
        $entityRepository = $this->entityManager->getRepository(ResponseFilterRule::class);
        $searchParams = [
            'service' => $WSRequest->webService,
            'action' => $WSRequest->getCustomAction(),
        ];
        if ($WSRequest->subService) {
            $searchParams['subService'] = $WSRequest->subService;
        }
        $responseFilterRules = $entityRepository->findBy($searchParams);
        if (!$responseFilterRules) {
            return;
        }
        $response = $parsedResponse->response;
        foreach ($responseFilterRules as $responseFilterRule) {
            if (preg_match('/(.+)([<>=]+)(.+)/', $responseFilterRule->condition, $matches)) {
                $path = $matches[1] ?? null;
                $operator = $matches[2] ?? null;
                $value = $matches[3] ?? null;
            } else {
                return;
            }
            if ($path === null || $operator === null || $value === null) {
                return;
            }
            $fieldValueJsonDecode = json_decode($value);
            if ($fieldValueJsonDecode) {
                $value = $fieldValueJsonDecode;
            }
            $pathParts = explode('/', $path);
            $this->filterArray($response, $pathParts, $operator, $value, $responseFilterRule);
        }
        $parsedResponse->response = $response;
    }

    private function isMatchCriteria(string $operator, $array, array|int|string $value): bool
    {
        $match = false;
        switch ($operator) {
            case '=':
                if (
                    $array == $value
                    || (is_array($value) && in_array($array, $value))
                ) {
                    $match = true;
                }
                break;
            case '>':
                if ($array > $value) {
                    $match = true;
                }
                break;
            case '<':
                if ($array < $value) {
                    $match = true;
                }
                break;
        }

        return $match;
    }

    private function replaceFieldValue(ResponseFilterRule $responseFilterRule, array &$array): void
    {
        $replaceValue = $responseFilterRule->value;
        $replaceValueDecode = @json_decode($replaceValue, true);
        if ($replaceValueDecode) {
            $replaceValue = $replaceValueDecode;
        }
        $from = null;
        $to = null;
        if (is_array($replaceValue)) {
            $from = array_keys($replaceValue);
            $to = array_values($replaceValue);
        } elseif (strpos($replaceValue, '=>')) {
            $replaceValue = explode('=>', $replaceValue);
            $from = $replaceValue[0] ?? '';
            $to = $replaceValue[1] ?? '';
        } elseif (strpos($replaceValue, '=')) {
            $replaceValue = explode('=', $replaceValue);
            $from = $replaceValue[0] ?? '';
            $to = $replaceValue[1] ?? '';
        }
        $array[$responseFilterRule->field] = str_replace(
            $from,
            $to,
            $array[$responseFilterRule->field] ?? ''
        );
    }
}
