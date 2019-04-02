<?php

namespace PE\Component\Cronos\Schedule\Storage;

use PE\Component\Cronos\Core\TaskInterface;

interface PersisterInterface extends ProviderInterface
{
    /**
     * @param TaskInterface $task
     *
     * @return TaskInterface
     */
    public function insertTask(TaskInterface $task): TaskInterface;

    /**
     * @param TaskInterface $task
     *
     * @return TaskInterface
     */
    public function updateTask(TaskInterface $task): TaskInterface;

    /**
     * @param TaskInterface $task
     *
     * @return TaskInterface
     */
    public function removeTask(TaskInterface $task): TaskInterface;
}
