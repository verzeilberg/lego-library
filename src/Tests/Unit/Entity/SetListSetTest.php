<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetList;
use App\Entity\Lego\SetListSet;
use App\Entity\Media\MediaObject;
use PHPUnit\Framework\TestCase;

class SetListSetTest extends TestCase
{
    private SetListSet $setListSet;

    protected function setUp(): void
    {
        $this->setListSet = new SetListSet();
    }

    public function testMediaObjectsCollectionIsEmptyOnCreation(): void
    {
        $this->assertCount(0, $this->setListSet->getMediaObjects());
    }

    public function testIdIsNullOnCreation(): void
    {
        $this->assertNull($this->setListSet->getId());
    }

    public function testShowImagesDefaultIsTrue(): void
    {
        $this->assertTrue($this->setListSet->isShowImages());
    }

    public function testShowPartsDefaultIsTrue(): void
    {
        $this->assertTrue($this->setListSet->isShowParts());
    }

    public function testShowMinifsDefaultIsTrue(): void
    {
        $this->assertTrue($this->setListSet->isShowMinifigs());
    }

    public function testCompleteDefaultIsTrue(): void
    {
        $this->assertTrue($this->setListSet->isComplete());
    }

    public function testInstructionsDefaultIsTrue(): void
    {
        $this->assertTrue($this->setListSet->hasInstructions());
    }

    public function testSetShowImages(): void
    {
        $this->setListSet->setShowImages(false);
        $this->assertFalse($this->setListSet->isShowImages());
    }

    public function testSetShowParts(): void
    {
        $this->setListSet->setShowParts(false);
        $this->assertFalse($this->setListSet->isShowParts());
    }

    public function testSetShowMinifigs(): void
    {
        $this->setListSet->setShowMinifigs(false);
        $this->assertFalse($this->setListSet->isShowMinifigs());
    }

    public function testSetComplete(): void
    {
        $this->setListSet->setComplete(false);
        $this->assertFalse($this->setListSet->isComplete());
    }

    public function testSetInstructions(): void
    {
        $this->setListSet->setInstructions(false);
        $this->assertFalse($this->setListSet->hasInstructions());
    }

    public function testSetAndGetSet(): void
    {
        $set = $this->createMock(Set::class);
        $this->setListSet->setSet($set);
        $this->assertSame($set, $this->setListSet->getSet());
    }

    public function testSetAndGetSetList(): void
    {
        $setList = $this->createMock(SetList::class);
        $this->setListSet->setSetList($setList);
        $this->assertSame($setList, $this->setListSet->getSetList());
    }

    public function testAddMediaObjectAddsToCollection(): void
    {
        $media = $this->createMock(MediaObject::class);
        $media->expects($this->once())->method('setSetListSet')->with($this->setListSet);

        $this->setListSet->addMediaObject($media);

        $this->assertCount(1, $this->setListSet->getMediaObjects());
        $this->assertTrue($this->setListSet->getMediaObjects()->contains($media));
    }

    public function testAddMediaObjectDoesNotAddDuplicate(): void
    {
        $media = $this->createMock(MediaObject::class);
        $media->expects($this->once())->method('setSetListSet');

        $this->setListSet->addMediaObject($media);
        $this->setListSet->addMediaObject($media);

        $this->assertCount(1, $this->setListSet->getMediaObjects());
    }

    public function testRemoveMediaObjectClearsOwningside(): void
    {
        $media = $this->createMock(MediaObject::class);
        // First call: addMediaObject sets the owning side to this SetListSet
        // Second call: removeMediaObject clears it to null
        $media->expects($this->exactly(2))
            ->method('setSetListSet')
            ->willReturnCallback(function ($arg) {});
        $media->method('getSetListSet')->willReturn($this->setListSet);

        $this->setListSet->addMediaObject($media);
        $this->setListSet->removeMediaObject($media);

        $this->assertCount(0, $this->setListSet->getMediaObjects());
    }

    public function testSetShowImagesReturnsSelf(): void
    {
        $result = $this->setListSet->setShowImages(false);
        $this->assertSame($this->setListSet, $result);
    }

    public function testSetShowPartsReturnsSelf(): void
    {
        $result = $this->setListSet->setShowParts(false);
        $this->assertSame($this->setListSet, $result);
    }

    public function testSetShowMinifsReturnsSelf(): void
    {
        $result = $this->setListSet->setShowMinifigs(false);
        $this->assertSame($this->setListSet, $result);
    }

    public function testSetCompleteReturnsSelf(): void
    {
        $result = $this->setListSet->setComplete(false);
        $this->assertSame($this->setListSet, $result);
    }

    public function testSetInstructionsReturnsSelf(): void
    {
        $result = $this->setListSet->setInstructions(false);
        $this->assertSame($this->setListSet, $result);
    }
}
