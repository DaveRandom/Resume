<?php declare(strict_types=1);

namespace DaveRandom\Resume;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/extra/FileOutputWriter.php';

$path = __DIR__ . '/fixtures/10KB_data.txt';
$contentType = 'text/plain';

try {
    $rangeHeader = 'bytes=100-199';
    $rangeSet = RangeSet::createFromHeader($rangeHeader);

    /** @var Resource $resource */
    $resource = new FileResource($path, $contentType);
    $writer = new \FileOutputWriter(__DIR__ . '/example02.out.txt');
    $servlet = new ResourceServlet($resource);

    $servlet->sendResource($rangeSet, $writer);
} catch (\Throwable $e) {
    echo $e;
}
