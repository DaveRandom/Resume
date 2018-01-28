<?php declare(strict_types=1);

namespace DaveRandom\Resume;

final class FileResource implements Resource
{
    /** @internal */
    const DEFAULT_CHUNK_SIZE = 8192;

    /**
     * Full canonical path of file on local file system
     *
     * @var string
     */
    private $localPath;

    /**
     * MIME type of file contents
     *
     * @var string
     */
    private $mimeType;

    /**
     * Size of local file, in bytes
     *
     * @var int
     */
    private $fileSize;

    /**
     * Stream handle for reading the file
     *
     * @var resource|null
     */
    private $handle = null;

    /**
     * Chunk size for local file system reads when sending a partial file
     *
     * @var int
     */
    private $chunkSize = self::DEFAULT_CHUNK_SIZE;

    /**
     * Open the local file handle if it's not open yet, and set the pointer to the supplied position
     *
     * @param int $position
     */
    private function openFile(int $position)
    {
        if ($this->handle === null && !$this->handle = \fopen($this->localPath, 'r')) {
            throw new SendFileFailureException("Failed to open '{$this->localPath}' for reading");
        }

        if (\fseek($this->handle, $position, \SEEK_SET) !== 0) {
            throw new SendFileFailureException('fseek() operation failed');
        }
    }

    /**
     * Send a chunk of data to the client
     *
     * @param OutputWriter $outputWriter
     * @param int $length
     * @return int
     */
    private function sendDataChunk(OutputWriter $outputWriter, int $length): int
    {
        $read = $length > $this->chunkSize
            ? $this->chunkSize
            : $length;

        $data = \fread($this->handle, $read);

        if ($data === false) {
            throw new SendFileFailureException('fread() operation failed');
        }

        $outputWriter->sendData($data);

        return \strlen($data);
    }

    /**
     * @param string $path Path of file on local file system
     * @param string $mimeType MIME type of file contents
     * @param int $chunkSize Chunk size for local file system reads when sending a partial file
     */
    public function __construct(string $path, string $mimeType = null, int $chunkSize = self::DEFAULT_CHUNK_SIZE)
    {
        $this->chunkSize = $chunkSize;
        $this->mimeType = $mimeType ?? 'application/octet-stream';

        // Make sure the file exists and is a file, otherwise we are wasting our time
        $this->localPath = \realpath($path);

        if ($this->localPath === false || !\is_file($this->localPath)) {
            throw new NonExistentFileException("Local path '{$path}' does not exist or is not a file");
        }

        // This shouldn't ever fail but just in case
        if (false === $this->fileSize = \filesize($this->localPath)) {
            throw new UnreadableFileException("Failed to retrieve size of file '{$this->localPath}'");
        }
    }

    /**
     * Explicitly close the file handle if it's open
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            \fclose($this->handle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendData(OutputWriter $outputWriter, Range $range = null, string $unit = null)
    {
        if (\strtolower($unit ?? 'bytes') !== 'bytes') {
            throw new UnsatisfiableRangeException('Unit not handled by this resource: ' . $unit);
        }

        $start = 0;
        $length = $this->fileSize;

        if ($range !== null) {
            $start = $range->getStart();
            $length = $range->getLength();
        }

        $this->openFile($start);

        while ($length > 0) {
            $length -= $this->sendDataChunk($outputWriter, $length);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        return $this->fileSize;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalHeaders(): array
    {
        return [
            'Content-Disposition' => 'attachment; filename="' . \basename($this->localPath) . '"'
        ];
    }

    /**
     * Get the chunk size for local file system reads when sending a partial file
     *
     * @return int
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * Set the chunk size for local file system reads when sending a partial file
     *
     * @param int $chunkSize
     */
    public function setChunkSize(int $chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }
}
