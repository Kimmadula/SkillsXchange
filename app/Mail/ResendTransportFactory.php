<?php

namespace App\Mail;

use Exception;
use Resend\Contracts\Client;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class ResendTransportFactory extends AbstractTransport
{
    /**
     * Create a new Resend transport instance.
     */
    public function __construct(
        protected Client $resend,
        protected array $config = []
    ) {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $headers = [];
        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'subject', 'content-type', 'sender', 'reply-to'];
        foreach ($email->getHeaders()->all() as $name => $header) {
            if (in_array($name, $headersToBypass, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        $attachments = [];
        if ($email->getAttachments()) {
            foreach ($email->getAttachments() as $attachment) {
                $headers = $attachment->getPreparedHeaders();
                $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');

                $item = [
                    'content' => str_replace("\r\n", '', $attachment->bodyToString()),
                    'filename' => $filename,
                ];

                $attachments[] = $item;
            }
        }

        try {
            // Debug: Log the configuration before sending
            \Log::info('Resend email configuration', [
                'from' => $envelope->getSender()->toString(),
                'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
                'subject' => $email->getSubject(),
                'config' => $this->config
            ]);

            // Safely prepare the from address
            $fromAddress = $envelope->getSender()->toString();
            if (empty($fromAddress)) {
                // Fallback to config if envelope sender is empty
                $fromAddress = ($this->config['from']['name'] ?? '') . ' <' . ($this->config['from']['address'] ?? 'onboarding@resend.dev') . '>';
            }

            $result = $this->resend->emails->send([
                'bcc' => $this->stringifyAddresses($email->getBcc()),
                'cc' => $this->stringifyAddresses($email->getCc()),
                'from' => $fromAddress,
                'headers' => $headers,
                'html' => $email->getHtmlBody(),
                'reply_to' => $this->stringifyAddresses($email->getReplyTo()),
                'subject' => $email->getSubject(),
                'text' => $email->getTextBody(),
                'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
                'attachments' => $attachments,
            ]);
        } catch (Exception $exception) {
            \Log::error('Resend email sending failed', [
                'error' => $exception->getMessage(),
                'from' => $envelope->getSender()->toString(),
                'config' => $this->config
            ]);
            throw new Exception(
                $exception->getMessage(),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception
            );
        }

        $messageId = $result->id;

        $email->getHeaders()->addHeader('X-Resend-Email-ID', $messageId);
    }

    /**
     * Get the recipients without CC or BCC.
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), function (Address $address) use ($email) {
            return in_array($address, array_merge($email->getCc(), $email->getBcc()), true) === false;
        });
    }

    /**
     * Convert an array of Address objects to an array of strings.
     */
    protected function stringifyAddresses(array $addresses): array
    {
        return array_map(function ($address) {
            // Handle both Address objects and strings
            if ($address instanceof Address) {
                return $address->toString();
            }
            
            // If it's already a string, return as is
            if (is_string($address)) {
                return $address;
            }
            
            // Fallback for unexpected types
            \Log::warning('Unexpected address type in stringifyAddresses', [
                'type' => gettype($address),
                'value' => $address
            ]);
            
            return (string) $address;
        }, $addresses);
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'resend';
    }
}
