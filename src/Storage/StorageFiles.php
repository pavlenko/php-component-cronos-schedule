<?php

namespace PE\Component\Cronos\Schedule\Storage;

use PE\Component\Cronos\Core\Task;
use PE\Component\Cronos\Expression\ExpressionFactory;

final class StorageFiles extends StorageBase
{
    /**
     * @var string[]
     */
    private $files;

    /**
     * @inheritDoc
     * @param string[] $files
     */
    public function __construct(array $files, ?ExpressionFactory $expressionFactory = null)
    {
        $this->files = array_filter($files, 'is_string');
        parent::__construct($expressionFactory);
    }

    /**
     * @inheritDoc
     */
    public function fetchTasks(): array
    {
        $fields = $this->expressionFactory->getFieldFactories();
        $tasks  = [];

        foreach ($this->files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $lines = file($file, FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES) ?? [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ('' === $line || strpos($line, '#') === 0) {
                    // Skip empty lines and comments
                    continue;
                }

                // Standard expression
                $parts = preg_split('/\s/', $line, -1, PREG_SPLIT_NO_EMPTY);

                $expression = implode(' ', array_slice($parts, 0, \count($fields)));

                if (!$this->expressionFactory->validate($expression)) {
                    continue;
                }

                $taskName  = implode(' ', array_slice($parts, \count($fields), 1));
                $arguments = array_slice($parts, count($tasks) + 1);

                $task = new Task();
                $task->setID($expression . ' ' . $taskName);
                $task->setName($taskName);
                $task->setArguments($arguments);
                $task->setExpression($expression);

                $tasks[] = $task;
            }
        }

        return $tasks;
    }
}
