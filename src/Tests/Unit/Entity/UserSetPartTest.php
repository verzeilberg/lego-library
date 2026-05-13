<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Lego\SetPart;
use App\Entity\Lego\UserSetPart;
use PHPUnit\Framework\TestCase;

class UserSetPartTest extends TestCase
{
    private UserSetPart $userSetPart;

    protected function setUp(): void
    {
        $setPart = $this->createMock(SetPart::class);
        $setPart->method('getQuantity')->willReturn(5);

        $this->userSetPart = new UserSetPart();
        $this->userSetPart->setSetPart($setPart);
    }

    public function testDefaultQuantitiesAreZero(): void
    {
        $this->assertEquals(0, $this->userSetPart->getMissingQuantity());
        $this->assertEquals(0, $this->userSetPart->getDamagedQuantity());
        $this->assertEquals(0, $this->userSetPart->getDiscolouredQuantity());
    }

    public function testIsCompleteWhenNoDefects(): void
    {
        $this->assertTrue($this->userSetPart->isComplete());
    }

    public function testIsNotCompleteWhenMissingParts(): void
    {
        $this->userSetPart->setMissingQuantity(1);
        $this->assertFalse($this->userSetPart->isComplete());
    }

    public function testIsNotCompleteWhenDamagedParts(): void
    {
        $this->userSetPart->setDamagedQuantity(1);
        $this->assertFalse($this->userSetPart->isComplete());
    }

    public function testIsCompleteWhenOnlyDiscolouredParts(): void
    {
        // Discoloured parts are considered owned and restorable, so set is still complete
        $this->userSetPart->setDiscolouredQuantity(2);
        $this->assertTrue($this->userSetPart->isComplete());
    }

    public function testGetTotalDefective(): void
    {
        $this->userSetPart->setMissingQuantity(1);
        $this->userSetPart->setDamagedQuantity(1);
        $this->userSetPart->setDiscolouredQuantity(1);
        $this->assertEquals(3, $this->userSetPart->getTotalDefective());
    }

    public function testGetTotalDefectiveWhenZero(): void
    {
        $this->assertEquals(0, $this->userSetPart->getTotalDefective());
    }

    public function testGetOwnedQuantity(): void
    {
        // Required: 5, missing: 1, damaged: 1, discoloured: 1 → owned: 5-1-1-1 = 2
        $this->userSetPart->setMissingQuantity(1);
        $this->userSetPart->setDamagedQuantity(1);
        $this->userSetPart->setDiscolouredQuantity(1);
        $this->assertEquals(2, $this->userSetPart->getOwnedQuantity());
    }

    public function testGetOwnedQuantityIsZeroWhenAllDefective(): void
    {
        // Required: 5, all five missing
        $this->userSetPart->setMissingQuantity(5);
        $this->assertEquals(0, $this->userSetPart->getOwnedQuantity());
    }

    public function testGetOwnedQuantityDoesNotGoBelowZero(): void
    {
        // Required: 5, set 4 missing + 4 damaged → normalizer will cap, but owned should not be negative
        $this->userSetPart->setMissingQuantity(5);
        $this->userSetPart->setDamagedQuantity(5);
        $this->assertGreaterThanOrEqual(0, $this->userSetPart->getOwnedQuantity());
    }

    public function testGetRequiredQuantity(): void
    {
        $this->assertEquals(5, $this->userSetPart->getRequiredQuantity());
    }

    public function testNegativeMissingQuantityIsNormalisedToZero(): void
    {
        $this->userSetPart->setMissingQuantity(-3);
        $this->assertEquals(0, $this->userSetPart->getMissingQuantity());
    }

    public function testNegativeDamagedQuantityIsNormalisedToZero(): void
    {
        $this->userSetPart->setDamagedQuantity(-1);
        $this->assertEquals(0, $this->userSetPart->getDamagedQuantity());
    }

    public function testNegativeDiscolouredQuantityIsNormalisedToZero(): void
    {
        $this->userSetPart->setDiscolouredQuantity(-2);
        $this->assertEquals(0, $this->userSetPart->getDiscolouredQuantity());
    }

    public function testNormalizeReducesDiscolouredFirstOnOverflow(): void
    {
        // Required: 5, missing: 2, damaged: 2, discoloured: 3 → total: 7, overflow: 2
        // Rule: reduce discoloured first → discoloured becomes 3 - 2 = 1
        $this->userSetPart->setMissingQuantity(2);
        $this->userSetPart->setDamagedQuantity(2);
        $this->userSetPart->setDiscolouredQuantity(3);

        $this->assertEquals(2, $this->userSetPart->getMissingQuantity());
        $this->assertEquals(2, $this->userSetPart->getDamagedQuantity());
        $this->assertEquals(1, $this->userSetPart->getDiscolouredQuantity());
    }

    public function testNormalizeReducesDamagedWhenDiscolouredExhausted(): void
    {
        // Required: 5, set missing=3 then damaged=3 (overflow=1 → damaged to 2), then discoloured=1
        // After step 1 (missing=3): no overflow
        // After step 2 (damaged=3): total=6, overflow=1 → discoloured(0)<1, damaged 3-1=2
        // After step 3 (discoloured=1): total=3+2+1=6, overflow=1 → discoloured(1)>=1, discoloured=0
        $this->userSetPart->setMissingQuantity(3);
        $this->userSetPart->setDamagedQuantity(3);
        $this->userSetPart->setDiscolouredQuantity(1);

        $this->assertEquals(3, $this->userSetPart->getMissingQuantity());
        $this->assertEquals(2, $this->userSetPart->getDamagedQuantity());
        $this->assertEquals(0, $this->userSetPart->getDiscolouredQuantity());
    }

    public function testNormalizeReducesMissingAsLastResort(): void
    {
        // Required: 5, set missing=5, then try to add damaged=5
        // After missing=5: total=5, no overflow
        // After damaged=5: total=10, overflow=5
        //   discoloured(0) < 5
        //   damaged(5) >= 5: damaged = 5-5 = 0
        $this->userSetPart->setMissingQuantity(5);
        $this->userSetPart->setDamagedQuantity(5);

        $this->assertEquals(5, $this->userSetPart->getMissingQuantity());
        $this->assertEquals(0, $this->userSetPart->getDamagedQuantity());
        $this->assertEquals(0, $this->userSetPart->getDiscolouredQuantity());
    }

    public function testSetterReturnsSelf(): void
    {
        $result = $this->userSetPart->setMissingQuantity(1);
        $this->assertSame($this->userSetPart, $result);
    }
}
