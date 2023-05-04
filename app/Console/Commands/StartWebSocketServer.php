<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use App\MyWebSocketServer;

class StartWebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'start:websocket-server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts the WebSocket server for the Laravel application.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $port = 8080; // change this to the port you want to use

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new MyWebSocketServer()
                )
            ),
            $port
        );

        $this->info("Starting WebSocket server on port $port...");
        $server->run();

        return Command::SUCCESS;
    }
}