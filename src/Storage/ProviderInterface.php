<?php

namespace PE\Component\Cronos\Schedule\Storage;

use PE\Component\Cronos\Core\TaskInterface;

interface ProviderInterface
{
    /**
     * @return TaskInterface[]
     */
    public function getExecutableTasks(): array;

    /**
     * @return TaskInterface[]
     */
    public function fetchTasks(): array;
}
