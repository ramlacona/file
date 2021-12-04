<?php

namespace Amp\File\Driver;

use Amp\File\Driver;
use Amp\File\File;
use Amp\File\Internal\Cache;

final class StatusCachingDriver implements Driver
{
    /** @var Driver */
    private Driver $driver;

    /** @var Cache */
    private Cache $statusCache;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
        $this->statusCache = new Cache(1000, 1024);
    }

    public function openFile(string $path, string $mode): File
    {
        $file = $this->driver->openFile($path, $mode);

        return new StatusCachingFile($file, fn () => $this->statusCache->delete($path));
    }

    public function getStatus(string $path): ?array
    {
        if ($cachedStat = $this->statusCache->get($path)) {
            return $cachedStat;
        }

        $stat = $this->driver->getStatus($path);
        if ($stat) {
            $this->statusCache->set($path, $stat, 1000);
        }

        return $stat;
    }

    public function getLinkStatus(string $path): ?array
    {
        return $this->driver->getLinkStatus($path);
    }

    public function createSymlink(string $target, string $link): void
    {
        try {
            $this->driver->createSymlink($target, $link);
        } finally {
            $this->statusCache->delete($target);
            $this->statusCache->delete($link);
        }
    }

    public function createHardlink(string $target, string $link): void
    {
        try {
            $this->driver->createHardlink($target, $link);
        } finally {
            $this->statusCache->delete($target);
            $this->statusCache->delete($link);
        }
    }

    public function resolveSymlink(string $target): string
    {
        return $this->driver->resolveSymlink($target);
    }

    public function move(string $from, string $to): void
    {
        try {
            $this->driver->move($from, $to);
        } finally {
            $this->statusCache->delete($from);
            $this->statusCache->delete($to);
        }
    }

    public function deleteFile(string $path): void
    {
        try {
            $this->driver->deleteFile($path);
        } finally {
            $this->statusCache->delete($path);
        }
    }

    public function createDirectory(string $path, int $mode = 0777): void
    {
        try {
            $this->driver->createDirectory($path, $mode);
        } finally {
            $this->statusCache->delete($path);
        }
    }

    public function createDirectoryRecursively(string $path, int $mode = 0777): void
    {
        try {
            $this->driver->createDirectoryRecursively($path, $mode);
        } finally {
            $this->statusCache->delete($path);
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->driver->deleteDirectory($path);
        } finally {
            $this->statusCache->delete($path);
        }
    }

    public function listFiles(string $path): array
    {
        return $this->driver->listFiles($path);
    }

    public function changePermissions(string $path, int $mode): void
    {
        try {
            $this->driver->changePermissions($path, $mode);
        } finally {
            $this->statusCache->delete($path);
        }
    }

    public function changeOwner(string $path, ?int $uid, ?int $gid): void
    {
        try {
            $this->driver->changeOwner($path, $uid, $gid);
        } finally {
            $this->statusCache->delete($path);
        }
    }

    public function touch(string $path, ?int $modificationTime, ?int $accessTime): void
    {
        try {
            $this->driver->touch($path, $modificationTime, $accessTime);
        } finally {
            $this->statusCache->delete($path);
        }
    }

    public function read(string $path): string
    {
        return $this->driver->read($path);
    }

    public function write(string $path, string $contents): void
    {
        try {
            $this->driver->write($path, $contents);
        } finally {
            $this->statusCache->delete($path);
        }
    }
}