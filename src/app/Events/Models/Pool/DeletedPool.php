<?php

namespace App\Events\Models\Pool;

use App\Models\Leave\Pool;
use App\Models\People\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeletedPool
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var User */
    protected $user;

    /** @var Pool */
    protected $pool;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Pool $pool)
    {
        $this->user = $user;
        $this->pool = $pool;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
