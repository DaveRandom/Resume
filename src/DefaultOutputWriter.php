<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class DefaultOutputWriter implements OutputWriter
{
    /**
     * {@inheritdoc}
     */
    function setResponseCode(int $code): void
    {
        \header("HTTP/1.1 {$code} " . self::RESPONSE_MESSAGES[$code]);
    }

    /**
     * {@inheritdoc}
     */
    public function sendHeader(string $name, string $value): void
    {
        \header("{$name}: {$value}");
    }

    /**
     * {@inheritdoc}
     */
    public function sendData(string $data): void
    {
        echo $data;
    }
}
