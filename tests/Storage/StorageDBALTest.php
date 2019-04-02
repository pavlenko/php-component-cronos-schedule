<?php

namespace PE\Component\Cronos\Schedule\Tests\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Schedule\Storage\StorageDBAL;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StorageDBALTest extends TestCase
{
    private const TABLE = 'TBL';

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var StorageDBAL
     */
    private $storage;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);
        $this->storage    = new StorageDBAL($this->connection, self::TABLE);
    }

    public function testGetExecutableTask(): void
    {
        $data = [
            [
                'id'          => 'ID',
                'name'        => 'TASK',
                'expression'  => '* * * * *',
                'status'      => TaskInterface::STATUS_PENDING,
                'error'       => null,
                'estimate'    => null,
                'progress'    => null,
                'scheduledAt' => null,
                'executedAt'  => null,
                'finishedAt'  => null,
            ]
        ];

        $statement = $this->createMock(Statement::class);
        $statement->expects(static::once())->method('fetchAll')->willReturn($data);

        $this->connection->method('getExpressionBuilder')->willReturn(new ExpressionBuilder($this->connection));
        $this->connection->method('createQueryBuilder')->willReturn(new QueryBuilder($this->connection));

        $this->connection
            ->expects(static::once())
            ->method('executeQuery')
            ->willReturn($statement);

        $tasks = $this->storage->getExecutableTasks();

        static::assertCount(1, $tasks);
        static::assertInstanceOf(TaskInterface::class, $tasks[0]);
        static::assertSame('ID', $tasks[0]->getID());
        static::assertSame('TASK', $tasks[0]->getName());
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testInsertTask(): void
    {
        $this->connection
            ->expects(static::once())
            ->method('insert')
            ->with(self::TABLE, static::isType('array'));

        $this->connection
            ->expects(static::once())
            ->method('lastInsertId')
            ->willReturn(1);

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('setID')->with(1)->willReturn($task);

        $this->storage->insertTask($task);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testUpdateTask(): void
    {
        $this->connection
            ->expects(static::once())
            ->method('update')
            ->with(self::TABLE, static::isType('array'), ['id' => 2]);

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getID')->willReturn(2);

        $this->storage->updateTask($task);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testRemoveTask(): void
    {
        $this->connection
            ->expects(static::once())
            ->method('delete')
            ->with(self::TABLE, ['id' => 2]);

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->expects(static::once())->method('getID')->willReturn(2);

        $this->storage->removeTask($task);
    }

    public function testConvertTaskToArray(): void
    {
        $date  = \DateTime::createFromFormat('Y-m-d H:i:s.u', '2000-01-01 00:00:00.100000');
        $error = new \Exception('ERROR');

        /* @var $task TaskInterface|MockObject */
        $task = $this->createMock(TaskInterface::class);
        $task->method('getName')->willReturn('TASK');
        $task->method('getExpression')->willReturn('* * * * *');
        $task->method('getStatus')->willReturn(TaskInterface::STATUS_ERROR);
        $task->method('getError')->willReturn($error);
        $task->method('getEstimate')->willReturn(100);
        $task->method('getProgress')->willReturn(99);
        $task->method('getScheduledAt')->willReturn($date);
        $task->method('getExecutedAt')->willReturn($date);
        $task->method('getFinishedAt')->willReturn($date);

        $data = [
            'name'        => 'TASK',
            'expression'  => '* * * * *',
            'status'      => TaskInterface::STATUS_ERROR,
            'error'       => (string) $error,
            'estimate'    => 100,
            'progress'    => 99,
            'scheduledAt' => '2000-01-01 00:00:00',
            'scheduledMs' => '100',
            'executedAt'  => '2000-01-01 00:00:00',
            'executedMs'  => '100',
            'finishedAt'  => '2000-01-01 00:00:00',
            'finishedMs'  => '100',
        ];

        static::assertEquals($data, $this->storage->convertTaskToArray($task));
    }

    public function testConvertArrayToTask(): void
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s.u', '2000-01-01 00:00:00.100000');

        $data = [
            'id'          => 100,
            'name'        => 'TASK',
            'expression'  => '* * * * *',
            'status'      => TaskInterface::STATUS_ERROR,
            'error'       => null,
            'estimate'    => 100,
            'progress'    => 99,
            'scheduledAt' => '2000-01-01 00:00:00',
            'scheduledMs' => '100',
            'executedAt'  => '2000-01-01 00:00:00',
            'executedMs'  => '100',
            'finishedAt'  => '2000-01-01 00:00:00',
            'finishedMs'  => '100',
        ];

        $task = $this->storage->convertArrayToTask($data);

        static::assertEquals(100, $task->getID());
        static::assertEquals('TASK', $task->getName());
        static::assertEquals('* * * * *', $task->getExpression());
        static::assertEquals(TaskInterface::STATUS_ERROR, $task->getStatus());
        static::assertEquals(null, $task->getError());
        static::assertEquals(100, $task->getEstimate());
        static::assertEquals(99, $task->getProgress());
        static::assertNotSame($date, $task->getScheduledAt());
        static::assertEquals($date, $task->getScheduledAt());
        static::assertNotSame($date, $task->getExecutedAt());
        static::assertEquals($date, $task->getExecutedAt());
        static::assertNotSame($date, $task->getFinishedAt());
        static::assertEquals($date, $task->getFinishedAt());
    }
}
