<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class Range
{
    private $start;
    private $end;
    private $normal;

    public function __construct(int $start, int $end = null)
    {
        $this->start = $start;
        $this->end = $end;

        if ($end < 0) {
            throw new InvalidRangeException('End cannot be negative');
        }

        $haveEnd = $end !== null;

        if ($haveEnd && $start > $end) {
            throw new InvalidRangeException('Start cannot be larger than end');
        }

        if ($haveEnd && $start < 0) {
            throw new InvalidRangeException('A range with a negative start cannot specify an end');
        }

        $this->normal = $start >= 0 && $haveEnd;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return int|null
     */
    public function getEnd()
    {
        return $this->end;
    }

    public function getLength(): int
    {
        if (!$this->normal) {
            throw new LengthNotAvailableException('Cannot retrieve length of a range that is not normalized');
        }

        return ($this->end - $this->start) + 1;
    }

    public function normalize(int $size): Range
    {
        if ($this->normal) {
            if ($this->start > $size) {
                throw new UnsatisfiableRangeException('Not satisfiable by a resource of the specified size');
            }

            return $this;
        }

        $end = $this->end ?? $size - 1;
        $start = $this->start < 0
            ? $end + $this->start + 1
            : $this->start;

        if ($start > $size) {
            throw new UnsatisfiableRangeException('Not satisfiable by a resource of the specified size');
        }

        return new self(\max($start, 0), \min($end, $size - 1));
    }

    public function overlaps(Range $other): bool
    {
        if (!$this->normal || !$other->normal) {
            throw new IncompatibleRangesException('Cannot test for overlap of ranges that have not been normalized');
        }

        // https://stackoverflow.com/a/3269471/889949
        return $this->start <= $other->end && $other->start <= $this->end;
    }

    public function combine(Range $other): self
    {
        if (!$this->normal || !$other->normal) {
            throw new IncompatibleRangesException('Cannot combine ranges that have not been normalized');
        }

        if (!($this->start <= $other->end && $other->start <= $this->end)) {
            throw new IncompatibleRangesException('Cannot combine non-overlapping ranges');
        }

        return new self(\min($this->start, $other->start), \max($this->end, $other->end));
    }

    public function __toString(): string
    {
        $suffix = $this->end !== null || $this->start >= 0
            ? '-' . $this->end
            : '';

        return $this->start . $suffix;
    }
}
