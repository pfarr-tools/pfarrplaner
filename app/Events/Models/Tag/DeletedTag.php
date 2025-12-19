<?php

namespace App\Events\Models\Tag;

use App\Models\People\User;
use App\Models\Tag;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeletedTag
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var User $user */
    protected $user;

    /** @var Tag $tag */
    protected $tag;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Tag $tag)
    {
        $this->user = $user;
        $this->tag = $tag;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
