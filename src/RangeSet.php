<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class RangeSet
{
    public const DEFAULT_MAX_RANGES = 10;

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
     * Create a new instance from a Range header string
     *
     * @param string|null $header
     * @param int $maxRanges
     * @return self|null
     */
    public static function createFromHeader(?string $header, int $maxRanges = self::DEFAULT_MAX_RANGES): ?self
    {
        static $headerParseExpr = /** @lang regex */ '/
          ^
          \s*                 # tolerate lead white-space
          (?<unit> [^\s=]+ )  # unit is everything up to first = or white-space
          (?: \s*=\s* | \s+ ) # separator is = or white-space
          (?<ranges> .+ )     # remainder is range spec
        /x';

        static $rangeParseExpr = /** @lang regex */ '/
          ^
          (?<start> [0-9]* ) # start is a decimal number
          \s*-\s*            # separator is a dash
          (?<end> [0-9]* )   # end is a decimal number
          $
        /x';

        if ($header === null) {
            return null;
        }

        if (!\preg_match($headerParseExpr, $header, $match)) {
            throw new InvalidRangeHeaderException('Invalid header: Parse failure');
        }

        $unit = $match['unit'];
        $rangeSpec = \explode(',', $match['ranges']);

        if (\count($rangeSpec) > $maxRanges) {
            throw new InvalidRangeHeaderException("Invalid header: Too many ranges");
        }

        $ranges = [];

        foreach (\explode(',', $match['ranges']) as $i => $range) {
            if (!\preg_match($rangeParseExpr, \trim($range), $match)) {
                throw new InvalidRangeHeaderException("Invalid range format at position {$i}: Parse failure");
            }

            if ($match['start'] === '' && $match['end'] === '') {
                throw new InvalidRangeHeaderException("Invalid range format at position {$i}: Start and end empty");
            }

            $ranges[] = $match['start'] === ''
                ? new Range(((int)$match['end']) * -1)
                : new Range((int)$match['start'], $match['end'] !== '' ? (int)$match['end'] : null);
        }

        return new self($unit, $ranges);
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
            $range = $range->normalize($size);

            if ($range->getStart() < $size) {
                $ranges[] = $range;
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
