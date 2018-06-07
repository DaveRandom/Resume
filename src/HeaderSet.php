<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class HeaderSet implements \IteratorAggregate
{
    private $keyMap;
    private $values;

    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->values);
    }

    public function setHeader(string $name, string $value)
    {
        $key = \strtolower($name);
        $name = $this->keyMap[$key] ?? $name;

        $this->values[$name] = $value;
        $this->keyMap[$key] = $name;
    }

    public function getHeader(string $name)
    {
        return $this->values[$this->keyMap[\strtolower($name)]] ?? null;
    }

    public function containsHeader(string $name): bool
    {
        return isset($this->values[$this->keyMap[\strtolower($name)]]);
    }

    public function removeHeader(string $name)
    {
        $key = \strtolower($name);
        $name = $this->keyMap[$key] ?? $name;

        unset($this->values[$name], $this->keyMap[$key]);
    }
}
