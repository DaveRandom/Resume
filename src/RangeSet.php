<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class RangeSet
{
    const DEFAULT_MAX_RANGES = 10;

    /** @internal */
    const HEADER_PARSE_EXPR = /** @lang regex */ '/
      ^
      \s*                 # tolerate lead white-space
      (?<unit> [^\s=]+ )  # unit is everything up to first = or white-space
      (?: \s*=\s* | \s+ ) # separator is = or white-space
      (?<ranges> .+ )     # remainder is range spec
    /x';

    /** @internal */
    const RANGE_PARSE_EXPR = /** @lang regex */ '/
      ^
      (?<start> [0-9]* ) # start is a decimal number
      \s*-\s*            # separator is a dash
      (?<end> [0-9]* )   # end is a decimal number
      $
    /x';

    /**
     * The unit for ranges in the set
     *
     * @var string
     */
    private $unit;

    /**
     * The ranges in the set
     *
     * @var Range[]
     */
    private $ranges = [];

    /**
     * Parse an array of range specifiers into an array of Range objects
     *
     * @param string[] $ranges
     * @return Range[]
     */
    private static function parseRanges(array $ranges): array
    {
        $result = [];

        foreach ($ranges as $i => $range) {
            if (!\preg_match(self::RANGE_PARSE_EXPR, \trim($range), $match)) {
                throw new InvalidRangeHeaderException("Invalid range format at position {$i}: Parse failure");
            }

            if ($match['start'] === '' && $match['end'] === '') {
                throw new InvalidRangeHeaderException("Invalid range format at position {$i}: Start and end empty");
            }

            $result[] = $match['start'] === ''
                ? new Range(((int)$match['end']) * -1)
                : new Range((int)$match['start'], $match['end'] !== '' ? (int)$match['end'] : null);
        }

        return $result;
    }

    /**
     * Get a set of normalized ranges applied to a resource size
     *
     * @param int $size
     * @return Range[]
     */
    private function normalizeRangesForSize(int $size): array
    {
        $result = [];

        foreach ($this->ranges as $range) {
            try {
                $range = $range->normalize($size);

                if ($range->getStart() < $size) {
                    $result[] = $range;
                }
            } catch (UnsatisfiableRangeException $e) {
                // ignore, other ranges in the set may be satisfiable
            }
        }

        if (empty($result)) {
            throw new UnsatisfiableRangeException('No specified ranges are satisfiable by a resource of the specified size');
        }

        return $result;
    }

    /**
     * Combine overlapping ranges in the supplied array and return the result
     *
     * @param Range[] $ranges
     * @return Range[]
     */
    private function combineOverlappingRanges(array $ranges)
    {
        \usort($ranges, static function(Range $a, Range $b) {
            return $a->getStart() <=> $b->getStart();
        });

        for ($i = 0, $l = \count($ranges) - 1; $i < $l; $i++) {
            if (!$ranges[$i]->overlaps($ranges[$i + 1])) {
                continue;
            }

            $ranges[$i] = $ranges[$i]->combine($ranges[$i + 1]);
            unset($ranges[$i + 1]);

            $i++;
        }

        return $ranges;
    }

    /**
     * Create a new instance from a Range header string
     *
     * @param string|null $header
     * @param int $maxRanges
     * @return self|null
     */
    public static function createFromHeader(string $header = null, int $maxRanges = self::DEFAULT_MAX_RANGES)
    {
        if ($header === null) {
            return null;
        }

        if (!\preg_match(self::HEADER_PARSE_EXPR, $header, $match)) {
            throw new InvalidRangeHeaderException('Invalid header: Parse failure');
        }

        $unit = $match['unit'];
        $ranges = \explode(',', $match['ranges']);

        if (\count($ranges) > $maxRanges) {
            throw new InvalidRangeHeaderException("Invalid header: Too many ranges");
        }

        return new self($unit, self::parseRanges($ranges));
    }

    /**
     * @param string $unit
     * @param Range[] $ranges
     */
    public function __construct(string $unit, array $ranges)
    {
        $this->unit = $unit;
        $this->ranges = $ranges;
    }

    /**
     * Get the unit for ranges in the set
     *
     * @return string
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * Get a set of normalized ranges applied to a resource size, reduced to the minimum set of ranges
     *
     * @param int $size
     * @return Range[]
     */
    public function getRangesForSize(int $size): array
    {
        $ranges = $this->normalizeRangesForSize($size);

        $previousCount = null;
        $count = \count($ranges);

        while ($count > 1 && $count !== $previousCount) {
            $previousCount = $count;

            $ranges = $this->combineOverlappingRanges($ranges);

            $count = \count($ranges);
        }

        return $ranges;
    }
}
