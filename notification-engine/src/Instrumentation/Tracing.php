<?php

namespace Demo\Project\Instrumentation;

use Demo\Project\Application;
use Demo\Project\Mailer\DemoSmtpMailer;
use Nette\Mail\SmtpMailer;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\CachedInstrumentation;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SemConv\TraceAttributes;
use OpenTelemetry\SemConv\TraceAttributeValues;
use Psr\Http\Client\ClientInterface;
use Throwable;
use function OpenTelemetry\Instrumentation\hook;

class Tracing
{
    use PostHookTrait;

    public static function register(): void
    {
        $instrumentation = new CachedInstrumentation(getenv('OTEL_SERVICE_NAME'));

        hook(
            Application::class,
            'processMessage',
            pre: static function (Application $app, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                $message = $params[0];
                $startTime = $params[1];
                $traceparent = $message['MessageAttributes']['traceparent']['StringValue'];
                $parent = Globals::propagator()->extract(['traceparent' => $traceparent]);
                Context::storage()->attach($parent);
                $instrumentation->tracer()
                    ->spanBuilder('SQS notification-events receive')
                    ->setSpanKind(SpanKind::KIND_CONSUMER)
                    ->setAttribute(TraceAttributes::MESSAGING_OPERATION, TraceAttributeValues::MESSAGING_OPERATION_RECEIVE)
                    ->setAttribute(TraceAttributes::MESSAGING_SYSTEM, TraceAttributeValues::MESSAGING_SYSTEM_AWS_SQS)
                    ->setAttribute(TraceAttributes::MESSAGING_DESTINATION_NAME, 'notification-events')
                    ->setStartTimestamp($startTime)
                    ->startSpan()
                    ->setStatus(StatusCode::STATUS_OK)
                    ->end();

                return $params;
            },
        );

        hook(
           ClientInterface::class,
           'sendRequest',
            pre: static function (ClientInterface $httpClient, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
               $request = $params[0];
               $parent = Context::getCurrent();
               $span = $instrumentation->tracer()
                   ->spanBuilder('HTTP GET /api/v1/templates/{key}')
                   ->setSpanKind(SpanKind::KIND_CLIENT)
                   ->startSpan();
               $context = $span->storeInContext($parent);
               Context::storage()->attach($context);
               Globals::propagator()->inject($request, HeadersPropagator::instance(), $context);
               $params[0] = $request;
               return $params;
            },
            post: static function (ClientInterface $httpClient, array $params, $returnValue, ?Throwable $exception) {
                static::endSpan($exception);
                return $returnValue;
            }
        );

        hook(
            SmtpMailer::class,
            'send',
            pre: static function (DemoSmtpMailer $mailer, array $params, string $class, string $function, ?string $filename, ?int $lineno) use ($instrumentation) {
                $parent = Context::getCurrent();
                $span = $instrumentation->tracer()
                    ->spanBuilder(sprintf('SMTP %s send', $mailer->name))
                    ->setSpanKind(SpanKind::KIND_CLIENT)
                    ->startSpan();
                Context::storage()->attach($span->storeInContext($parent));
                return $params;
            },
            post: static function (DemoSmtpMailer $mailer, array $params, $returnValue, ?Throwable $exception) {
                static::endSpan($exception);
                return $returnValue;
            }
        );
    }
}