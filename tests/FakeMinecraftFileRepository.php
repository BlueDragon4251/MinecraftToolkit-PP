<?php

declare(strict_types=1);

namespace BlueWolf\MinecraftToolkit\Tests;

final class FakeMinecraftFileRepository
{
    /** @var array<string, string> */
    private array $files = [];

    public function put(string $path, string $contents): void
    {
        $this->files[$this->path($path)] = $contents;
    }

    public function get(string $path): string
    {
        return $this->files[$this->path($path)] ?? throw new \RuntimeException('File not found.');
    }

    public function exists(string $path): bool
    {
        return array_key_exists($this->path($path), $this->files);
    }

    public function move(string $from, string $to): void
    {
        $this->put($to, $this->get($from));
        unset($this->files[$this->path($from)]);
    }

    public function delete(string $path): void
    {
        unset($this->files[$this->path($path)]);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->files;
    }

    private function path(string $path): string
    {
        $path = '/'.ltrim(str_replace('\\', '/', $path), '/');
        if (str_contains($path, '../')) {
            throw new \InvalidArgumentException('Unsafe path.');
        }

return $path;
    }
}
