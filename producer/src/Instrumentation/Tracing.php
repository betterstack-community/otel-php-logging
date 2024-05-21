<?php

namespace Demo\Project\Instrumentation;

use Aws\AwsClient;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use OpenTelemetry\SemConv\TraceAttributeValues;
use Throwable;
use function OpenTelemetry\Instrumentation\hook;

class Tracing
{
    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation(getenv('OTEL_SERVICE_NAME'));

        hook(
            AwsClient::class,
            '__call',
            pre: static function (AwsClient $client, array $args, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                if ($args[0] != 'sendMessage') {
                    return $args;
                }
                $parent = Context::getCurrent();
                $span = $instrumentation->tracer()
                    ->spanBuilder('SQS notification-events publish')
                    ->setSpanKind(SpanKind::KIND_PRODUCER)
                    ->setAttribute(TraceAttributes::MESSAGING_OPERATION, TraceAttributeValues::MESSAGING_OPERATION_PUBLISH)
                    ->setAttribute(TraceAttributes::MESSAGING_SYSTEM, TraceAttributeValues::MESSAGING_SYSTEM_AWS_SQS)
                    ->setAttribute(TraceAttributes::MESSAGING_DESTINATION_NAME, 'notification-events')
                    ->startSpan();
                Context::storage()->attach($span->storeInContext($parent));

                $carrier = [];
                TraceContextPropagator::getInstance()->inject($carrier);
                $args[1][0]['MessageAttributes'] = [
                    'traceparent' => [
                        'DataType' => 'String',
                        'StringValue' => $carrier['traceparent'],
                    ],
                ];

                return $args;
            },
            post: static function (AwsClient $client, array $args, $returnValue, ?Throwable $exception) {
                if ($args[0] != 'sendMessage') {
                    return $args;
                }
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
        );
    }
}
