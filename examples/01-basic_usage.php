<?php declare(strict_types=1);

namespace DaveRandom\Resume;

require __DIR__ . '/../vendor/autoload.php';

$path = '/local/path/to/file.ext';
$contentType = 'application/octet-stream';

// Avoid sending unexpected errors to the client - we should be serving a file,
// we don't want to corrupt the data we send
\ini_set('display_errors', '0');

try {
    // Note that this construct will still work if the client did not specify a Range: header
    $rangeHeader = get_request_header('Range');
    $rangeSet = RangeSet::createFromHeader($rangeHeader);

    /** @var Resource $resource */
    $resource = new FileResource($path, $contentType);
    $servlet = new ResourceServlet($resource);

    $servlet->sendResource($rangeSet);
} catch (InvalidRangeHeaderException $e) {
    \header("HTTP/1.1 400 Bad Request");
} catch (UnsatisfiableRangeException $e) {
    \header("HTTP/1.1 416 Range Not Satisfiable");
} catch (NonExistentFileException $e) {
    \header("HTTP/1.1 404 Not Found");
} catch (UnreadableFileException $e) {
    \header("HTTP/1.1 500 Internal Server Error");
} catch (SendFileFailureException $e) {
    if (!\headers_sent()) {
        \header("HTTP/1.1 500 Internal Server Error");
    }

    echo "An error occurred while attempting to send the requested resource: {$e->getMessage()}";
}

// It's usually a good idea to explicitly exit after sending a file to avoid sending any
// extra data on the end that might corrupt the file
exit;
