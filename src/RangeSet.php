<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class RangeSet
{
    public const DEFAULT_MAX_RANGES = 10;

    private const HEADER_PARSE_EXPR = /** @lang regex */ '/
      ^
      \s*                 # tolerate lead white-space
      (?<unit> [^\s=]+ )  # unit is everything up to first = or white-space
      (?: \s*=\s* | \s+ ) # separator is = or white-space
      (?<ranges> .+ )     # remainder is range spec
    /x';

    private const RANGE_PARSE_EXPR = /** @lang regex */ '/
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
     * Create a new instance from a Range header string
     *
     * @param string|null $header
     * @param int $maxRanges
     * @return self|null
     */
    public static function createFromHeader(?string $header, int $maxRanges = self::DEFAULT_MAX_RANGES): ?self
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
     * Get a set of normalized ranges applied to a resource size
     *
     * @param int $size
     * @return Range[]
     */
    public function getRangesForSize(int $size): array
    {
        /** @var Range[] $ranges */
        $ranges = [];

        foreach ($this->ranges as $range) {
            try {
                $range = $range->normalize($size);

                if ($range->getStart() < $size) {
                    $ranges[] = $range;
                }
            } catch (UnsatisfiableRangeException $e) {
                // ignore, other ranges in the set may be satisfiable
            }
        }

        if (empty($ranges)) {
            throw new UnsatisfiableRangeException('No specified ranges are satisfiable by a resource of the specified size');
        }

        $previousCount = null;
        $count = \count($ranges);

        while ($count > 1 && $count !== $previousCount) {
            \usort($ranges, static function(Range $a, Range $b) {
                return $a->getStart() <=> $b->getStart();
            });

            $previousCount = $count;

            for ($i = 0; $i < $count - 1; $i++) {
                if ($ranges[$i]->overlaps($ranges[$i + 1])) {
                    $ranges[$i] = $ranges[$i]->combine($ranges[$i + 1]);
                    unset($ranges[$i + 1]);
                    break;
                }
            }

            $count = \count($ranges);
        }

        return $ranges;
    }
}
