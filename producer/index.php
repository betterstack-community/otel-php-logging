<?php

require __DIR__ . '/vendor/autoload.php';

\Demo\Project\Instrumentation\Tracing::register();

$client = new Aws\Sqs\SqsClient(['http' => ['connect_timeout' => 1]]);
$faker = Faker\Factory::create();
$app = new Demo\Project\Application($client, $faker);

$app->run();
