<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;


class BroadcastController extends Controller
{
    public function broadcast(Request $request)
    {
        Redis::publish('example', 'Hello, world!');

        WebSocketsRouter::broadcastToChannel('example', [
            'type' => 'message',
            'content' => 'Hello, world!'
        ]);

        return response()->json(['success' => true]);
    }
}
