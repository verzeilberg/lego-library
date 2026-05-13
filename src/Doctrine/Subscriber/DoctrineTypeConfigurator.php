<?php

namespace App\Doctrine\Subscriber;

use App\Doctrine\Type\EncryptedStringType;
use App\Service\EmailEncryptionService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DoctrineTypeConfigurator implements EventSubscriberInterface
{
    public function __construct(private readonly EmailEncryptionService $encryptionService) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->configure();
    }

    public function configure(): void
    {
        if (!EncryptedStringType::hasService()) {
            EncryptedStringType::setService($this->encryptionService);
        }
    }
}