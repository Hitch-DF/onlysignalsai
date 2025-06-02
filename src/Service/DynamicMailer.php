<?php

namespace App\Service;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment as TwigEnvironment;

class DynamicMailer
{
    private MailerInterface $mailer;

    public function __construct(
        private TwigEnvironment $twig,
        string $mailerDsn,
        private string $senderEmail,
        private string $senderName
    ) {
        $this->mailer = new Mailer(Transport::fromDsn($mailerDsn));
    }

    public function sendTemplatedEmail(
        string $to,
        string $subject,
        string $templatePath,
        array $templateVars = [],
        array $attachments = []
    ): void {
        $htmlContent = $this->twig->render($templatePath, $templateVars);

        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($to)
            ->subject($subject)
            ->html($htmlContent);

        foreach ($attachments as $filePath) {
            if (file_exists($filePath)) {
                $email->attachFromPath($filePath);
            }
        }

        $this->mailer->send($email);
    }
}
