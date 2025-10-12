<?php

namespace App\Mail;

use Exception;
use Illuminate\Support\Facades\Log;
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
     * 
     * @param Client $resend The Resend client instance
     * @param array $config Configuration array
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
            // Prepare the from address as string format (Resend API expects string)
            $fromAddress = config('mail.from.address');
            $fromName = config('mail.from.name');
            $fromString = $fromName ? "{$fromName} <{$fromAddress}>" : $fromAddress;

            // Debug: Log the configuration before sending
            Log::info('Resend email configuration', [
                'from_address' => $fromAddress,
                'from_name' => $fromName,
                'from_string' => $fromString,
                'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
                'subject' => $email->getSubject(),
                'config' => $this->config
            ]);

            /** @var \Resend\Service\Email $emails */
            $emails = $this->resend->emails;
            
            $emailData = [
                'bcc' => $this->stringifyAddresses($email->getBcc()),
                'cc' => $this->stringifyAddresses($email->getCc()),
                'from' => $fromString,
                'headers' => $headers,
                'html' => $email->getHtmlBody(),
                'reply_to' => $this->stringifyAddresses($email->getReplyTo()),
                'subject' => $email->getSubject(),
                'text' => $email->getTextBody(),
                'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
                'attachments' => $attachments,
            ];
            
            Log::info('Resend email data being sent', $emailData);
            
            $result = $emails->send($emailData);
            
            Log::info('Resend API response', ['result' => $result]);
        } catch (Exception $exception) {
            Log::error('Resend email sending failed', [
                'error' => $exception->getMessage(),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'config' => $this->config
            ]);
            
            // Check if this is the "Undefined array key 'name'" error
            if (strpos($exception->getMessage(), "Undefined array key 'name'") !== false) {
                Log::warning('Resend API returned error without name key - this might be a domain verification issue', [
                    'error' => $exception->getMessage(),
                    'suggestion' => 'Consider verifying your domain or using a different email service'
                ]);
                
                // For now, we'll throw a more user-friendly error
                throw new Exception(
                    'Email sending failed: Domain verification required. Please contact support.',
                    0,
                    $exception
                );
            }
            
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
            Log::warning('Unexpected address type in stringifyAddresses', [
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
