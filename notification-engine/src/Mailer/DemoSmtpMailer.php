<?php

namespace Demo\Project\Mailer;

use Nette\Mail\SmtpMailer;

/**
 * For the purposes of this tutorial, DemoSmtpMailer is just an extension of SmtpMailer that copies the passed $host
 * to a public readonly variable called $name, which can be used to include the mailing provider name in the trace.
 */
class DemoSmtpMailer extends SmtpMailer
{
    public readonly string $name;

    public function __construct(string $host, string $username, #[\SensitiveParameter] string $password, int $port)
    {
        $this->name = $host;
        parent::__construct($host, $username, $password, $port);
    }
}
