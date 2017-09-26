<?php declare(strict_types=1);

namespace DaveRandom\Resume\Tests;

use DaveRandom\Resume\IncompatibleRangesException;
use DaveRandom\Resume\InvalidRangeException;
use DaveRandom\Resume\LengthNotAvailableException;
use DaveRandom\Resume\Range;
use DaveRandom\Resume\UnsatisfiableRangeException;
use PHPUnit\Framework\TestCase;

final class RangeTest extends TestCase
{
    public function testRangeFromBeginningToEnd()
    {
        $range = new Range(0);

        $this->assertSame(0, $range->getStart());

        $this->assertNull($range->getEnd());

        $this->expectException(LengthNotAvailableException::class);
        $range->getLength();
    }

    public function testRangeFromBeginningToOffset()
    {
        $range = new Range(0, 99);

        $this->assertSame(0, $range->getStart());

        $this->assertSame(99, $range->getEnd());

        $this->assertSame(100, $range->getLength());
    }

    public function testRangeFromOffsetToEnd()
    {
        $range = new Range(10);

        $this->assertSame(10, $range->getStart());

        $this->assertNull($range->getEnd());

        $this->expectException(LengthNotAvailableException::class);
        $range->getLength();
    }

    public function testRangeFromOffsetToOffset()
    {
        $range = new Range(10, 99);

        $this->assertSame(10, $range->getStart());

        $this->assertSame(99, $range->getEnd());

        $this->assertSame(90, $range->getLength());
    }

    public function testRangeFromNegativeStart()
    {
        $range = new Range(-10);

        $this->assertSame(-10, $range->getStart());

        $this->assertNull($range->getEnd());

        $this->expectException(LengthNotAvailableException::class);
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

    public function testNormalizingNormalizedRangeAfterEndOfSizeFails()
    {
        $this->expectException(UnsatisfiableRangeException::class);

        (new Range(10, 100))->normalize(5);
    }

    public function testNormalizingNonNormalizedRangeAfterEndOfSizeFails()
    {
        $this->expectException(UnsatisfiableRangeException::class);

        (new Range(10))->normalize(5);
    }

    public function testNegativeEndInvalid()
    {
        $this->expectException(InvalidRangeException::class);

        new Range(0, -1);
    }

    public function testEndSmallerThanStartInvalid()
    {
        $this->expectException(InvalidRangeException::class);

        new Range(10, 9);
    }

    public function testEndWithNegativeStartInvalid()
    {
        $this->expectException(InvalidRangeException::class);

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

    public function testNonNormalizedIncomparableWithNormalized()
    {
        $normal = new Range(1, 10);
        $notNormal = new Range(1);

        $this->expectException(IncompatibleRangesException::class);

        $normal->overlaps($notNormal);
    }

    public function testNormalizedIncomparableWithNonNormalized()
    {
        $normal = new Range(1, 10);
        $notNormal = new Range(1);

        $this->expectException(IncompatibleRangesException::class);

        $notNormal->overlaps($normal);
    }

    public function testNonNormalizedIncomparableWithNonNormalized()
    {
        $notNormal = new Range(1);

        $this->expectException(IncompatibleRangesException::class);

        $notNormal->overlaps($notNormal);
    }

    public function testOverlappingRanges()
    {
        $this->assertTrue((new Range(1, 10))->overlaps(new Range(5, 15)));
    }

    public function testNonOverlappingRanges()
    {
        $this->assertFalse((new Range(1, 10))->overlaps(new Range(15, 25)));
    }

    public function testNonNormalizedCombineWithNormalizedFails()
    {
        $normal = new Range(1, 10);
        $notNormal = new Range(1);

        $this->expectException(IncompatibleRangesException::class);

        $normal->combine($notNormal);
    }

    public function testNormalizedCombineWithNonNormalizedFails()
    {
        $normal = new Range(1, 10);
        $notNormal = new Range(1);

        $this->expectException(IncompatibleRangesException::class);

        $notNormal->combine($normal);
    }

    public function testNonNormalizedCombineWithNonNormalizedFails()
    {
        $notNormal = new Range(1);

        $this->expectException(IncompatibleRangesException::class);

        $notNormal->combine($notNormal);
    }

    public function testCombiningOverlappingRanges()
    {
        $range = (new Range(1, 10))->combine(new Range(5, 15));

        $this->assertSame(1, $range->getStart());

        $this->assertSame(15, $range->getEnd());

        $this->assertSame(15, $range->getLength());
    }

    public function testCombiningNonOverlappingRanges()
    {
        $this->expectException(IncompatibleRangesException::class);

        (new Range(1, 10))->combine(new Range(15, 25));
    }
}
