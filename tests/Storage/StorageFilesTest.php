<?php

namespace PE\Component\Cronos\Schedule\Tests\Storage;

use PE\Component\Cronos\Schedule\Storage\StorageFiles;
use PHPUnit\Framework\TestCase;

class StorageFilesTest extends TestCase
{
    public function testGetExecutableTasksFromFile1(): void
    {
        $storage = new StorageFiles([__DIR__ . '/../Fixtures/instant.conf']);

        static::assertCount(2, $tasks = $storage->getExecutableTasks());

        static::assertEquals('instant-command1', $tasks[0]->getName());
        static::assertEquals('instant-command2', $tasks[1]->getName());

        static::assertEquals('* * * * * instant-command1', $tasks[0]->getID());
        static::assertEquals('* * * * * instant-command2', $tasks[1]->getID());
    }

    public function testGetExecutableTasksFromFile2(): void
    {
        $storage = new StorageFiles([__DIR__ . '/../Fixtures/ignored.conf']);

        static::assertCount(1, $tasks = $storage->getExecutableTasks());

        static::assertEquals('instant-command3', $tasks[0]->getName());

        static::assertEquals('* * * * * instant-command3', $tasks[0]->getID());
    }

    public function testGetExecutableTasksFromFile3(): void
    {
        $storage = new StorageFiles([__DIR__ . '/../Fixtures/not-exists.conf']);

        static::assertCount(0, $tasks = $storage->getExecutableTasks());
    }
}
