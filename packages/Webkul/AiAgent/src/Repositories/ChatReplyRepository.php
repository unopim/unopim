<?php

namespace Webkul\AiAgent\Repositories;

use DateTimeInterface;
use Webkul\AiAgent\Models\ChatReply;
use Webkul\Core\Eloquent\Repository;

class ChatReplyRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return ChatReply::class;
    }

    /**
     * Find a reply owned by the admin.
     */
    public function findForUser(string $id, int $userId): ?ChatReply
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Atomically move a queued reply to generating.
     *
     * Returns false when the reply was cancelled or another worker already
     * claimed it, so a reply is generated at most once.
     */
    public function claim(string $id): bool
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('status', ChatReply::STATUS_QUEUED)
            ->update(['status' => ChatReply::STATUS_GENERATING]) === 1;
    }

    /**
     * Cancel the admin's reply while no worker has claimed it yet.
     *
     * Returns false once a worker is generating it, in which case the caller
     * keeps polling instead of falling back.
     */
    public function cancelQueued(string $id, int $userId): bool
    {
        return $this->model->newQuery()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('status', ChatReply::STATUS_QUEUED)
            ->update(['status' => ChatReply::STATUS_CANCELLED]) === 1;
    }

    /**
     * Mark a reply that has not finished as failed.
     */
    public function failUnfinished(string $id, string $error): void
    {
        $this->model->newQuery()
            ->where('id', $id)
            ->whereIn('status', [ChatReply::STATUS_QUEUED, ChatReply::STATUS_GENERATING])
            ->update([
                'status'       => ChatReply::STATUS_ERROR,
                'error'        => $error,
                'agent_status' => null,
            ]);
    }

    /**
     * Delete replies created before the cutoff and return how many were removed.
     */
    public function pruneCreatedBefore(DateTimeInterface $cutoff): int
    {
        return $this->model->newQuery()
            ->where('created_at', '<', $cutoff)
            ->delete();
    }

    /**
     * Count replies created before the cutoff.
     */
    public function countCreatedBefore(DateTimeInterface $cutoff): int
    {
        return $this->model->newQuery()
            ->where('created_at', '<', $cutoff)
            ->count();
    }
}
