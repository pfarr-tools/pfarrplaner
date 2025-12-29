<?php

namespace App\Events\Models\Location;

use App\Models\People\User;
use App\Models\Location;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdatedLocation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var User $user */
    protected $user;

    /** @var Location $location */
    protected $location;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Location $location)
    {
        $this->user = $user;
        $this->location = $location;
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
