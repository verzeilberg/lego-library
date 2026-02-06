<?php

namespace App\Entity\Lego;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\Lego\CreateRateForSetController;
use App\Controller\Lego\CreateSetController;
use App\Controller\Lego\GetSetController;
use App\Dto\Request\Lego\CreateSetRequest;
use App\Dto\Request\Lego\RateSetRequest;
use App\Entity\Traits\TimestampableTrait;
use App\Entity\User\UserData;
use App\Repository\Lego\SetRatingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SetRatingRepository::class)]
#[ORM\Table(
    name: 'lego_set_rating',
    indexes: [
        new ORM\Index(name: 'idx_set_rating_set', columns: ['set_number']),
        new ORM\Index(name: 'idx_set_rating_user', columns: ['user_id']),
    ],
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_user_set_rating',
            columns: ['user_id', 'set_number']
        )
    ]
)]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/lego/sets/rate-set',
            formats: ['json' => ['application/json']],
            defaults: ['dto' => RateSetRequest::class],
            controller: CreateRateForSetController::class,
            shortName: 'Rate lego set',
            input: RateSetRequest::class,
            output: RateSetRequest::class,
            deserialize: true,
        )
        ]
)]
class SetRating
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserData::class, inversedBy: 'setRatings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?UserData $user = null;

    #[ORM\ManyToOne(targetEntity: Set::class, inversedBy: 'ratings')]
    #[ORM\JoinColumn(
        name: 'set_number',
        referencedColumnName: 'number',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ?Set $set = null;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 5)]
    private int $value = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?UserData
    {
        return $this->user;
    }

    public function setUser(UserData $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSet(): ?Set
    {
        return $this->set;
    }

    public function setSet(Set $set): self
    {
        $this->set = $set;

        return $this;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;

        return $this;
    }
}
