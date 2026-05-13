<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Lego\Color;
use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    private Color $color;

    protected function setUp(): void
    {
        $this->color = new Color();
    }

    public function testSetRgbConvertsToUppercase(): void
    {
        $this->color->setRgb('ff0000');
        $this->assertEquals('FF0000', $this->color->getRgb());
    }

    public function testSetRgbAlreadyUppercaseIsUnchanged(): void
    {
        $this->color->setRgb('FF0000');
        $this->assertEquals('FF0000', $this->color->getRgb());
    }

    public function testSetRgbMixedCaseConvertsToUppercase(): void
    {
        $this->color->setRgb('aAbBcC');
        $this->assertEquals('AABBCC', $this->color->getRgb());
    }

    public function testGetHexColorPrefixesHash(): void
    {
        $this->color->setRgb('FF0000');
        $this->assertEquals('#FF0000', $this->color->getHexColor());
    }

    public function testGetHexColorWithLowercaseInputConvertsCorrectly(): void
    {
        $this->color->setRgb('00ff00');
        $this->assertEquals('#00FF00', $this->color->getHexColor());
    }

    public function testIsTransDefaultIsFalse(): void
    {
        $this->assertFalse($this->color->isTrans());
    }

    public function testSetIsTransToTrue(): void
    {
        $this->color->setIsTrans(true);
        $this->assertTrue($this->color->isTrans());
    }

    public function testSetIsTransToFalse(): void
    {
        $this->color->setIsTrans(true);
        $this->color->setIsTrans(false);
        $this->assertFalse($this->color->isTrans());
    }

    public function testSetAndGetName(): void
    {
        $this->color->setName('Red');
        $this->assertEquals('Red', $this->color->getName());
    }

    public function testSetAndGetId(): void
    {
        $this->color->setId(42);
        $this->assertEquals(42, $this->color->getId());
    }

    public function testSetIdReturnsSelf(): void
    {
        $result = $this->color->setId(1);
        $this->assertSame($this->color, $result);
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->color->setName('Blue');
        $this->assertSame($this->color, $result);
    }

    public function testSetRgbReturnsSelf(): void
    {
        $result = $this->color->setRgb('0000FF');
        $this->assertSame($this->color, $result);
    }

    public function testSetIsTransReturnsSelf(): void
    {
        $result = $this->color->setIsTrans(true);
        $this->assertSame($this->color, $result);
    }
}
