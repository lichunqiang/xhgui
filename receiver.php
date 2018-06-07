<?php
require __DIR__ . '/src/bootstrap.php';

use Workerman\Worker;


Worker::$pidFile = __DIR__ . '/cache/workerman.pid';
Worker::$logFile = __DIR__ . '/cache/workerman.log';

$worker = new Worker('udp://0.0.0.0:9501');

$worker->name = 'Xhprof_Receiver';
$worker->count = 4;

$worker->onMessage = function (\Workerman\Connection\ConnectionInterface $connection, $data) {
    try {
        $config = Xhgui_Config::all();
        $config += ['db.options' => []];
        $saver = Xhgui_Saver::factory($config);
        $saver->save(json_decode($data, true));
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
};


Worker::runAll();
