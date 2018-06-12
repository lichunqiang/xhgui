<?php
if (!extension_loaded('xhprof')) {
    error_log('xhgui - xhprof extension is not installed.');
    
    return;
}

//是否采集cli
if (PHP_SAPI === 'cli' && !getenv('XHPROF_ENABLE_CLI')) {
    return;
}

$blockIpList = [
    //'127.0.0.1',
];

if (isset($_SERVER['REMOTE_ADDR'])) {
    foreach ($blockIpList as $filter) {
        if ($filter === $_SERVER['REMOTE_ADDR']) {
            return;
        }
        if (($pos = strpos($filter, '*')) !== false && strncmp($_SERVER['REMOTE_ADDR'], $filter, $pos) === 0) {
            return;
        }
    }
}
//settings
$GLOBALS['XHGUI_UDP_ADDR'] = '192.168.10.11';
$GLOBALS['XHGUI_UDP_PORT'] = 9501;
$GLOBALS['XHGUI_REMOTE'] = 'http://192.168.10.11:8080/api/receiver';

if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
}

if (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 4) {
    xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY | XHPROF_FLAGS_NO_BUILTINS);
} else {
    xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
}

register_shutdown_function(
    function () {
        $data['profile'] = xhprof_disable();
        //replace slashes, the key contains slashed is invalid to store mongodb
        $profile = [];
        foreach ($data['profile'] as $key => $value) {
            $profile[strtr($key, ['.' => '_'])] = $value;
        }
        
        $data['profile'] = $profile;
        //ignore_user_abort(true);
        //flush();
        
        $uri = array_key_exists('REQUEST_URI', $_SERVER)
            ? $_SERVER['REQUEST_URI']
            : null;
        if (empty($uri) && isset($_SERVER['argv'])) {
            $cmd = basename($_SERVER['argv'][0]);
            $uri = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        }
        $time = array_key_exists('REQUEST_TIME', $_SERVER)
            ? $_SERVER['REQUEST_TIME']
            : time();
        // In some cases there is comma instead of dot
        $delimiter = (strpos($_SERVER['REQUEST_TIME_FLOAT'], ',') !== false) ? ',' : '.';
        $requestTimeFloat = explode($delimiter, $_SERVER['REQUEST_TIME_FLOAT']);
        if (!isset($requestTimeFloat[1])) {
            $requestTimeFloat[1] = 0;
        }
        //for mongodb
        if (extension_loaded('mongo')) {
            $requestTs = new MongoDate($time);
            $requestTsMicro = new MongoDate($requestTimeFloat[0], $requestTimeFloat[1]);
        } else {
            $requestTs = ['sec' => $time, 'usec' => 0];
            $requestTsMicro = ['sec' => $requestTimeFloat[0], 'usec' => $requestTimeFloat[1]];
        }
        
        $data['meta'] = [
            'url' => $uri,
            'SERVER' => $_SERVER,
            'get' => $_GET,
            'env' => $_ENV,
            'simple_url' => preg_replace('/\=\d+/', '', $uri),
            'request_ts' => $requestTs,
            'request_ts_micro' => $requestTsMicro,
            'request_date' => date('Y-m-d', $time),
        ];
    
        $msg = json_encode($data, JSON_UNESCAPED_UNICODE);
        $len = strlen($msg);
        
        if ($GLOBALS['XHGUI_UDP_ADDR'] && $len < 65023) {
            //消息大小小于datagram最大值通过udp 发送日志
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_sendto($sock, $msg, $len, 0, $GLOBALS['XHGUI_UDP_ADDR'], $GLOBALS['XHGUI_UDP_PORT']);
            socket_close($sock);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'timeout' => 1,
                    'header' => 'Content-Type: text/json',
                    'content' => $msg,
                ],
            ]);
            if (false === ($response = file_get_contents($GLOBALS['XHGUI_REMOTE'], false, $context))) {
                //send to php error log.
                error_log('xhgui - xhprof send data faild.');
            }
        }
    }
);
