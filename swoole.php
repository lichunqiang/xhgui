<?php
require __DIR__ . '/src/bootstrap.php';

$server = new \Swoole\Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);

$server->set([
    'daemonize' => 1,
    'worker_num' => 4,
    'log_file' => __DIR__ . '/cache/swoole.log',
    'pid_file' => __DIR__ . '/cache/swoole.pid',
]);
//加载测试配置
//Xhgui_Config::load(__DIR__ . '/config.dev.php');

$server->on('packet', function ($serv, $data, $client_info) {
    //debug the client info.
    $clientInfo = http_build_query($client_info, '', '|');
    echo sprintf('[%s] %s' . PHP_EOL, date('Y-m-d H:i:s'), http_build_query($client_info, '', '|'));
    error_log(date('Y-m-d H:i:s') . $clientInfo . PHP_EOL, 3, __DIR__ . '/cache/client.log');
    try {
        $config = Xhgui_Config::all();
        $config += ['db.options' => []];
        $saver = Xhgui_Saver::factory($config);
        $saver->save(json_decode($data, true));
    } catch (Exception $e) {
        error_log(
            sprintf('[%s] %s. clientInfo: %s. data: %s', date('Y-m-d H:i:s'), $e->getMessage(), $clientInfo, $data) . PHP_EOL,
            3,
            __DIR__ . '/cache/error.log'
        );
    }
});

echo 'Starting UDP Server: 0.0.0.0:9501' . PHP_EOL;
$server->start();
