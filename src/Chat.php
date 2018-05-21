<?php


namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $memcache;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->memcache = new \Memcache;
        $this->memcache->addServer('localhost', 11211);

//Считываем сонеты с файла в массив
        $sonnets = [];
        $file = fopen('../sonnets.txt', 'r');
        $sonnet = '';
        $num = 0;
        while (!feof($file)) {
            $str = fgets($file);
            if (preg_match('#(?<num>\d+)#', $str, $matches)) {
                $num = $matches['num'];
                $sonnets[$num] = '';
            } elseif (!preg_match('#^\r\n$#', $str)) {
                $sonnet .= $str;
            } else {
                $sonnets[$num] = $sonnet;
                $sonnet = '';
            }
        }
        fclose($file);
        $this->memcache->set('sonnets', $sonnets);
//        $this->memcache->set('sonnets', '123', false, 60);
        echo "in constructor\n";

    }

    public function onOpen(ConnectionInterface $conn)
    {

        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $sonnets = $this->memcache->get('sonnets');
        $index = random_int(127, 140);
        $text = $sonnets[$index];
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $text, $numRecv, $numRecv == 1 ? '' : 's');

        $msg = ['id' => $from->resourceId, 'text' => $text];
        foreach ($this->clients as $client) {
            if ($from == $client) {
                // The sender is not the receiver, send to each client connected
//        $this->clients[$from->resourceId]->send(json_encode($msg));
                $client->send(json_encode($msg));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}