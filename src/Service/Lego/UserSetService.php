<?php

namespace App\Service\Lego;

use App\Entity\Lego\Set;
use App\Entity\Lego\UserSet;
use App\Entity\Lego\UserSetPart;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;


class UserSetService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function addSetToUser(User $user, Set $set): UserSet
    {
        $userSet = new UserSet();
        $userSet->setUser($user)->setSet($set)->setIsComplete(false);
        $this->em->persist($userSet);

        foreach ($set->getSetParts() as $setPart) {
            $usp = new UserSetPart();
            $usp->setUserSet($userSet)
                ->setSetPart($setPart)
                ->setMissingQuantity($setPart->getQuantity())
                ->setDamagedQuantity(0);
            $userSet->getPartStates()->add($usp);
            $this->em->persist($usp);
        }

        $this->em->flush();
        return $userSet;
    }
}
