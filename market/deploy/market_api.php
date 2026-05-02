<?php

/**
 * RuiNexus Market - API Entry Point
 *
 * 独立 API 入口文件，不依赖 data/route/api.php 路由注册
 * 安装在 public/ 目录下，前端直接请求此文件
 *
 * 请求方式: market_api.php?action=list&page=1&size=20
 *
 * 开发者: RuiNexus / YeHuaiJing
 */

define('APP_DEBUG', false);
define('CMF_ROOT', dirname(__DIR__) . '/');
define('CMF_DATA', CMF_ROOT . 'data/');
define('APP_PATH', CMF_ROOT . 'app/');
define('WEB_ROOT', __DIR__ . '/');

require CMF_ROOT . 'vendor/thinkphp/base.php';

$app = \think\Container::get('app', [APP_PATH]);
$app->initialize();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');
$method = $_SERVER['REQUEST_METHOD'];

$controller = new \app\api\controller\MarketApiController();

$routes = [
    ['method' => 'GET',     'action' => 'config',       'handler' => 'config'],
    ['method' => 'GET',     'action' => 'list',         'handler' => 'list'],
    ['method' => 'GET',     'action' => 'detail',       'handler' => 'detail',    'args' => [intval($_GET['id'] ?? 0)]],
    ['method' => 'POST',    'action' => 'buy',          'handler' => 'buy'],
    ['method' => 'POST',    'action' => 'create',       'handler' => 'create'],
    ['method' => 'POST',    'action' => 'update',       'handler' => 'update',    'args' => [intval($_GET['id'] ?? 0)]],
    ['method' => 'POST',    'action' => 'delist',       'handler' => 'delist',    'args' => [intval($_GET['id'] ?? 0)]],
    ['method' => 'GET',     'action' => 'my_hosts',     'handler' => 'myHosts'],
    ['method' => 'GET',     'action' => 'my_listings',  'handler' => 'myListings'],
    ['method' => 'GET',     'action' => 'my_orders',    'handler' => 'myOrders'],
    ['method' => 'GET',     'action' => 'my_sales',     'handler' => 'mySales'],
    ['method' => 'POST',    'action' => 'favorite',     'handler' => 'favorite',  'args' => [intval($_GET['id'] ?? 0)]],
    ['method' => 'GET',     'action' => 'favorites',    'handler' => 'favorites'],
    ['method' => 'GET',     'action' => 'fields',       'handler' => 'fields'],
    ['method' => 'POST',    'action' => 'cancelOrder',  'handler' => 'cancelOrder'],
];

$matched = false;
foreach ($routes as $route) {
    if ($method === $route['method'] && $action === $route['action']) {
        $matched = true;
        $args = $route['args'] ?? [];
        try {
            $result = call_user_func_array([$controller, $route['handler']], $args);
            $data = is_string($result) ? $result : $result->getData();
            if (!is_string($data)) {
                $data = json_encode($data);
            }
            $app->response->content($data)->send();
        } catch (\Exception $e) {
            $app->response->content(json_encode([
                'status' => 500,
                'msg'    => 'Server Error: ' . $e->getMessage(),
            ]))->send();
        }
        break;
    }
}

if (!$matched) {
    $app->response->content(json_encode([
        'status' => 404,
        'msg'    => 'Unknown action: ' . htmlspecialchars($action),
        'available' => array_column($routes, 'action'),
    ]))->send();
}
