<?php

require __DIR__ . '/../vendor/autoload.php';

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$eventToTemplateMap = [
    'maintenance-scheduled' => __DIR__ . '/../templates/maintenance.txt',
    'suspicious-activity-detected' => __DIR__ . '/../templates/suspicious.txt',
    'terms-of-service-updated' => __DIR__ . '/../templates/tos.txt',
];

$app = AppFactory::create();

$app->addErrorMiddleware(false, false, false);

$tracer = Globals::tracerProvider()->getTracer(getenv('OTEL_SERVICE_NAME'));

// Add routes
$app->get('/api/v1/templates/{key}', function (Request $request, Response $response, array $args) use ($eventToTemplateMap, $tracer) {
    $parent = Globals::propagator()->extract($request->getHeaders());
    $span = $tracer->spanBuilder('HTTP GET /api/v1/templates/{key}')->setSpanKind(SpanKind::KIND_SERVER)->setParent($parent)->startSpan();
    $key = $args['key'];
    if (isset($eventToTemplateMap[$key])) {
        $body = file_get_contents($eventToTemplateMap[$key]);
        $response->getBody()->write($body);
    } else {
        $response->withStatus(404);
        $span->setStatus(StatusCode::STATUS_ERROR, sprintf('Key "%s" not found', $key));
    }
    $span->end();

    return $response;
});

$app->run();
