<?php
ini_set('memory_limit', '128m');
//xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY); 加入单入口PHP文件顶部

$GLOBALS['_PUT'] = []; //统一请求值 _GET，_POST, _PUT
register_shutdown_function(function() {
    $data['SHUTDOWN_TIME'] = microtime(true);
    $info = $GLOBALS['_SERVER'];

    $data['SERVER_NAME']  = '';
    if (isset($info['REQUEST_URI'])){
        $data['SERVER_NAME'] = $info['SERVER_NAME'];
        $data['REQUEST_METHOD'] = $info['REQUEST_METHOD'];
        $data['REQUEST_URI'] = $info['REQUEST_URI'];
        $data['REDIRECT_STATUS'] = $info['REDIRECT_STATUS'];
        $data['REQUEST_TIME'] = $info['REQUEST_TIME_FLOAT'];

        $data['_GET'] = $GLOBALS['_GET'];
        $data['_POST'] = $GLOBALS['_POST'];
        $data['_PUT'] = $GLOBALS['_PUT'];
        $data['_COOKIE'] = isset($GLOBALS['_COOKIE']) ? $GLOBALS['_COOKIE'] : [];
        $data['_SESSION'] = isset($GLOBALS['_SESSION']) ? $GLOBALS['_SESSION'] : [];
        $data['_FILES'] = isset($GLOBALS['_FILES']) ? $GLOBALS['_FILES'] : [];
    } else {
        $data['REQUEST_METHOD'] = 'cli';
        $data['REQUEST_URI'] = join('_', $info['argv']);
    }

    $data['_XHPROF'] = xhprof_disable();
    $uniqId = uniqid();
    $param = preg_replace(
                '/[^\w\_\-\[\]]+/', '_', $data['REQUEST_METHOD'] . '_' . $data['SERVER_NAME']
                . '-' . urldecode($data['REQUEST_URI'])
            );
    $param = mb_substr($param, 0, 200);
    $fileName = ini_get("xhprof.output_dir") . '/' .
            $uniqId . '.' . $param. '.xhprof';

    file_put_contents($fileName, json_encode($data, JSON_UNESCAPED_UNICODE));
});
