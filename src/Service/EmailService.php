<?php
namespace App\Service;

use App\Dto\Request\User\ProfileRequest;
use App\Entity\User;
use App\Entity\UserData;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Service to send emails
 */
class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param string $template
     * @param array|string $to
     * @param string $subject
     * @param array $templateVariables
     */
    public function send(string $template, array|string $to, string $subject, array $templateVariables = []): void
    {
        $toAddress = is_array($to) ? new Address($to['address'], $to['name']) : new Address($to);

        $email = (new TemplatedEmail())
            ->to($toAddress)
            ->subject($subject)
            ->context($templateVariables)
            ->htmlTemplate(sprintf('emails/%s.html.twig', $template))
            ->textTemplate(sprintf('emails/%s.text.twig', $template));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning(
                sprintf('Cannot send email message: %s', $e->getMessage()),
                [
                    'exception' => $e,
                    'template' => $template,
                    'to' => $to,
                    'subject' => $subject,
                    'templateVariables' => $templateVariables,
                ]
            );
        }
    }

    /**
     * @param string $template
     * @param UserData $user
     * @param string $subject
     * @param array $templateVariables
     */
    public function sendToUser(string $template, ProfileRequest $user, string $subject, array $templateVariables = []): void
    {
        $this->send(
            $template,
            ['address' => $user->getEmail(), 'name' => sprintf('%s %s', $user->getFirstName(), $user->getLastName())],
            $subject,
            $templateVariables
        );
    }
}
