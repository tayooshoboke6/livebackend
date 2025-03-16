<?php

namespace App\Mail\Transport;

use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendSmtpEmail;
use SendinBlue\Client\Model\SendSmtpEmailTo;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    /**
     * The Brevo API client instance.
     *
     * @var \SendinBlue\Client\Api\TransactionalEmailsApi
     */
    protected $client;

    /**
     * Create a new Brevo transport instance.
     *
     * @param string $apiKey
     * @return void
     */
    public function __construct(string $apiKey)
    {
        parent::__construct();

        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', $apiKey);

        $this->client = new TransactionalEmailsApi(null, $config);
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());     

        $this->sendToBrevo($email);
    }

    /**
     * Send the email message to Brevo API.
     *
     * @param \Symfony\Component\Mime\Email $email
     * @return void
     */
    protected function sendToBrevo(Email $email): void
    {
        $sendSmtpEmail = new SendSmtpEmail();

        // Set sender
        $from = $email->getFrom();
        if (count($from) > 0) {
            $fromAddress = $from[0];
            $sendSmtpEmail->setSender([
                'name' => $fromAddress->getName() ?: $fromAddress->getAddress(),
                'email' => $fromAddress->getAddress(),
            ]);
        }

        // Set recipients
        $recipients = [];
        foreach ($email->getTo() as $to) {
            $recipients[] = new SendSmtpEmailTo([
                'name' => $to->getName() ?: $to->getAddress(), // Use email as name if name is empty
                'email' => $to->getAddress(),
            ]);
        }
        $sendSmtpEmail->setTo($recipients);

        // Set CC recipients
        $ccRecipients = [];
        foreach ($email->getCc() as $cc) {
            $ccRecipients[] = new SendSmtpEmailTo([
                'name' => $cc->getName() ?: $cc->getAddress(), // Use email as name if name is empty
                'email' => $cc->getAddress(),
            ]);
        }
        if (!empty($ccRecipients)) {
            $sendSmtpEmail->setCc($ccRecipients);
        }

        // Set BCC recipients
        $bccRecipients = [];
        foreach ($email->getBcc() as $bcc) {
            $bccRecipients[] = new SendSmtpEmailTo([
                'name' => $bcc->getName() ?: $bcc->getAddress(), // Use email as name if name is empty
                'email' => $bcc->getAddress(),
            ]);
        }
        if (!empty($bccRecipients)) {
            $sendSmtpEmail->setBcc($bccRecipients);
        }

        // Set subject
        $sendSmtpEmail->setSubject($email->getSubject());

        // Set content
        $htmlContent = $email->getHtmlBody();
        $textContent = $email->getTextBody();

        if ($htmlContent) {
            $sendSmtpEmail->setHtmlContent($htmlContent);
        }

        if ($textContent) {
            $sendSmtpEmail->setTextContent($textContent);
        }

        // Send the email
        $this->client->sendTransacEmail($sendSmtpEmail);
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'brevo';
    }
}
