<?php declare(strict_types=1);

namespace DaveRandom\Resume;

interface Resource
{
    /**
     * Write the specified data range to output
     *
     * @param OutputWriter $outputWriter
     * @param string|null $unit
     * @param Range|null $range
     */
    function sendData(OutputWriter $outputWriter, Range $range = null, string $unit = null);

    /**
     * Get the total length of the resource
     *
     * @return int
     */
    function getLength(): int;

    /**
     * Get the MIME type of the resource
     *
     * @return string
     */
    function getMimeType(): string;

    /**
     * Get additional headers to be send with the resource
     *
     * @return array
     */
    function getAdditionalHeaders(): array;
}
