<?php

namespace App\Doctrine\Subscriber;

use App\Doctrine\Type\EncryptedStringType;
use App\Service\EmailEncryptionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;

#[AsDoctrineListener(event: Events::loadClassMetadata, priority: 255)]
class DoctrineTypeConfigurator
{
    public function __construct(private readonly EmailEncryptionService $encryptionService) {}

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        if (!EncryptedStringType::hasService()) {
            EncryptedStringType::setService($this->encryptionService);
        }
    }
}