CREATE TABLE IF NOT EXISTS `sh_market_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `key` varchar(100) NOT NULL,
    `value` text,
    PRIMARY KEY (`id`),
    UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sh_market_listing` (
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
    `create_time` int(11) DEFAULT '0',
    `update_time` int(11) DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_uid` (`uid`),
    KEY `idx_host` (`host_id`),
    KEY `idx_product` (`product_id`),
    KEY `idx_status` (`status`),
    KEY `idx_price` (`sale_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sh_market_order` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `listing_id` int(11) NOT NULL,
    `host_id` int(11) NOT NULL COMMENT '原始host ID',
    `seller_uid` int(11) NOT NULL,
    `buyer_uid` int(11) NOT NULL,
    `invoice_id` int(11) DEFAULT '0' COMMENT '关联账单ID',
    `amount` decimal(10,2) NOT NULL COMMENT '实际支付金额',
    `fee` decimal(10,2) DEFAULT '0.00' COMMENT '手续费',
    `seller_amount` decimal(10,2) DEFAULT '0.00' COMMENT '卖家实收',
    `pay_type` varchar(20) DEFAULT 'online' COMMENT 'online线下 offline线上',
    `status` tinyint(1) DEFAULT '0' COMMENT '0待付款1已付款2已转移3完成4取消5退款中6已退款',
    `remark` text,
    `create_time` int(11) DEFAULT '0',
    `pay_time` int(11) DEFAULT '0',
    `transfer_time` int(11) DEFAULT '0',
    `complete_time` int(11) DEFAULT '0',
    PRIMARY KEY (`id`),
    KEY `idx_listing` (`listing_id`),
    KEY `idx_seller` (`seller_uid`),
    KEY `idx_buyer` (`buyer_uid`),
    KEY `idx_host` (`host_id`),
    KEY `idx_invoice` (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sh_market_favorite` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `uid` int(11) NOT NULL,
    `listing_id` int(11) NOT NULL,
    `create_time` int(11) DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_uid_listing` (`uid`,`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
