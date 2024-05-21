<?php

require __DIR__ . '/vendor/autoload.php';

\Demo\Project\Instrumentation\Tracing::register();

$sqsClient = new Aws\Sqs\SqsClient(['http' => ['connect_timeout' => 1]]);
$httpClient = new \GuzzleHttp\Client(['connect_timeout' => 1]);
$httpFactory = new \GuzzleHttp\Psr7\HttpFactory();

$app = new Demo\Project\Application(
    $sqsClient,
    $httpClient,
    $httpFactory,
);
$app->registerSmtpServer('mailgun');
$app->registerSmtpServer('postmark');

$app->run();
