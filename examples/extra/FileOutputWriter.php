<?php declare(strict_types=1);

class FileOutputWriter implements \DaveRandom\Resume\OutputWriter
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var bool
     */
    private $headerWritten = false;

    /**
     * @var int
     */
    private $responseCode;

    /**
     * @var string[]
     */
    private $headers = [];

    private function writeHeader()
    {
        $header = "HTTP/1.1 {$this->responseCode} " . self::RESPONSE_MESSAGES[$this->responseCode] . "\r\n"
                . \implode("\r\n", $this->headers) . "\r\n"
                . "\r\n";

        \file_put_contents($this->file, $header);

        $this->headerWritten = true;
    }

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * Set the HTTP response code to send to the client
     *
     * @param int $code
     */
    function setResponseCode(int $code)
    {
        $this->responseCode = $code;
    }

    /**
     * Send a response header to the client
     *
     * @param string $name
     * @param string $value
     */
    function sendHeader(string $name, string $value)
    {
        $this->headers[] = "{$name}: {$value}";
    }

    /**
     * Send a data block to the client
     *
     * @param string $data
     */
    function sendData(string $data)
    {
        if (!$this->headerWritten) {
            $this->writeHeader();
        }

        \file_put_contents($this->file, $data, \FILE_APPEND);
    }
}
