<?php

namespace App\Message;

class RappelDeadline
{
    private int $projectId;
    private \DateTimeInterface $deadline;

    public function __construct(int $projectId, \DateTimeInterface $deadline)
    {
        $this->projectId = $projectId;
        $this->deadline = $deadline;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getDeadline(): \DateTimeInterface
    {
        return $this->deadline;
    }
} 