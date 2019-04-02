<?php

namespace PE\Component\Cronos\Schedule;

use PE\Component\Cronos\Core\ClientInterface;
use PE\Component\Cronos\Core\TaskInterface;

class ScheduleAPI
{
    public const INSERT_TASK = 'schedule:insert_task';
    public const UPDATE_TASK = 'schedule:update_task';
    public const REMOVE_TASK = 'schedule:remove_task';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param TaskInterface $task
     *
     * @return TaskInterface
     */
    public function insertTask(TaskInterface $task): TaskInterface
    {
        return $this->client->request(self::INSERT_TASK, $task);
    }

    /**
     * @param TaskInterface $task
     *
     * @return TaskInterface
     */
    public function updateTask(TaskInterface $task): TaskInterface
    {
        return $this->client->request(self::UPDATE_TASK, $task);
    }

    /**
     * @param TaskInterface $task
     *
     * @return TaskInterface
     */
    public function removeTask(TaskInterface $task): TaskInterface
    {
        return $this->client->request(self::REMOVE_TASK, $task);
    }
}
