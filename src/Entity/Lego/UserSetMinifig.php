<?php

namespace App\Entity\Lego;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * User specific state of a minifig of a set within a list,
 * e.g. how many of this minifig are missing.
 */
#[ORM\Entity]
#[ORM\Table(name: 'lego_user_set_minifig')]
#[ORM\UniqueConstraint(name: 'uniq_user_set_minifig', columns: ['set_list_set_id', 'set_minifig_id'])]
class UserSetMinifig
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: 'Ramsey\Uuid\Doctrine\UuidGenerator')]
    private ?UuidInterface $id = null;

    #[ORM\ManyToOne(targetEntity: SetListSet::class, inversedBy: 'minifigStates')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SetListSet $setListSet;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SetMinifig $setMinifig;

    #[ORM\Column(type: 'integer')]
    private int $missingQuantity = 0;

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getSetListSet(): SetListSet
    {
        return $this->setListSet;
    }

    public function setSetListSet(SetListSet $setListSet): self
    {
        $this->setListSet = $setListSet;
        return $this;
    }

    public function getSetMinifig(): SetMinifig
    {
        return $this->setMinifig;
    }

    public function setSetMinifig(SetMinifig $setMinifig): self
    {
        $this->setMinifig = $setMinifig;
        return $this;
    }

    public function getMissingQuantity(): int
    {
        return $this->missingQuantity;
    }

    /**
     * Clamped between 0 and the quantity of this minifig in the set.
     */
    public function setMissingQuantity(int $missingQuantity): self
    {
        $this->missingQuantity = max(0, min($missingQuantity, $this->setMinifig->getQuantity()));
        return $this;
    }
}
