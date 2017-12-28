<?php


class ReceiverTest extends PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        
        $msg = json_encode([
            'profile' => [
                'name' => 'dsadas',
            ],
            'meta' => [
                'test' => 'dadsad',
                'dasdsa' => 'dasda',
            ],
        ]);
        $len = strlen($msg);
        $result = socket_sendto($sock, $msg, $len, 0, '127.0.0.1', 8799);
        socket_close($sock);
        
        $this->assertTrue($result !== false);
    }
}