<?php

namespace Demo\Project;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Demo\Project\Event\Event;
use Demo\Project\Event\MaintenanceScheduled;
use Demo\Project\Event\SuspiciousActivityDetected;
use Demo\Project\Event\TermsOfServiceUpdated;
use Faker\Generator;

class Application
{
    public function __construct(
        private readonly SqsClient $client,
        private readonly Generator $faker,
    ) {}

    public function run()
    {
        while (true) {
            try {
                $args = [
                    'QueueUrl' => getenv('AWS_QUEUE_URL'),
                    'MessageBody' => $this->buildMessageBody(),
                ];
                $this->client->sendMessage($args);
            } catch (AwsException $e) {
                error_log($e->getMessage());
            }
            sleep(getenv('SQS_SEND_INTERVAL'));
        }
    }

    private function buildMessageBody(): string
    {
        $eventClass = $this->faker->randomElement([MaintenanceScheduled::class, SuspiciousActivityDetected::class, TermsOfServiceUpdated::class]);
        /** @var $event Event */
        $event = new $eventClass($this->faker);

        return $event->toJson();
    }
}
