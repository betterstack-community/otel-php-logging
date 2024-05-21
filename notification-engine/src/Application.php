<?php

namespace Demo\Project;

use Aws\Sqs\SqsClient;
use Demo\Project\Helper\Str;
use Demo\Project\Mailer\DemoSmtpMailer;
use Nette\Mail\Message;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Throwable;

class Application
{
    /** @var DemoSmtpMailer[] */
    private array $smtpServers;

    public function __construct(
        private readonly SqsClient $sqsClient,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $httpFactory,
    ) {}

    public function registerSmtpServer(string $hostname): void
    {
        $this->smtpServers[] = new DemoSmtpMailer($hostname, '', '', 1025);
    }

    public function run()
    {
        while (true) {
            try {
                $this->processQueue();
            } catch (Throwable $e) {
                error_log($e->getMessage());
                exit(1);
            }
        }
    }

    private function processQueue(): void
    {
        $args = [
            'QueueUrl' => getenv('AWS_QUEUE_URL'),
            'MessageAttributeNames' => ['traceparent'],
        ];
        $startTime = (int)(microtime(true) * 1000000000);
        $result = $this->sqsClient->receiveMessage($args);
        $messages = $result->get('Messages');
        if (!$messages) {
            sleep(getenv('SQS_RECEIVE_INTERVAL'));
            return;
        }

        foreach ($messages as $message) {
            $this->processMessage($message, $startTime);
        }
    }

    private function processMessage(array $message, int $startTime): void
    {
        // decode raw message body to JSON
        $event = json_decode($message['Body'], true);

        // retrieve template from CMS
        $url = sprintf('%s/api/v1/templates/%s', getenv('CMS_API_URL'), Str::kebab($event['name']));
        $response = $this->httpClient->sendRequest(
            $this->httpFactory->createRequest('GET', $url)
        );

        // substitute template variables
        $template = $response->getBody()->getContents();
        foreach ($event['context'] as $key => $value) {
            $template = str_replace("{{ $key }}", $value, $template);
        }

        // send email message using populated template
        $mail = new Message();
        $mail->setFrom('ACME LLC <acme.llc@example.com>')
            ->addTo($event['context']['email'])
            ->setSubject(preg_replace('/([a-z])([A-Z])/s','$1 $2', $event['name']))
            ->setBody($template);
        $this->smtpServers[mt_rand(0, 1)]->send($mail);

        // mark message as received in SQS
        $this->sqsClient->deleteMessage([
            'QueueUrl' => getenv('AWS_QUEUE_URL'),
            'ReceiptHandle' => $message['ReceiptHandle']
        ]);
    }
}
