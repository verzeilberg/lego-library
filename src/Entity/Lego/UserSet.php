<?php

namespace App\Entity\Lego;

use App\Entity\User\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class UserSet
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Set::class)]
    #[ORM\JoinColumn(
            name: 'set_number',
            referencedColumnName: 'number',
            nullable: false,
            onDelete: 'CASCADE'
    )]
    private Set $set;

    #[ORM\Column]
    private bool $isComplete = false;

    #[ORM\OneToMany(mappedBy: 'userSet', targetEntity: UserSetPart::class, cascade: ['persist','remove'])]
    private Collection $partStates;

    public function __construct() { $this->partStates = new ArrayCollection(); }

    public function getCompletionPercentage(): float
    {
        $total = 0;
        $owned = 0;
        foreach ($this->partStates as $usp) {
            $total += $usp->getSetPart()->getQuantity();
            $owned += $usp->getSetPart()->getQuantity() - $usp->getMissingQuantity() - $usp->getDamagedQuantity();
        }
        return $total > 0 ? ($owned / $total) * 100 : 0;
    }
}
