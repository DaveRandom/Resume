<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class ResourceServlet
{
    /**
     * @var \DaveRandom\Resume\Resource
     */
    private $resource;

    /**
     * Generate the default response headers for this resource
     *
     * @return HeaderSet
     */
    private function generateDefaultHeaders(): HeaderSet
    {
        $ranges = $this->resource instanceof RangeUnitProvider
            ? \implode(',', $this->resource->getRangeUnits())
            : 'bytes';

        if ($ranges === '') {
            $ranges = 'none';
        }

        return new HeaderSet([
            'Content-Type' => $this->resource->getMimeType(),
            'Accept-Ranges' => $ranges,
        ]);
    }

    /**
     * Send the headers that are included regardless of whether a range was requested
     *
     * @param OutputWriter $outputWriter
     * @param HeaderSet $headers
     */
    private function sendHeaders(OutputWriter $outputWriter, HeaderSet $headers)
    {
        foreach ($this->resource->getAdditionalHeaders() as $name => $value) {
            $headers->setHeader($name, $value);
        }

        foreach ($headers as $name => $value) {
            $outputWriter->sendHeader(\trim($name), \trim($value));
        }
    }

    /**
     * Create a Content-Range header corresponding to the specified unit and ranges
     *
     * @param string $unit
     * @param Range[] $ranges
     * @param int $size
     * @return string
     */
    private function getContentRangeHeader(string $unit, array $ranges, int $size): string
    {
        return $unit . ' ' . \implode(',', $ranges) . '/' . $size;
    }

    /**
     * Send the complete resource to the client
     *
     * @param OutputWriter $outputWriter
     * @param HeaderSet $headers
     */
    private function sendCompleteResource(OutputWriter $outputWriter, HeaderSet $headers)
    {
        $outputWriter->setResponseCode(200);

        $this->sendHeaders($outputWriter, $headers);

        $headers->setHeader('Content-Length', (string)$this->resource->getLength());

        $this->resource->sendData($outputWriter);
    }

    /**
     * Send the requested ranges to the client
     *
     * @param OutputWriter $outputWriter
     * @param HeaderSet $headers
     * @param RangeSet $rangeSet
     */
    private function sendResourceRanges(OutputWriter $outputWriter, HeaderSet $headers, RangeSet $rangeSet)
    {
        $totalResourceSize = $this->resource->getLength();
        $ranges = $rangeSet->getRangesForSize($totalResourceSize);

        $responseBodySize = \array_reduce($ranges, function(int $size, Range $range) {
            return $size + $range->getLength();
        }, 0);

        $outputWriter->setResponseCode(206);
        $this->sendHeaders($outputWriter, $headers);

        $contentRangeHeader = $this->getContentRangeHeader($rangeSet->getUnit(), $ranges, $totalResourceSize);

        $outputWriter->sendHeader('Content-Range', $contentRangeHeader);
        $outputWriter->sendHeader('Content-Length', (string)$responseBodySize);

        foreach ($ranges as $range) {
            $this->resource->sendData($outputWriter, $range);
        }
    }

    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Send data from a file based on the Range header described by the supplied RangeSet
     *
     * @param RangeSet|null $rangeSet Range header on which the transmission will be based
     * @param OutputWriter|null $outputWriter Output writer via which resource will be sent
     */
    public function sendResource(RangeSet $rangeSet = null, OutputWriter $outputWriter = null)
    {
        $outputWriter = $outputWriter ?? new DefaultOutputWriter();
        $headers = $this->generateDefaultHeaders();

        if ($rangeSet === null) {
            $this->sendCompleteResource($outputWriter, $headers);
        } else {
            $this->sendResourceRanges($outputWriter, $headers, $rangeSet);
        }
    }
}
