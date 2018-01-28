<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class DefaultOutputWriter implements OutputWriter
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setResponseCode(int $code)
    {
        \header("HTTP/1.1 {$code} " . self::RESPONSE_MESSAGES[$code]);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function sendHeader(string $name, string $value)
    {
        \header("{$name}: {$value}");
    }

    /**
     * {@inheritdoc}
     */
    public function sendData(string $data)
    {
        echo $data;
    }
}
