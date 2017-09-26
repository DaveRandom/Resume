<?php declare(strict_types=1);

namespace DaveRandom\Resume\Tests;

use DaveRandom\Resume\DefaultOutputWriter;
use PHPUnit\Framework\TestCase;

class DefaultOutputWriterTest extends TestCase
{
    public function testOutputsData()
    {
        $this->expectOutputString('test');

        (new DefaultOutputWriter)->sendData('test');
    }
}
