<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Lego\Set;
use App\Entity\Lego\SetListSet;
use App\Entity\Lego\SetMinifig;
use App\Entity\Lego\SetPart;
use App\Entity\Lego\SetRating;
use App\Entity\Lego\Theme;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    private Set $set;

    protected function setUp(): void
    {
        $this->set = new Set();
    }

    public function testCollectionsAreEmptyOnCreation(): void
    {
        $this->assertCount(0, $this->set->getSetParts());
        $this->assertCount(0, $this->set->getSetMinifigs());
        $this->assertCount(0, $this->set->getListLinks());
        $this->assertCount(0, $this->set->getRatings());
    }

    public function testSetAndGetNumber(): void
    {
        $this->set->setNumber('75192-1');
        $this->assertEquals('75192-1', $this->set->getNumber());
    }

    public function testSetAndGetBaseNumber(): void
    {
        $this->set->setBaseNumber('75192');
        $this->assertEquals('75192', $this->set->getBaseNumber());
    }

    public function testSetIdSetsBaseNumber(): void
    {
        $this->set->setId('75192');
        $this->assertEquals('75192', $this->set->getBaseNumber());
        $this->assertEquals('75192', $this->set->getId());
    }

    public function testSetAndGetName(): void
    {
        $this->set->setName('Millennium Falcon');
        $this->assertEquals('Millennium Falcon', $this->set->getName());
    }

    public function testDefaultNameIsEmptyString(): void
    {
        $this->assertEquals('', $this->set->getName());
    }

    public function testSetAndGetYear(): void
    {
        $this->set->setYear(2017);
        $this->assertEquals(2017, $this->set->getYear());
    }

    public function testSetAndGetNumParts(): void
    {
        $this->set->setNumParts(7541);
        $this->assertEquals(7541, $this->set->getNumParts());
    }

    public function testDefaultNumPartsIsZero(): void
    {
        $this->assertEquals(0, $this->set->getNumParts());
    }

    public function testSetAndGetRating(): void
    {
        $this->set->setRating(4.5);
        $this->assertEquals(4.5, $this->set->getRating());
    }

    public function testDefaultRatingIsZero(): void
    {
        $this->assertEquals(0.0, $this->set->getRating());
    }

    public function testSetAndGetFilePath(): void
    {
        $this->set->setFilePath('uploads/set.jpg');
        $this->assertEquals('uploads/set.jpg', $this->set->getFilePath());
    }

    public function testSetFilePathToNull(): void
    {
        $this->set->setFilePath('uploads/set.jpg');
        $this->set->setFilePath(null);
        $this->assertNull($this->set->getFilePath());
    }

    public function testSetAndGetContentUrl(): void
    {
        $this->set->setContentUrl('https://example.com/set.jpg');
        $this->assertEquals('https://example.com/set.jpg', $this->set->getContentUrl());
    }

    public function testSetAndGetTheme(): void
    {
        $theme = $this->createMock(Theme::class);
        $this->set->setTheme($theme);
        $this->assertSame($theme, $this->set->getTheme());
    }

    public function testGetThemeNameReturnsNullWhenNoTheme(): void
    {
        $this->assertNull($this->set->getThemeName());
    }

    public function testGetThemeNameDelegatesToTheme(): void
    {
        $theme = $this->createMock(Theme::class);
        $theme->method('getName')->willReturn('Star Wars');
        $this->set->setTheme($theme);

        $this->assertEquals('Star Wars', $this->set->getThemeName());
    }

    public function testAddSetPartSynchronisesOwningside(): void
    {
        $setPart = $this->createMock(SetPart::class);
        $setPart->expects($this->once())->method('setModel')->with($this->set);

        $this->set->addSetPart($setPart);

        $this->assertCount(1, $this->set->getSetParts());
    }

    public function testAddSetPartDoesNotAddDuplicate(): void
    {
        $setPart = $this->createMock(SetPart::class);
        $setPart->expects($this->once())->method('setModel');

        $this->set->addSetPart($setPart);
        $this->set->addSetPart($setPart);

        $this->assertCount(1, $this->set->getSetParts());
    }

    public function testRemoveSetPartClearsOwningSide(): void
    {
        $setPart = $this->createMock(SetPart::class);
        $setPart->method('setModel');
        $setPart->method('getModel')->willReturn($this->set);

        $this->set->addSetPart($setPart);
        $setPart->expects($this->once())->method('setModel')->with(null);
        $this->set->removeSetPart($setPart);

        $this->assertCount(0, $this->set->getSetParts());
    }

    public function testAddSetMinifigSynchronisesOwningside(): void
    {
        $minifig = $this->createMock(SetMinifig::class);
        $minifig->expects($this->once())->method('setSet')->with($this->set);

        $this->set->addSetMinifig($minifig);

        $this->assertCount(1, $this->set->getSetMinifigs());
    }

    public function testAddSetMinifigDoesNotAddDuplicate(): void
    {
        $minifig = $this->createMock(SetMinifig::class);
        $minifig->expects($this->once())->method('setSet');

        $this->set->addSetMinifig($minifig);
        $this->set->addSetMinifig($minifig);

        $this->assertCount(1, $this->set->getSetMinifigs());
    }

    public function testAddListLinkSynchronisesOwningside(): void
    {
        $link = $this->createMock(SetListSet::class);
        $link->expects($this->once())->method('setSet')->with($this->set);

        $this->set->addListLink($link);

        $this->assertCount(1, $this->set->getListLinks());
    }

    public function testAddListLinkDoesNotAddDuplicate(): void
    {
        $link = $this->createMock(SetListSet::class);
        $link->expects($this->once())->method('setSet');

        $this->set->addListLink($link);
        $this->set->addListLink($link);

        $this->assertCount(1, $this->set->getListLinks());
    }

    public function testRemoveListLinkClearsOwningSide(): void
    {
        $link = $this->createMock(SetListSet::class);
        $link->method('setSet');
        $link->method('getSet')->willReturn($this->set);

        $this->set->addListLink($link);
        $link->expects($this->once())->method('setSet')->with(null);
        $this->set->removeListLink($link);

        $this->assertCount(0, $this->set->getListLinks());
    }

    public function testAddRatingSynchronisesOwningside(): void
    {
        $rating = $this->createMock(SetRating::class);
        $rating->expects($this->once())->method('setSet')->with($this->set);

        $this->set->addRating($rating);

        $this->assertCount(1, $this->set->getRatings());
    }

    public function testAddRatingDoesNotAddDuplicate(): void
    {
        $rating = $this->createMock(SetRating::class);
        $rating->expects($this->once())->method('setSet');

        $this->set->addRating($rating);
        $this->set->addRating($rating);

        $this->assertCount(1, $this->set->getRatings());
    }

    public function testSetTotalPartsQuantity(): void
    {
        $this->set->setTotalPartsQuantity(100);
        $this->assertEquals(100, $this->set->getTotalPartsQuantity());
    }

    public function testSetTotalParts(): void
    {
        $this->set->setTotalParts(50);
        $this->assertEquals(50, $this->set->getTotalParts());
    }

    public function testSetTotalMiniFigParts(): void
    {
        $this->set->setTotalMiniFigParts(25);
        $this->assertEquals(25, $this->set->getTotalMiniFigParts());
    }
}
