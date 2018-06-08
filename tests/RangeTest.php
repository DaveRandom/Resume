<?php declare(strict_types=1);

namespace DaveRandom\Resume\Tests;

use DaveRandom\Resume\Range;
use PHPUnit\Framework\TestCase;

final class RangeTest extends TestCase
{
    /**
     * @expectedException \DaveRandom\Resume\LengthNotAvailableException
     */
    public function testGetLengthOfRangeFromBeginningToEndFails()
    {
        $range = new Range(0);

        $this->assertSame(0, $range->getStart());

        $this->assertNull($range->getEnd());

        $range->getLength();
    }

    public function testRangeFromBeginningToOffset()
    {
        $range = new Range(0, 99);

        $this->assertSame(0, $range->getStart());

        $this->assertSame(99, $range->getEnd());

        $this->assertSame(100, $range->getLength());
    }

    /**
     * @expectedException \DaveRandom\Resume\LengthNotAvailableException
     */
    public function testGetLengthOfRangeFromOffsetToEndFails()
    {
        $range = new Range(10);

        $this->assertSame(10, $range->getStart());

        $this->assertNull($range->getEnd());

        $range->getLength();
    }

    public function testRangeFromOffsetToOffset()
    {
        $range = new Range(10, 99);

        $this->assertSame(10, $range->getStart());

        $this->assertSame(99, $range->getEnd());

        $this->assertSame(90, $range->getLength());
    }

    /**
     * @expectedException \DaveRandom\Resume\LengthNotAvailableException
     */
    public function testGetLengthOfRangeWithNegativeStartFails()
    {
        $range = new Range(-10);

        $this->assertSame(-10, $range->getStart());

        $this->assertNull($range->getEnd());

        $range->getLength();
    }

    public function testRangeFromBeginningToEndNormalized()
    {
        $range = (new Range(0))->normalize(100);

        $this->assertSame(0, $range->getStart());

        $this->assertSame(99, $range->getEnd());

        $this->assertSame(100, $range->getLength());
    }

    public function testRangeFromOffsetToEndNormalized()
    {
        $range = (new Range(10))->normalize(100);

        $this->assertSame(10, $range->getStart());

        $this->assertSame(99, $range->getEnd());

        $this->assertSame(90, $range->getLength());
    }

    public function testRangeFromNegativeStartNormalized()
    {
        $range = (new Range(-10))->normalize(100);

        $this->assertSame(90, $range->getStart());

        $this->assertSame(99, $range->getEnd());

        $this->assertSame(10, $range->getLength());
    }

    public function testNormalizingNormalizedRangeReturnsSameInstance()
    {
        $range = new Range(0, 99);

        $this->assertSame($range, $range->normalize(100));
    }

    public function testNormalizingNonNormalizedRangeReturnsDifferentInstance()
    {
        $range = new Range(0);

        $this->assertNotSame($range, $range->normalize(100));
    }

    /**
     * @expectedException \DaveRandom\Resume\UnsatisfiableRangeException
     */
    public function testNormalizingNormalizedRangeAfterEndOfSizeFails()
    {
        (new Range(10, 100))->normalize(5);
    }

    /**
     * @expectedException \DaveRandom\Resume\UnsatisfiableRangeException
     */
    public function testNormalizingNonNormalizedRangeAfterEndOfSizeFails()
    {
        (new Range(10))->normalize(5);
    }

    /**
     * @expectedException \DaveRandom\Resume\InvalidRangeException
     */
    public function testNegativeEndFails()
    {
        new Range(0, -1);
    }

    /**
     * @expectedException \DaveRandom\Resume\InvalidRangeException
     */
    public function testEndSmallerThanStartFails()
    {
        new Range(10, 9);
    }

    /**
     * @expectedException \DaveRandom\Resume\InvalidRangeException
     */
    public function testEndWithNegativeStartFails()
    {
        new Range(-1, 10);
    }

    public function testToStringNormal()
    {
        $this->assertSame('1-10', (string)new Range(1, 10));
    }

    public function testToStringNonNormalPositive()
    {
        $this->assertSame('1-', (string)new Range(1));
    }

    public function testToStringNonNormalNegative()
    {
        $this->assertSame('-1', (string)new Range(-1));
    }

    /**
     * @expectedException \DaveRandom\Resume\IncompatibleRangesException
     */
    public function testCompareNonNormalizedWithNormalizedFails()
    {
        (new Range(1, 10))->overlaps(new Range(1));
    }

    /**
     * @expectedException \DaveRandom\Resume\IncompatibleRangesException
     */
    public function testCompareNormalizedWithNonNormalizedFails()
    {
        (new Range(1, 10))->overlaps(new Range(1));
    }

    /**
     * @expectedException \DaveRandom\Resume\IncompatibleRangesException
     */
    public function testCompareNonNormalizedWithNonNormalizedFails()
    {
        (new Range(1))->overlaps(new Range(2));
    }

    public function testOverlappingRanges()
    {
        $this->assertTrue((new Range(1, 10))->overlaps(new Range(5, 15)));
    }

    public function testNonOverlappingRanges()
    {
        $this->assertFalse((new Range(1, 10))->overlaps(new Range(15, 25)));
    }

    /**
     * @expectedException \DaveRandom\Resume\IncompatibleRangesException
     */
    public function testCombineNonNormalizedWithNormalizedFails()
    {
        (new Range(1, 10))->combine(new Range(1));
    }

    /**
     * @expectedException \DaveRandom\Resume\IncompatibleRangesException
     */
    public function testCombineNormalizedWithNonNormalizedFails()
    {
        (new Range(1))->combine(new Range(1, 10));
    }

    /**
     * @expectedException \DaveRandom\Resume\IncompatibleRangesException
     */
    public function testCombineNonNormalizedWithNonNormalizedFails()
    {
        (new Range(1))->combine(new Range(2));
    }

    public function testCombiningOverlappingRanges()
    {
        $range = (new Range(1, 10))->combine(new Range(5, 15));

        $this->assertSame(1, $range->getStart());

        $this->assertSame(15, $range->getEnd());

        $this->assertSame(15, $range->getLength());
    }

    /**
     * @expectedException \DaveRandom\Resume\IncompatibleRangesException
     */
    public function testCombiningNonOverlappingRangesFails()
    {
        (new Range(1, 10))->combine(new Range(15, 25));
    }
}
