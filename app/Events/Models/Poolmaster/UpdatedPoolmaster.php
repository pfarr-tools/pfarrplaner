<?php

namespace App\Events\Models\Poolmaster;

use App\Models\Leave\Poolmaster;
use App\Models\People\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdatedPoolmaster
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var User $user */
    protected $user;

    /** @var Poolmaster $poolmaster */
    protected $poolmaster;


    public function __construct(User $user, Poolmaster $poolmaster)
    {
        $this->user = $user;
        $this->poolmaster = $poolmaster;
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
