<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Lego\SetList;
use App\Entity\User\UserData;
use App\Enum\BoardVisibility;
use PHPUnit\Framework\TestCase;

class SetListTest extends TestCase
{
    private UserData $owner;
    private UserData $friend;
    private UserData $stranger;
    private SetList $root;
    private SetList $child;
    private SetList $grandchild;

    protected function setUp(): void
    {
        $this->owner = new UserData();
        $this->friend = new UserData();
        $this->stranger = new UserData();

        $this->root = $this->board($this->owner);
        $this->child = $this->board($this->owner, $this->root);
        $this->grandchild = $this->board($this->owner, $this->child);

        $this->root->addSharedWith($this->friend);
    }

    private function board(UserData $owner, ?SetList $parent = null): SetList
    {
        $board = new SetList();
        $board->setUserData($owner);
        if ($parent !== null) {
            $parent->addChildList($board);
        }
        return $board;
    }

    public function testSharedUserCanContributeToEveryBoardInsideTheSharedBoard(): void
    {
        $this->assertTrue($this->root->canContribute($this->friend));
        $this->assertTrue($this->child->canContribute($this->friend));
        $this->assertTrue($this->grandchild->canContribute($this->friend));
        $this->assertFalse($this->grandchild->canContribute($this->stranger));
    }

    public function testSharedUserCannotEditTheSharedBoardItself(): void
    {
        $this->assertTrue($this->root->canEdit($this->owner));
        $this->assertFalse($this->root->canEdit($this->friend));
        $this->assertTrue($this->child->canEdit($this->friend));
        $this->assertTrue($this->grandchild->canEdit($this->friend));
        $this->assertFalse($this->child->canEdit($this->stranger));
    }

    public function testOwnerCanManageSubBoardsCreatedByASharedUser(): void
    {
        $friendsBoard = $this->board($this->friend, $this->child);

        $this->assertTrue($friendsBoard->canContribute($this->owner));
        $this->assertTrue($friendsBoard->canEdit($this->owner));
    }

    public function testBoardsOutsideTheSharedBoardStayClosed(): void
    {
        $otherBoard = $this->board($this->owner);

        $this->assertFalse($otherBoard->canContribute($this->friend));
        $this->assertFalse($otherBoard->canEdit($this->friend));
    }

    public function testContainsMatchesItselfAndSubBoardsOnly(): void
    {
        $this->assertTrue($this->child->contains($this->child));
        $this->assertTrue($this->child->contains($this->grandchild));
        $this->assertFalse($this->child->contains($this->root));
    }

    public function testPublicFlagMapsToVisibility(): void
    {
        $this->root->setPublic(true);
        $this->assertSame(BoardVisibility::Public, $this->root->getVisibility());

        $this->root->setVisibility(BoardVisibility::Friends);
        $this->assertFalse($this->root->isPublic());
        $this->assertTrue($this->root->isVisibleToFriends());

        $this->root->setPublic(false);
        $this->assertFalse($this->root->isVisibleToFriends());
    }
}
