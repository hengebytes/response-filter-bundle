<?php

namespace Hengebytes\ResponseFilterBundle\Middleware;

use Hengebytes\ResponseFilterBundle\Handler\ResponseFilterHandler;
use Hengebytes\WebserviceCoreAsyncBundle\Callback\OnResponseReceivedCallback;
use Hengebytes\WebserviceCoreAsyncBundle\Middleware\ResponseModificationInterface;
use Hengebytes\WebserviceCoreAsyncBundle\Response\AsyncResponse;
use Hengebytes\WebserviceCoreAsyncBundle\Response\ParsedResponse;

class FilterResponseResponseModifier implements ResponseModificationInterface
{
    public function __construct(private ResponseFilterHandler $responseFilterHandler)
    {
    }

    public function modify(AsyncResponse $response): void
    {
        $response->addOnResponseReceivedCallback(new OnResponseReceivedCallback(
            function (ParsedResponse $parsedResponse) {
                if ($parsedResponse->exception || !$parsedResponse->response) {
                    return;
                }
                $this->responseFilterHandler->applyFiltersToResponse($parsedResponse);
            }
        ));
    }

    public function supports(AsyncResponse $response): bool
    {
        return !$response->isCached;
    }

    public static function getPriority(): int
    {
        return 180; // before store to cache
    }
}