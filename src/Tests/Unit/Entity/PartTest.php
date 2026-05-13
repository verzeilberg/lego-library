<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Lego\Part;
use App\Entity\Lego\PartColor;
use PHPUnit\Framework\TestCase;

class PartTest extends TestCase
{
    private Part $part;

    protected function setUp(): void
    {
        $this->part = new Part();
    }

    public function testColorsCollectionIsEmptyOnCreation(): void
    {
        $this->assertCount(0, $this->part->getColors());
    }

    public function testSetAndGetPartNumber(): void
    {
        $this->part->setPartNumber('3001');
        $this->assertEquals('3001', $this->part->getPartNumber());
    }

    public function testSetAndGetName(): void
    {
        $this->part->setName('Brick 2x4');
        $this->assertEquals('Brick 2x4', $this->part->getName());
    }

    public function testSetPartNumberReturnsSelf(): void
    {
        $result = $this->part->setPartNumber('3001');
        $this->assertSame($this->part, $result);
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->part->setName('Brick 2x4');
        $this->assertSame($this->part, $result);
    }

    public function testAddColorAddsToCollection(): void
    {
        $partColor = $this->createMock(PartColor::class);
        $partColor->expects($this->once())->method('setPart')->with($this->part);

        $this->part->addColor($partColor);

        $this->assertCount(1, $this->part->getColors());
        $this->assertTrue($this->part->getColors()->contains($partColor));
    }

    public function testAddColorDoesNotAddDuplicate(): void
    {
        $partColor = $this->createMock(PartColor::class);
        $partColor->expects($this->once())->method('setPart');

        $this->part->addColor($partColor);
        $this->part->addColor($partColor);

        $this->assertCount(1, $this->part->getColors());
    }

    public function testRemoveColorRemovesFromCollection(): void
    {
        $partColor = $this->createMock(PartColor::class);
        $partColor->method('setPart');

        $this->part->addColor($partColor);
        $this->part->removeColor($partColor);

        $this->assertCount(0, $this->part->getColors());
    }

    public function testAddColorReturnsSelf(): void
    {
        $partColor = $this->createMock(PartColor::class);
        $partColor->method('setPart');

        $result = $this->part->addColor($partColor);
        $this->assertSame($this->part, $result);
    }

    public function testRemoveColorReturnsSelf(): void
    {
        $partColor = $this->createMock(PartColor::class);
        $partColor->method('setPart');

        $this->part->addColor($partColor);
        $result = $this->part->removeColor($partColor);
        $this->assertSame($this->part, $result);
    }

    public function testMultipleColorsCanBeAdded(): void
    {
        $red = $this->createMock(PartColor::class);
        $red->method('setPart');
        $blue = $this->createMock(PartColor::class);
        $blue->method('setPart');

        $this->part->addColor($red);
        $this->part->addColor($blue);

        $this->assertCount(2, $this->part->getColors());
    }
}
