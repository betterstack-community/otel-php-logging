<?php

namespace Demo\Project\Instrumentation;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use Throwable;

trait PostHookTrait
{
    private static function endSpan(?Throwable $exception = null): void
    {
        $scope = Context::storage()->scope();

        if (!$scope) {
            return;
        }
        $scope->detach();
        $span = Span::fromContext($scope->context());

        if ($exception) {
            $span->recordException($exception, [TraceAttributes::EXCEPTION_ESCAPED => true]);
            $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        }
        $span->end();
    }
}
