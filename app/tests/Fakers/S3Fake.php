<?php

namespace Tests\Fakers;

use App\Interfaces\S3Interface;

final class S3Fake implements S3Interface
{
    public array $files = [];
    public array $uploaded = [];
    public array $deleted = [];

    public function setFiles(array $files): void
    {
        $this->files = $files;
    }

    public function listCsvFiles(string $bucket): array
    {
        return $this->files;
    }

    public function getObject(string $bucket, string $key): \Psr\Http\Message\StreamInterface
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "header\nrow\n");
        rewind($stream);

        return new class($stream) implements \Psr\Http\Message\StreamInterface {
            public function __construct(private $stream) {}

            public function __toString(): string { return stream_get_contents($this->stream); }
            public function close(): void { fclose($this->stream); }
            public function detach() { return $this->stream; }
            public function getSize(): ?int { return null; }
            public function tell(): int { return 0; }
            public function eof(): bool { return feof($this->stream); }
            public function isSeekable(): bool { return true; }
            public function seek($offset, $whence = SEEK_SET): void {}
            public function rewind(): void { rewind($this->stream); }
            public function isWritable(): bool { return false; }
            public function write($string): int { return 0; }
            public function isReadable(): bool { return true; }
            public function read($length): string { return fread($this->stream, $length); }
            public function getContents(): string { return stream_get_contents($this->stream); }
            public function getMetadata($key = null): mixed { return null; }
        };
    }

    public function putObject(string $bucket, string $key, string $body): void
    {
        $this->uploaded[] = compact('bucket', 'key', 'body');
    }

    public function deleteObject(string $bucket, string $key): void
    {
        $this->deleted[] = compact('bucket', 'key');
    }
}