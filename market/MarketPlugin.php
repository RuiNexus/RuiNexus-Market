<?php

namespace addons\market;

use app\admin\lib\Plugin;

class MarketPlugin extends Plugin
{
    public $info = [
        'name'        => 'Market',
        'title'       => 'RuiNexus Market',
        'description' => '二手服务器转卖交易市场',
        'status'      => 1,
        'author'      => 'RuiNexus/YeHuaiJing',
        'version'     => '1.12.2',
        'module'      => 'addons',
        'lang'        => [
            'chinese'     => 'RuiNexus Market',
            'chinese_tw'  => 'RuiNexus Market',
            'english'     => 'RuiNexus Market',
        ],
    ];

    public function install()
    {
        $this->runSql();

        $this->deployApi();

        $this->deployEntry();

        $this->migrateConfig();

        return true;
    }

    public function uninstall()
    {
        $this->dropTables();

        $this->removeApi();

        $this->removeEntry();

        return true;
    }

    public function getConfigOptions()
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
            'escrow_uid' => [
                'type'    => 'number',
                'name'    => '中间账户UID',
                'default' => '0',
                'desc'    => '上架后自动将产品转移到此账户，交易完成后转移给买家，取消上架后退回卖家。设为0则不启用',
            ],
            'notice_content' => [
                'type'    => 'textarea',
                'name'    => '公告内容',
                'default' => '',
                'desc'    => '前端页面公告区域显示的内容',
            ],
            'fee_to_escrow' => [
                'type'    => 'select',
                'name'    => '手续费入中间账户',
                'default' => '0',
                'options' => ['0' => '不启用', '1' => '启用'],
                'desc'    => '启用后，每笔交易的手续费将自动添加到中间账户余额，方便统计平台收入',
            ],
            'order_expire_minutes' => [
                'type'    => 'number',
                'name'    => '订单有效期(分钟)',
                'default' => '15',
                'desc'    => '买家下单后需在此时间内完成支付，超时订单自动取消并解锁商品',
            ],
        ];
    }

    private function runSql()
    {
        $DbConfig = \think\Db::getConfig();
        $prefix = $DbConfig['prefix'];

        $tableLista = \think\Db::query("SELECT table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA='" . $DbConfig['database'] . "'");
        $tableList = array_column($tableLista, 'table_name');

        $tables = [
            'market_config' => "CREATE TABLE `{$prefix}market_config` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `key` varchar(100) NOT NULL,
                `value` text,
                PRIMARY KEY (`id`),
                UNIQUE KEY `key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'market_listing' => "CREATE TABLE `{$prefix}market_listing` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `uid` int(11) NOT NULL COMMENT '卖家用户ID',
                `host_id` int(11) NOT NULL COMMENT '关联的host ID',
                `product_id` int(11) NOT NULL COMMENT '关联的产品ID',
                `title` varchar(255) NOT NULL COMMENT '标题',
                `description` text COMMENT '描述',
                `sale_price` decimal(10,2) NOT NULL COMMENT '售价',
                `host_domain` varchar(255) DEFAULT '' COMMENT '主机名',
                `host_os` varchar(255) DEFAULT '' COMMENT '操作系统',
                `host_ip` varchar(255) DEFAULT '' COMMENT '主IP',
                `host_port` int(11) DEFAULT '0' COMMENT '端口',
                `product_name` varchar(255) DEFAULT '' COMMENT '产品名称',
                `product_type` varchar(50) DEFAULT '' COMMENT '产品类型',
                `billing_cycle` varchar(100) DEFAULT '' COMMENT '付款周期',
                `nextduedate` int(11) DEFAULT '0' COMMENT '到期时间',
                `regdate` int(11) DEFAULT '0' COMMENT '开通时间',
                `original_amount` decimal(10,2) DEFAULT '0.00' COMMENT '原价',
                `status` tinyint(1) DEFAULT '0' COMMENT '0待审核1上架2已售3下架4删除',
                `is_featured` tinyint(1) DEFAULT '0' COMMENT '推荐',
                `sort_order` int(11) DEFAULT '0',
                `views` int(11) DEFAULT '0',
                `notes` text COMMENT '卖家备注',
                `create_time` int(11) DEFAULT '0',
                `update_time` int(11) DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `idx_uid` (`uid`),
                KEY `idx_host` (`host_id`),
                KEY `idx_product` (`product_id`),
                KEY `idx_status` (`status`),
                KEY `idx_price` (`sale_price`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'market_order' => "CREATE TABLE `{$prefix}market_order` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `listing_id` int(11) NOT NULL,
                `host_id` int(11) NOT NULL COMMENT '原始host ID',
                `seller_uid` int(11) NOT NULL,
                `buyer_uid` int(11) NOT NULL,
                `invoice_id` int(11) DEFAULT '0' COMMENT '关联账单ID',
                `amount` decimal(10,2) NOT NULL COMMENT '实际支付金额',
                `fee` decimal(10,2) DEFAULT '0.00' COMMENT '手续费',
                `seller_amount` decimal(10,2) DEFAULT '0.00' COMMENT '卖家实收',
                `pay_type` varchar(20) DEFAULT 'online' COMMENT 'online线上 offline线下',
                `status` tinyint(1) DEFAULT '0' COMMENT '0待付款1已付款2已转移3完成4取消5退款中6已退款',
                `remark` text,
                `create_time` int(11) DEFAULT '0',
                `expire_time` int(11) DEFAULT '0' COMMENT '订单过期时间',
                `pay_time` int(11) DEFAULT '0',
                `transfer_time` int(11) DEFAULT '0',
                `complete_time` int(11) DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `idx_listing` (`listing_id`),
                KEY `idx_seller` (`seller_uid`),
                KEY `idx_buyer` (`buyer_uid`),
                KEY `idx_host` (`host_id`),
                KEY `idx_invoice` (`invoice_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'market_favorite' => "CREATE TABLE `{$prefix}market_favorite` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `uid` int(11) NOT NULL,
                `listing_id` int(11) NOT NULL,
                `create_time` int(11) DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_uid_listing` (`uid`,`listing_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'market_config_field' => "CREATE TABLE `{$prefix}market_config_field` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `field_name` varchar(100) NOT NULL COMMENT '字段标识',
                `field_label` varchar(255) NOT NULL COMMENT '字段显示名',
                `field_type` varchar(20) NOT NULL DEFAULT 'input' COMMENT '字段类型: input/dropdown/radio/checkbox',
                `field_options` text COMMENT '选项(JSON)，dropdown/radio/checkbox使用',
                `sort_order` int(11) NOT NULL DEFAULT '0',
                `is_required` tinyint(1) NOT NULL DEFAULT '0',
                `create_time` int(11) DEFAULT '0',
                `update_time` int(11) DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `idx_sort` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];

        $columnDefs = [
            'market_config' => [
                "`id` int(11) NOT NULL AUTO_INCREMENT",
                "`key` varchar(100) NOT NULL",
                "`value` text",
            ],
            'market_listing' => [
                "`id` int(11) NOT NULL AUTO_INCREMENT",
                "`uid` int(11) NOT NULL COMMENT '卖家用户ID'",
                "`host_id` int(11) NOT NULL COMMENT '关联的host ID'",
                "`product_id` int(11) NOT NULL COMMENT '关联的产品ID'",
                "`title` varchar(255) NOT NULL COMMENT '标题'",
                "`description` text COMMENT '描述'",
                "`sale_price` decimal(10,2) NOT NULL COMMENT '售价'",
                "`host_domain` varchar(255) DEFAULT '' COMMENT '主机名'",
                "`host_os` varchar(255) DEFAULT '' COMMENT '操作系统'",
                "`host_ip` varchar(255) DEFAULT '' COMMENT '主IP'",
                "`host_port` int(11) DEFAULT '0' COMMENT '端口'",
                "`product_name` varchar(255) DEFAULT '' COMMENT '产品名称'",
                "`product_type` varchar(50) DEFAULT '' COMMENT '产品类型'",
                "`billing_cycle` varchar(100) DEFAULT '' COMMENT '付款周期'",
                "`nextduedate` int(11) DEFAULT '0' COMMENT '到期时间'",
                "`regdate` int(11) DEFAULT '0' COMMENT '开通时间'",
                "`original_amount` decimal(10,2) DEFAULT '0.00' COMMENT '原价'",
                "`status` tinyint(1) DEFAULT '0' COMMENT '0待审核1上架2已售3下架4删除'",
                "`is_featured` tinyint(1) DEFAULT '0' COMMENT '推荐'",
                "`sort_order` int(11) DEFAULT '0'",
                "`views` int(11) DEFAULT '0'",
                "`notes` text COMMENT '卖家备注'",
                "`create_time` int(11) DEFAULT '0'",
                "`update_time` int(11) DEFAULT '0'",
                "`spec_data` text COMMENT '自定义配置数据(JSON)'",
            ],
            'market_order' => [
                "`id` int(11) NOT NULL AUTO_INCREMENT",
                "`listing_id` int(11) NOT NULL",
                "`host_id` int(11) NOT NULL COMMENT '原始host ID'",
                "`seller_uid` int(11) NOT NULL",
                "`buyer_uid` int(11) NOT NULL",
                "`invoice_id` int(11) DEFAULT '0' COMMENT '关联账单ID'",
                "`amount` decimal(10,2) NOT NULL COMMENT '实际支付金额'",
                "`fee` decimal(10,2) DEFAULT '0.00' COMMENT '手续费'",
                "`seller_amount` decimal(10,2) DEFAULT '0.00' COMMENT '卖家实收'",
                "`pay_type` varchar(20) DEFAULT 'online' COMMENT 'online线上 offline线下'",
                "`status` tinyint(1) DEFAULT '0' COMMENT '0待付款1已付款2已转移3完成4取消5退款中6已退款'",
                "`remark` text",
                "`create_time` int(11) DEFAULT '0'",
                "`expire_time` int(11) DEFAULT '0' COMMENT '订单过期时间'",
                "`pay_time` int(11) DEFAULT '0'",
                "`transfer_time` int(11) DEFAULT '0'",
                "`complete_time` int(11) DEFAULT '0'",
            ],
            'market_favorite' => [
                "`id` int(11) NOT NULL AUTO_INCREMENT",
                "`uid` int(11) NOT NULL",
                "`listing_id` int(11) NOT NULL",
                "`create_time` int(11) DEFAULT '0'",
            ],
            'market_config_field' => [
                "`id` int(11) NOT NULL AUTO_INCREMENT",
                "`field_name` varchar(100) NOT NULL COMMENT '字段标识'",
                "`field_label` varchar(255) NOT NULL COMMENT '字段显示名'",
                "`field_type` varchar(20) NOT NULL DEFAULT 'input' COMMENT '字段类型: input/dropdown/radio/checkbox'",
                "`field_options` text COMMENT '选项(JSON)，dropdown/radio/checkbox使用'",
                "`sort_order` int(11) NOT NULL DEFAULT '0'",
                "`is_required` tinyint(1) NOT NULL DEFAULT '0'",
                "`create_time` int(11) DEFAULT '0'",
                "`update_time` int(11) DEFAULT '0'",
            ],
        ];

        foreach ($tables as $suffix => $createSql) {
            $tableName = $prefix . $suffix;
            if (!in_array($tableName, $tableList)) {
                \think\Db::execute($createSql);
            } else {
                $existing = \think\Db::query("SHOW COLUMNS FROM `{$tableName}`");
                $existingNames = array_column($existing, 'Field');
                foreach ($columnDefs[$suffix] as $colDef) {
                    preg_match('/`(\w+)`/', $colDef, $m);
                    if (!$m) continue;
                    $colName = $m[1];
                    if (!in_array($colName, $existingNames)) {
                        \think\Db::execute("ALTER TABLE `{$tableName}` ADD COLUMN {$colDef}");
                    }
                }
            }
        }
    }

    private function dropTables()
    {
    }

    private function migrateConfig()
    {
        $count = \think\Db::name('market_config')->count();
        if ($count > 0) {
            return;
        }

        $oldConfig = \think\Db::name('plugin')
            ->where('name', 'Market')->where('module', 'addons')
            ->value('config');

        if (empty($oldConfig) || $oldConfig === 'null') {
            return;
        }

        $oldConfig = json_decode($oldConfig, true);
        if (!is_array($oldConfig) || empty($oldConfig)) {
            return;
        }

        foreach ($oldConfig as $key => $value) {
            \think\Db::name('market_config')->insert([
                'key'   => $key,
                'value' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            ]);
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

    private function deployEntry()
    {
        $source = __DIR__ . '/deploy/market_api.php';
        $target = WEB_ROOT . 'market_api.php';
        if (file_exists($source)) {
            copy($source, $target);
        }
    }

    private function removeEntry()
    {
        $target = WEB_ROOT . 'market_api.php';
        if (file_exists($target)) {
            @unlink($target);
        }
    }
}
