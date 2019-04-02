<?php

namespace PE\Component\Cronos\Schedule\Tests\Storage;

use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Schedule\Storage\StorageBase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StorageBaseTest extends TestCase
{
    public function testGetExecutableTasks(): void
    {
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getExpression')->willReturn('* * * * *');

        /* @var $storage StorageBase|MockObject */
        $storage = $this->getMockForAbstractClass(StorageBase::class);

        $storage
            ->expects(static::once())
            ->method('load')
            ->willReturn([$task]);

        $tasks = $storage->getExecutableTasks();

        static::assertCount(1, $tasks);
    }
}
