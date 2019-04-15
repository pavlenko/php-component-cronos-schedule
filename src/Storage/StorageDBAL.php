<?php

namespace PE\Component\Cronos\Schedule\Storage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use PE\Component\Cronos\Core\Task;
use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Expression\ExpressionFactory;

final class StorageDBAL extends StorageBase implements PersisterInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @param Connection             $connection
     * @param string                 $tableName
     * @param ExpressionFactory|null $expressionFactory
     */
    public function __construct(Connection $connection, string $tableName, ExpressionFactory $expressionFactory = null)
    {
        $this->connection = $connection;
        $this->tableName  = $tableName;

        parent::__construct($expressionFactory);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @codeCoverageIgnore
     */
    public function initialize(): void
    {
        $platform  = $this->connection->getDatabasePlatform();
        $schemaOld = $this->connection->getSchemaManager()->createSchema();
        $schemaNew = clone $schemaOld;

        if ($schemaNew->hasTable($this->tableName)) {
            $schemaNew->dropTable($this->tableName);
        }

        $table = $schemaNew->createTable($this->tableName);
        $table->addColumn('id', Type::INTEGER, ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('name', Type::STRING, ['length' => 255]);
        $table->addColumn('arguments', Type::TEXT, ['notnull' => false, 'length' => 65535]);
        $table->addColumn('status', Type::INTEGER, ['unsigned' => true]);
        $table->addColumn('error', Type::TEXT, ['notnull' => false, 'length' => 65535]);
        $table->addColumn('estimate', Type::INTEGER, ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('progress', Type::INTEGER, ['unsigned' => true, 'notnull' => false]);
        $table->addColumn('scheduledAt', Type::DATETIME, ['notnull' => false]);
        $table->addColumn('scheduledMs', Type::INTEGER, ['notnull' => false]);
        $table->addColumn('executedAt', Type::DATETIME, ['notnull' => false]);
        $table->addColumn('executedMs', Type::INTEGER, ['notnull' => false]);
        $table->addColumn('finishedAt', Type::DATETIME, ['notnull' => false]);
        $table->addColumn('finishedMs', Type::INTEGER, ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status'], 'idx_status');
        $table->addIndex(['scheduledAt'], 'idx_scheduledAt');

        foreach ($schemaOld->getMigrateToSql($schemaNew, $platform) as $sql) {
            $this->connection->executeQuery($sql);
        }
    }

    /**
     * @inheritDoc
     */
    protected function load(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->from($this->tableName)
            ->where($query->expr()->in('status', [TaskInterface::STATUS_PENDING, TaskInterface::STATUS_IN_PROGRESS]));

        $data = [];
        $rows = $query->execute()->fetchAll();

        foreach ($rows as $row) {
            $data[] = $this->convertArrayToTask($row);
        }

        return $data;
    }

    /**
     * @inheritDoc
     * @throws \Doctrine\DBAL\DBALException
     */
    public function insertTask(TaskInterface $task): TaskInterface
    {
        $this->connection->insert($this->tableName, $this->convertTaskToArray($task));
        return $task->setID($this->connection->lastInsertId());
    }

    /**
     * @inheritDoc
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateTask(TaskInterface $task): TaskInterface
    {
        $this->connection->update($this->tableName, $this->convertTaskToArray($task), ['id' => $task->getID()]);
        return $task;
    }

    /**
     * @inheritDoc
     * @throws \Doctrine\DBAL\DBALException
     */
    public function removeTask(TaskInterface $task): TaskInterface
    {
        $this->connection->delete($this->tableName, ['id' => $task->getID()]);
        return $task;
    }

    /**
     * @param TaskInterface $task
     *
     * @return array
     */
    public function convertTaskToArray(TaskInterface $task): array
    {
        $data = [
            'name'       => $task->getName(),
            'arguments'  => json_encode($task->getArguments()),
            'status'     => $task->getStatus(),
            'error'      => $task->getError() ? (string) $task->getError() : null,
            'expression' => $task->getExpression(),
            'estimate'   => $task->getEstimate(),
            'progress'   => $task->getProgress(),
        ];

        if ($task->getScheduledAt()) {
            $data['scheduledAt'] = $task->getScheduledAt()->format('Y-m-d H:i:s');
            $data['scheduledMs'] = $task->getScheduledAt()->format('v');
        } else {
            $data['scheduledAt'] = null;
            $data['scheduledMs'] = null;
        }

        if ($task->getExecutedAt()) {
            $data['executedAt'] = $task->getExecutedAt()->format('Y-m-d H:i:s');
            $data['executedMs'] = $task->getExecutedAt()->format('v');
        } else {
            $data['executedAt'] = null;
            $data['executedMs'] = null;
        }

        if ($task->getFinishedAt()) {
            $data['finishedAt'] = $task->getFinishedAt()->format('Y-m-d H:i:s');
            $data['finishedMs'] = $task->getFinishedAt()->format('v');
        } else {
            $data['finishedAt'] = null;
            $data['finishedMs'] = null;
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return TaskInterface
     */
    public function convertArrayToTask(array $data): TaskInterface
    {
        $task = new Task();

        $task->setID($data['id']);
        $task->setName($data['name']);
        $task->setArguments((array) json_decode($data['arguments'] ?: '[]'));
        $task->setExpression($data['expression']);
        $task->setStatus($data['status']);
        $task->setEstimate($data['estimate']);
        $task->setProgress($data['progress']);

        if (!empty($data['scheduledAt'])) {
            $date = \DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                $data['scheduledAt'] . '.' . ($data['scheduledMs'] ?? 0)
            );

            $task->setScheduledAt($date);
        }

        if (!empty($data['executedAt'])) {
            $date = \DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                $data['executedAt'] . '.' . ($data['executedMs'] ?? 0)
            );

            $task->setExecutedAt($date);
        }

        if (!empty($data['finishedAt'])) {
            $date = \DateTime::createFromFormat(
                'Y-m-d H:i:s.u',
                $data['finishedAt'] . '.' . ($data['finishedMs'] ?? 0)
            );

            $task->setFinishedAt($date);
        }

        return $task;
    }
}
