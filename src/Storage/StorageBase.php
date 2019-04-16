<?php

namespace PE\Component\Cronos\Schedule\Storage;

use PE\Component\Cronos\Core\TaskInterface;
use PE\Component\Cronos\Expression\ExpressionFactory;

abstract class StorageBase implements ProviderInterface
{
    /**
     * @var ExpressionFactory
     */
    protected $expressionFactory;

    /**
     * @param ExpressionFactory|null $expressionFactory
     */
    public function __construct(ExpressionFactory $expressionFactory = null)
    {
        $this->expressionFactory = $expressionFactory ?: new ExpressionFactory();
    }

    /**
     * @inheritDoc
     */
    final public function getExecutableTasks(): array
    {
        $tasks = [];

        foreach ($this->fetchTasks() as $task) {
            $expr = $this->expressionFactory->create($task->getExpression());

            if ($expr->isDue()) {
                $tasks[] = $task;
            }
        }

        return $tasks;
    }
}
