<?php

namespace addons\market;

class Market
{
    public $info = [
        'name'        => 'Market',
        'title'       => 'RuiNexus Market',
        'description' => '二手服务器转卖交易市场',
        'author'      => 'RuiNexus/YeHuaiJing',
        'version'     => '1.0.0',
        'module'      => 'addons',
    ];

    public $hasAdmin = 1;

    public function install()
    {
        $this->runSql();

        $this->deployApi();

        $this->registerRoutes();

        return true;
    }

    public function uninstall()
    {
        $this->dropTables();

        $this->removeApi();

        $this->removeRoutes();

        return true;
    }

    public function getConfig()
    {
        return [
            'site_name' => [
                'type'    => 'text',
                'name'    => '站点名称',
                'default' => 'RuiNexus Market',
                'desc'    => '前端页面显示的站点名称',
            ],
            'contact_email' => [
                'type'    => 'text',
                'name'    => '联系邮箱',
                'default' => '',
                'desc'    => '买家联系卖家的邮箱',
            ],
            'contact_qq' => [
                'type'    => 'text',
                'name'    => '联系QQ',
                'default' => '',
                'desc'    => '买家联系卖家的QQ',
            ],
            'need_audit' => [
                'type'    => 'select',
                'name'    => '上架审核',
                'default' => '1',
                'options' => ['0' => '直接上架', '1' => '需要审核'],
                'desc'    => '用户发布的服务器是否需要管理员审核',
            ],
            'allow_offline' => [
                'type'    => 'select',
                'name'    => '线下交易',
                'default' => '1',
                'options' => ['0' => '禁止', '1' => '允许'],
                'desc'    => '是否允许买家选择线下交易',
            ],
            'fee_percent' => [
                'type'    => 'number',
                'name'    => '手续费比例(%)',
                'default' => '5',
                'desc'    => '平台从每笔交易中抽取的手续费百分比',
            ],
            'product_blacklist' => [
                'type'    => 'text',
                'name'    => '禁止交易的产品ID',
                'default' => '',
                'desc'    => '逗号分隔，这些产品下的host禁止上架交易',
            ],
            'notice_content' => [
                'type'    => 'textarea',
                'name'    => '公告内容',
                'default' => '',
                'desc'    => '前端页面公告区域显示的内容',
            ],
        ];
    }

    public function getDefaultConfig()
    {
        $config = [];
        foreach ($this->getConfig() as $key => $item) {
            $config[$key] = $item['default'] ?? '';
        }
        return $config;
    }

    private function runSql()
    {
        $sqlFile = __DIR__ . '/sql/install.sql';
        if (!file_exists($sqlFile)) {
            return;
        }
        $sql = file_get_contents($sqlFile);
        $sql = str_replace("\r", "\n", $sql);
        $statements = explode(";\n", $sql);
        foreach ($statements as $stm) {
            $stm = trim($stm);
            if ($stm === '') {
                continue;
            }
            \think\Db::execute($stm);
        }
    }

    private function dropTables()
    {
        $tables = [
            'sh_market_favorite',
            'sh_market_order',
            'sh_market_listing',
            'sh_market_config',
        ];
        foreach ($tables as $table) {
            \think\Db::execute("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    private function deployApi()
    {
        $source = __DIR__ . '/deploy/MarketApiController.php';
        $target = CMF_ROOT . 'app/api/controller/MarketApiController.php';
        if (file_exists($source)) {
            copy($source, $target);
        }
    }

    private function removeApi()
    {
        $target = CMF_ROOT . 'app/api/controller/MarketApiController.php';
        if (file_exists($target)) {
            @unlink($target);
        }
    }

    private function registerRoutes()
    {
        $routeFile = CMF_DATA . 'route/api.php';
        if (!file_exists($routeFile)) {
            return;
        }
        $content = file_get_contents($routeFile);

        if (strpos($content, 'api/market') !== false) {
            return;
        }

        $routes = <<<'PHP'

// === RuiNexus Market API ===
think\facade\Route::get("api/market/config", "api/marketApi/config");
think\facade\Route::get("api/market/list", "api/marketApi/list");
think\facade\Route::get("api/market/detail/:id", "api/marketApi/detail");
think\facade\Route::post("api/market/buy", "api/marketApi/buy");
think\facade\Route::post("api/market/create", "api/marketApi/create");
think\facade\Route::put("api/market/update/:id", "api/marketApi/update");
think\facade\Route::post("api/market/delist/:id", "api/marketApi/delist");
think\facade\Route::get("api/market/my_hosts", "api/marketApi/myHosts");
think\facade\Route::get("api/market/my_listings", "api/marketApi/myListings");
think\facade\Route::get("api/market/my_orders", "api/marketApi/myOrders");
think\facade\Route::get("api/market/my_sales", "api/marketApi/mySales");
think\facade\Route::post("api/market/favorite/:id", "api/marketApi/favorite");
think\facade\Route::get("api/market/favorites", "api/marketApi/favorites");
PHP;

        $content = str_replace("?>\n", $routes . "\n", $content);
        file_put_contents($routeFile, $content);
    }

    private function removeRoutes()
    {
        $routeFile = CMF_DATA . 'route/api.php';
        if (!file_exists($routeFile)) {
            return;
        }
        $content = file_get_contents($routeFile);

        $marker = '// === RuiNexus Market API ===';
        $pos = strpos($content, $marker);
        if ($pos === false) {
            return;
        }

        $endPos = strpos($content, "?>", $pos);
        $before = substr($content, 0, $pos);
        $before = rtrim($before) . "\n";
        $after = $endPos !== false ? substr($content, $endPos) : '';
        $content = $before . $after;

        file_put_contents($routeFile, $content);
    }
}
