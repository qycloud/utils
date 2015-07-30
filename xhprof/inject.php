<?php
/**
 * INSTALL
 *
 * vi /etc/php5/fpm/conf.d/xhprof.ini
 * ```
 * [xhprof]
 * extension=xhprof.so;
 * xhprof.output_dir=/var/log/xhprof
 *
 * auto_prepend_file = /[path]/xhprof/inject.php
 * ```
 * sudo service php5-fpm restart
 *
 * nginx server配置中添加环境变量 MONITORED = true
 */

//监控执行上限 /ms
$monitorLimit = 1000;

/**
 * $monitorTimes 记录时间
 * 时间段:
 * ['2015-04-01 12:34:56', '2015-04-01 12:39:33']
 * [           '12:34:00',            '12:35:00']   每天'12:34:00'至 '12:35:00'
 * [              '30:00',               '31:56']   每小时'30:00'至'31:56'
 * [                 '30',                  '56']   每分钟'30'秒 至 '56'秒
 * [                  '0',                   '5']   秒数小于5的时间，相当于 监控5秒，停五秒
 */
$monitorTimes = [
//    ['15:34:56', '24:39:33'],
];

$sites = [
//    'www.test.com',
];

/**** running *******/
$serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';

if (!in_array($serverName, $sites)) {
    return;
}

$now = date('Y-m-d H:i:s');
$timeLen = strlen($now);
$status = empty($monitorTimes);
foreach ($monitorTimes as $index => $times) {
    list($startTime, $endTime) = $times;
    $startTime = substr($now, 0, $timeLen - strlen($startTime)) . $startTime;
    $endTime = substr($now, 0, $timeLen - strlen($endTime)) . $endTime;

    if ($startTime <= $now && $now <= $endTime) {
        $status = true;
        break;
    }
}

if (!$status) {
    return;
}

//monitoring
xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

$GLOBALS['_PUT'] = []; //统一请求值 _GET，_POST, _PUT
define('MONITOR_LIMIT', isset($monitorLimit) ? intval($monitorLimit) / 1000 : 0);

register_shutdown_function(function() {
    $data['SHUTDOWN_TIME'] = microtime(true);
    if (MONITOR_LIMIT > ($data['SHUTDOWN_TIME'] - $GLOBALS['_SERVER']['REQUEST_TIME_FLOAT'])) {
        return;
    }

    $data['SERVER_NAME'] = $GLOBALS['_SERVER']['SERVER_NAME'];
    $data['REQUEST_METHOD'] = $GLOBALS['_SERVER']['REQUEST_METHOD'];
    $data['REQUEST_URI'] = $GLOBALS['_SERVER']['REQUEST_URI'];
    $data['REDIRECT_STATUS'] = $GLOBALS['_SERVER']['REDIRECT_STATUS'];
    $data['REQUEST_TIME'] = $GLOBALS['_SERVER']['REQUEST_TIME_FLOAT'];
    $data['SHUTDOWN_TIME'] = microtime(true);

    $data['_GET'] = $GLOBALS['_GET'];
    $data['_POST'] = $GLOBALS['_POST'];
    $data['_PUT'] = $GLOBALS['_PUT'];
    $data['_COOKIE'] = isset($GLOBALS['_COOKIE']) ? $GLOBALS['_COOKIE'] : [];
    $data['_SESSION'] = isset($GLOBALS['_SESSION']) ? $GLOBALS['_SESSION'] : [];
    $data['_FILES'] = isset($GLOBALS['_FILES']) ? $GLOBALS['_FILES'] : [];

    $data['_XHPROF'] = xhprof_disable();

    $fileName = ini_get("xhprof.output_dir") . '/' . uniqid() . '.' . $data['SERVER_NAME'] .
            urldecode(str_replace(array('/', '?','&'), '_', $data['REQUEST_URI'])) . '.xhprof';

    file_put_contents($fileName, json_encode($data, JSON_UNESCAPED_UNICODE));
});