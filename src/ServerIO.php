<?php
/**
 * Created by PhpStorm.
 * User: Mathijs
 * Date: 2017-07-02
 * Time: 11:36 AM
 */

namespace bombants\backend;


use bombants\backend\models\Player;
use bombants\backend\responses\Authenticated;
use bombants\backend\responses\AuthenticatedAlready;
use bombants\backend\responses\MessageInvalid;
use bombants\backend\responses\AuthenticatedNot;
use bombants\backend\value\Token;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class ServerIO implements MessageComponentInterface
{

    private $server = null;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    function onOpen(ConnectionInterface $conn)
    {
        echo 'Connection opened'.PHP_EOL;

        $player = new Player($conn->resourceId);
        $this->server->addPlayer($player);
    }

    function onClose(ConnectionInterface $conn)
    {
        echo 'Connection closed'.PHP_EOL;
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo 'Connection error'.PHP_EOL;
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = json_decode($msg);

        // if the message can not be decoded
        if (false === is_object($msg)) {
            $from->send((string)new MessageInvalid());
            return;
        }

        if (empty($msg->path)) {
            $from->send((string)new MessageInvalid());
            return;
        }

        $player = $this->server->getPlayer($from->resourceId);

        $token = !empty($msg->token) ? $msg->token : null;
        $token = Token::fromString($token);

        if ($msg->path === '/login') {
            if ($player->isAuthenticated($token)) {
                $response = new AuthenticatedAlready();
                $from->send((string)$response);
                return;
            }

            $player->setToken(Token::random());
            $response = new Authenticated($player);
            $from->send((string)$response);
            return;
        }

        if (!$player->isAuthenticated($token)) {
            $response = new AuthenticatedNot();
            $from->send((string)$response);
            return;
        }


        echo 'Connection message: '.PHP_EOL;
        var_dump($from->resourceId);
        var_dump($msg);
    }
}