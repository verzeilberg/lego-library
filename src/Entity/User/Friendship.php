<?php

namespace App\Entity\User;

use App\Repository\FriendshipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FriendshipRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_friendship', columns: ['requester_id', 'recipient_id'])]
class Friendship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserData::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserData $requester = null;

    #[ORM\ManyToOne(targetEntity: UserData::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserData $recipient = null;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getRequester(): ?UserData { return $this->requester; }
    public function setRequester(?UserData $requester): self { $this->requester = $requester; return $this; }

    public function getRecipient(): ?UserData { return $this->recipient; }
    public function setRecipient(?UserData $recipient): self { $this->recipient = $recipient; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
