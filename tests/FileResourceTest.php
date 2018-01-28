<?php declare(strict_types=1);

namespace DaveRandom\Resume\Tests;

use DaveRandom\Resume\FileResource;
use DaveRandom\Resume\NonExistentFileException;
use PHPUnit\Framework\TestCase;

final class FileResourceTest extends TestCase
{
    public function testFileSizeAsLength()
    {
        $path = __DIR__ . '/../fixtures/10KB_data.txt';

        $file = new FileResource($path);

        $this->assertSame(\filesize($path), $file->getLength());
    }

    public function testNonExistentPathFails()
    {
        $path = __DIR__ . '/does_not_exist';

        $this->expectException(NonExistentFileException::class);
        new FileResource($path);
    }

    public function testDirectoryFails()
    {
        $path = __DIR__;

        $this->expectException(NonExistentFileException::class);
        new FileResource($path);
    }
}
