<?php

namespace PE\Component\Cronos\Schedule\Tests;

use PE\Component\Cronos\Core\ClientAction;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Schedule\Storage\PersisterInterface;
use PE\Component\Cronos\Schedule\Storage\ProviderInterface;
use PE\Component\Cronos\Schedule\ScheduleAPI;
use PE\Component\Cronos\Schedule\ScheduleModule;
use PE\Component\Cronos\Core\QueueInterface;
use PE\Component\Cronos\Core\ServerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScheduleModuleTest extends TestCase
{
    /**
     * @var ProviderInterface|MockObject
     */
    private $storage1;

    /**
     * @var PersisterInterface|MockObject
     */
    private $storage2;

    protected function setUp(): void
    {
        $this->storage1 = $this->createMock(ProviderInterface::class);
        $this->storage2 = $this->createMock(PersisterInterface::class);
    }

    public function testAttachServerForProvider(): void
    {
        $module = new ScheduleModule($this->storage1);

        /* @var $server ServerInterface|MockObject */
        $server = $this->createMock(ServerInterface::class);

        $server->expects(static::once())->method('attachListener')->withConsecutive(
            [ServerInterface::EVENT_ENQUEUE_TASKS, [$module, 'onEnqueueTasks']]
        );

        $module->attachServer($server);
    }

    public function testAttachServer(): void
    {
        $module = new ScheduleModule($this->storage2);

        /* @var $server ServerInterface|MockObject */
        $server = $this->createMock(ServerInterface::class);

        $server->expects(static::exactly(6))->method('attachListener')->withConsecutive(
            [ServerInterface::EVENT_ENQUEUE_TASKS, [$module, 'onEnqueueTasks']],
            [ServerInterface::EVENT_SET_TASK_EXECUTED, [$module, 'onTaskExecuted']],
            [ServerInterface::EVENT_SET_TASK_ESTIMATE, [$module, 'onTaskEstimate']],
            [ServerInterface::EVENT_SET_TASK_PROGRESS, [$module, 'onTaskProgress']],
            [ServerInterface::EVENT_SET_TASK_FINISHED, [$module, 'onTaskFinished']],
            [ServerInterface::EVENT_CLIENT_ACTION, [$module, 'onClientAction']]
        );

        $module->attachServer($server);
    }

    public function testDetachServerForProvider(): void
    {
        $module = new ScheduleModule($this->storage1);

        /* @var $server ServerInterface|MockObject */
        $server = $this->createMock(ServerInterface::class);

        $server->expects(static::once())->method('detachListener')->withConsecutive(
            [ServerInterface::EVENT_ENQUEUE_TASKS, [$module, 'onEnqueueTasks']]
        );

        $module->detachServer($server);
    }

    public function testDetachServer(): void
    {
        $module = new ScheduleModule($this->storage2);

        /* @var $server ServerInterface|MockObject */
        $server = $this->createMock(ServerInterface::class);

        $server->expects(static::exactly(6))->method('detachListener')->withConsecutive(
            [ServerInterface::EVENT_ENQUEUE_TASKS, [$module, 'onEnqueueTasks']],
            [ServerInterface::EVENT_SET_TASK_EXECUTED, [$module, 'onTaskExecuted']],
            [ServerInterface::EVENT_SET_TASK_ESTIMATE, [$module, 'onTaskEstimate']],
            [ServerInterface::EVENT_SET_TASK_PROGRESS, [$module, 'onTaskProgress']],
            [ServerInterface::EVENT_SET_TASK_FINISHED, [$module, 'onTaskFinished']],
            [ServerInterface::EVENT_CLIENT_ACTION, [$module, 'onClientAction']]
        );

        $module->detachServer($server);
    }

    public function testOnEnqueueTasks(): void
    {
        $module = new ScheduleModule($this->storage1);
        $module->setID('SCHEDULE');

        /* @var $task1 TaskInterface|MockObject */
        $task1 = $this->createMock(TaskInterface::class);
        $task1->expects(static::once())->method('setModuleID')->with('SCHEDULE');

        /* @var $task2 TaskInterface|MockObject */
        $task2 = $this->createMock(TaskInterface::class);
        $task2->expects(static::once())->method('setModuleID')->with('SCHEDULE');

        $this->storage1->expects(static::once())->method('getExecutableTasks')->willReturn([$task1, $task2]);

        /* @var $queue QueueInterface|MockObject */
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects(static::exactly(2))->method('enqueue')->withConsecutive($task1, $task2);

        $module->onEnqueueTasks($queue);
    }

    public function testOnTaskExecutedWithoutPersister(): void
    {
        $module = new ScheduleModule($this->storage1);

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::never())->method('getModuleID');

        $module->onTaskExecuted($task);
    }

    public function testOnTaskExecutedOtherModule(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE2');

        $this->storage2->expects(static::never())->method('updateTask');

        $module->onTaskExecuted($task);
    }

    public function testOnTaskExecuted(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE');

        $this->storage2->expects(static::once())->method('updateTask')->with($task);

        $module->onTaskExecuted($task);
    }

    public function testOnTaskEstimateWithoutPersister(): void
    {
        $module = new ScheduleModule($this->storage1);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::never())->method('getModuleID');

        $module->onTaskEstimate($task);
    }

    public function testOnTaskEstimateOtherModule(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE2');

        $this->storage2->expects(static::never())->method('updateTask');

        $module->onTaskEstimate($task);
    }

    public function testOnTaskEstimate(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE');

        $this->storage2->expects(static::once())->method('updateTask')->with($task);

        $module->onTaskEstimate($task);
    }

    public function testOnTaskProgressWithoutPersister(): void
    {
        $module = new ScheduleModule($this->storage1);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::never())->method('getModuleID');

        $module->onTaskProgress($task);
    }

    public function testOnTaskProgressOtherModule(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE2');

        $this->storage2->expects(static::never())->method('updateTask');

        $module->onTaskProgress($task);
    }

    public function testOnTaskProgress(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE');

        $this->storage2->expects(static::once())->method('updateTask')->with($task);

        $module->onTaskProgress($task);
    }

    public function testOnTaskFinishedWithoutPersister(): void
    {
        $module = new ScheduleModule($this->storage1);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::never())->method('getModuleID');

        $module->onTaskFinished($task);
    }

    public function testOnTaskFinishedOtherModule(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE2');

        $this->storage2->expects(static::never())->method('updateTask');

        $module->onTaskFinished($task);
    }

    public function testOnTaskFinished(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getModuleID')->willReturn('SCHEDULE');

        $this->storage2->expects(static::once())->method('updateTask')->with($task);

        $module->onTaskFinished($task);
    }

    public function testOnClientRequest(): void
    {
        $module = new ScheduleModule($this->storage2);
        $module->setID('SCHEDULE');

        /* @var $task1 TaskInterface|MockObject */
        $task1 = $this->createMock(TaskInterface::class);
        $task2 = clone $task1;

        $this->storage2
            ->expects(static::once())
            ->method('insertTask')
            ->with($task1)
            ->willReturn($task2);

        $this->storage2
            ->expects(static::once())
            ->method('updateTask')
            ->with($task1)
            ->willReturn($task2);

        $this->storage2
            ->expects(static::once())
            ->method('removeTask')
            ->with($task1)
            ->willReturn($task2);

        $module->onClientAction($event = new ClientAction(ScheduleAPI::INSERT_TASK, $task1));

        static::assertSame($task2, $event->getResult());

        $module->onClientAction($event = new ClientAction(ScheduleAPI::UPDATE_TASK, $task1));

        static::assertSame($task2, $event->getResult());

        $module->onClientAction($event = new ClientAction(ScheduleAPI::REMOVE_TASK, $task1));

        static::assertSame($task2, $event->getResult());
    }
}
