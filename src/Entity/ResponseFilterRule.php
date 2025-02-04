<?php

namespace ResponseFilterBundle\Entity;

use ResponseFilterBundle\Enum\ResponseFilterRuleTypeEnum;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
class ResponseFilterRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: false)]
    public string $service;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    public ?string $subService;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: false)]
    public string $action;

    #[ORM\Column(type: Types::SMALLINT, nullable: false, enumType: ResponseFilterRuleTypeEnum::class)]
    public ResponseFilterRuleTypeEnum $type;

    #[ORM\Column(nullable: true)]
    public ?string $field = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 1500)]
    #[ORM\Column(name: 'condition_val', length: 1500, nullable: false)]
    public string $condition = '';

    #[Assert\Length(max: 250)]
    #[ORM\Column(length: 250, nullable: true)]
    public ?string $value = null;


    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if (!$this->condition) {
            return;
        }
        if (preg_match('/(.+)([<>=]+)(.+)/', $this->condition, $matches)) {
            $path = $matches[1];
            $operator = $matches[2] ?? null;
            $value = $matches[3] ?? null;
        } else {
            $context->buildViolation('Condition should be in format: field_name/field_name/*/operator(=<>)value(string/array). Example: profile/age>10 or profile/name=["John", "Doe"]')
                ->atPath('condition')
                ->addViolation();

            return;
        }
        if (!$path || !$operator || !$value) {
            $context->buildViolation('Condition should be in format: field_name/field_name/*/operator(=<>)value(string/array). Example: profile/age>10 or profile/name=["John", "Doe"]')
                ->atPath('condition')
                ->addViolation();

            return;
        }
        if (!in_array($operator, ['<', '>', '=', '>=', '<='])) {
            $context->buildViolation('Operator should be one of: <, >, =, >=, <=')
                ->atPath('condition')
                ->addViolation();
        }
        if (!@json_decode($value, true)) {
            $context->buildViolation('Value should be a valid JSON string')
                ->atPath('condition')
                ->addViolation();
        }
        $pathParts = explode('/', $path);
        $multipleApplyArrayCount = 0;
        foreach ($pathParts as $pathPart) {
            if ($pathPart === '*') {
                $multipleApplyArrayCount++;
            }
        }
        if ($multipleApplyArrayCount > 1) {
            $context->buildViolation('Only one * is allowed in the path. Please use "!*" for loop through all elements of array')
                ->atPath('condition')
                ->addViolation();
        }
    }
}
