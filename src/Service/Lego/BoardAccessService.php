<?php

namespace App\Service\Lego;

use App\Entity\Lego\SetList;
use App\Entity\User\UserData;
use App\Enum\BoardVisibility;
use App\Repository\FriendshipRepository;

/**
 * Decides who may see a board.
 *
 * - public:  everyone
 * - friends: the owner's friends
 * - private: only the owner
 * Users the board (or a board above it) is shared with can always see it.
 */
readonly class BoardAccessService
{
    public function __construct(
        private FriendshipRepository $friendshipRepository,
    ) {}

    public function canView(SetList $setList, ?UserData $viewer): bool
    {
        if ($setList->getVisibility() === BoardVisibility::Public) {
            return true;
        }

        if ($viewer === null) {
            return false;
        }

        if ($setList->canContribute($viewer)) {
            return true;
        }

        return $setList->getVisibility() === BoardVisibility::Friends
            && $this->areFriends($setList->getUserData(), $viewer);
    }

    public function areFriends(?UserData $a, ?UserData $b): bool
    {
        if ($a === null || $b === null || $a === $b) {
            return false;
        }

        return $this->friendshipRepository->findBetween($a, $b)?->getStatus() === 'accepted';
    }
}
