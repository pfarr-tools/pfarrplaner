<?php

namespace App\Events\Models\AdChannel;

use App\Models\Ads\AdChannel;
use App\Models\People\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeletedAdChannel
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var User $user */
    protected $user;

    /** @var AdChannel $adChannel */
    protected $adChannel;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, AdChannel $adChannel)
    {
        $this->user = $user;
        $this->adChannel = $adChannel  ;
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
