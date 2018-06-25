<?php declare(strict_types=1);

namespace DaveRandom\Resume\Tests;

use DaveRandom\Resume\FileResource;
use PHPUnit\Framework\TestCase;

final class FileResourceTest extends TestCase
{
    public function testFileSizeAsLength()
    {
        $path = __DIR__ . '/fixtures/10KB_data.txt';

        $file = new FileResource($path);

        $this->assertSame(\filesize($path), $file->getLength());
    }

    /**
     * @expectedException \DaveRandom\Resume\NonExistentFileException
     */
    public function testNonExistentPathFails()
    {
        new FileResource(__DIR__ . '/does_not_exist');
    }

    /**
     * @expectedException \DaveRandom\Resume\NonExistentFileException
     */
    public function testDirectoryFails()
    {
        new FileResource(__DIR__);
    }
}
