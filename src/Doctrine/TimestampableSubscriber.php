<?php
namespace App\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use App\Entity\Traits\TimestampableTrait;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class TimestampableSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!in_array(TimestampableTrait::class, class_uses($entity))) {
            return;
        }

        $now = new \DateTimeImmutable();

        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!in_array(TimestampableTrait::class, class_uses($entity))) {
            return;
        }

        $entity->setUpdatedAt(new \DateTimeImmutable());
    }
}
