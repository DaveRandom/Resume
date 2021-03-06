<?php declare(strict_types=1);

namespace DaveRandom\Resume\Tests;

use DaveRandom\Resume\RangeSet;
use PHPUnit\Framework\TestCase;

final class RangeSetTest extends TestCase
{
    public function testCreateFromNullInputReturnsNull()
    {
        $this->assertNull(RangeSet::createFromHeader(null));
    }

    public function testValidSingleRange()
    {
        $set = RangeSet::createFromHeader('bytes=0-23');

        $this->assertInstanceOf(RangeSet::class, $set);

        $this->assertSame('bytes', $set->getUnit());

        $this->assertSame(1, \count($set->getRangesForSize(1000)));
    }

    public function testValidSingleRanges()
    {
        foreach (['bytes=0-23', 'bytes=-23', 'bytes=0-', 'bytes=10-'] as $header) {
            $set = RangeSet::createFromHeader($header);

            $this->assertInstanceOf(RangeSet::class, $set);

            $this->assertSame('bytes', $set->getUnit(), "Header value: {$header}");

            $this->assertSame(1, \count($set->getRangesForSize(1000)), "Header value: {$header}");
        }
    }

    public function testValidSingleRangesVariance()
    {
        foreach (['bytes 0-23', 'bytes = 0-23', 'bytes = 0 - 23', 'bytes = 0 - 23'] as $header) {
            $set = RangeSet::createFromHeader($header);

            $this->assertInstanceOf(RangeSet::class, $set);

            $this->assertSame('bytes', $set->getUnit(), "Header value: {$header}");

            $this->assertSame(1, \count($set->getRangesForSize(1000)));
        }
    }

    public function testOverlappingRanges()
    {
        $set = RangeSet::createFromHeader('bytes=0-23,15-43');

        $this->assertInstanceOf(RangeSet::class, $set);

        $this->assertSame('bytes', $set->getUnit());

        $ranges = $set->getRangesForSize(1000);

        $this->assertSame(1, \count($ranges));

        $this->assertSame(0, $ranges[0]->getStart());

        $this->assertSame(43, $ranges[0]->getEnd());
    }

    public function testNonOverlappingRanges()
    {
        $set = RangeSet::createFromHeader('bytes=0-23,30-43');

        $this->assertInstanceOf(RangeSet::class, $set);

        $this->assertSame('bytes', $set->getUnit());

        $this->assertSame(2, \count($set->getRangesForSize(1000)));
    }

    /**
     * @expectedException \DaveRandom\Resume\InvalidRangeHeaderException
     */
    public function testRangesNumberLimit()
    {
        $set = RangeSet::createFromHeader('bytes=0-1,1-2,2-3,3-4,4-5', 5);

        $this->assertInstanceOf(RangeSet::class, $set);

        RangeSet::createFromHeader('bytes=0-1,1-2,2-3,3-4,4-5', 4);
    }

    /**
     * @expectedException \DaveRandom\Resume\InvalidRangeHeaderException
     */
    public function testInvalidHeaderSyntaxThrows()
    {
        RangeSet::createFromHeader('randomgarbage');
    }

    /**
     * @expectedException \DaveRandom\Resume\InvalidRangeHeaderException
     */
    public function testInvalidRangeSyntaxThrows()
    {
        RangeSet::createFromHeader('bytes=randomgarbage');
    }

    /**
     * @expectedException \DaveRandom\Resume\InvalidRangeHeaderException
     */
    public function testEmptyRangeThrows()
    {
        RangeSet::createFromHeader('bytes=-');
    }

    /**
     * @expectedException \DaveRandom\Resume\UnsatisfiableRangeException
     */
    public function testNoMatchingRangeThrows()
    {
        RangeSet::createFromHeader('bytes=10-100')->getRangesForSize(5);
    }
}
