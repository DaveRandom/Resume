<?php declare(strict_types=1);

namespace DaveRandom\Resume;

interface OutputWriter
{
    const RESPONSE_MESSAGES = [
        200 => 'OK',
        206 => 'Partial Content',
    ];

    /**
     * Set the HTTP response code to send to the client
     *
     * @param int $code
     */
    function setResponseCode(int $code);

    /**
     * Send a response header to the client
     *
     * @param string $name
     * @param string $value
     */
    function sendHeader(string $name, string $value);

    /**
     * Send a data block to the client
     *
     * @param string $data
     */
    function sendData(string $data);
}
