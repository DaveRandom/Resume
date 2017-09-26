<?php declare(strict_types=1);

namespace DaveRandom\Resume;

interface RangeUnitProvider extends Resource
{
    /**
     * Get a list of the range units supported by this resource
     *
     * @return string[]
     */
    function getRangeUnits(): array;
}
