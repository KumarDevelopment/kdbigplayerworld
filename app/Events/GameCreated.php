<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class GameCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $gameData;

    public function __construct(array $gameData)
    {
        $this->gameData = $gameData;
    }

    public function broadcastOn()
    {
          //  Log::debug('Game created event broadcasted');
        return new PrivateChannel('game-channel');
    }

    public function broadcastAs()
    {
        return 'game.created';
    }
}


