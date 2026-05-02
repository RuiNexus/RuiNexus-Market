<?php

return [
    'site_name' => [
        'type'  => 'text',
        'name'  => '站点名称',
        'value' => 'RuiNexus Market',
        'desc'  => '前端页面显示的站点名称',
    ],
    'contact_email' => [
        'type'  => 'text',
        'name'  => '联系邮箱',
        'value' => '',
        'desc'  => '买家联系卖家的邮箱',
    ],
    'contact_qq' => [
        'type'  => 'text',
        'name'  => '联系QQ',
        'value' => '',
        'desc'  => '买家联系卖家的QQ',
    ],
    'need_audit' => [
        'type'    => 'select',
        'name'    => '上架审核',
        'value'   => '1',
        'options' => ['0' => '直接上架', '1' => '需要审核'],
        'desc'    => '用户发布的服务器是否需要管理员审核',
    ],
    'allow_offline' => [
        'type'    => 'select',
        'name'    => '线下交易',
        'value'   => '1',
        'options' => ['0' => '禁止', '1' => '允许'],
        'desc'    => '是否允许买家选择线下交易',
    ],
    'fee_percent' => [
        'type'  => 'number',
        'name'  => '手续费比例(%)',
        'value' => '5',
        'desc'  => '平台从每笔交易中抽取的手续费百分比',
    ],
    'product_blacklist' => [
        'type'  => 'text',
        'name'  => '禁止交易的产品ID',
        'value' => '',
        'desc'  => '逗号分隔，这些产品下的host禁止上架交易',
    ],
    'escrow_uid' => [
        'type'  => 'number',
        'name'  => '中间账户UID',
        'value' => '0',
        'desc'  => '上架后自动将产品转移到此账户，交易完成后转移给买家，取消上架后退回卖家。设为0则不启用',
    ],
    'notice_content' => [
        'type'  => 'textarea',
        'name'  => '公告内容',
        'value' => '',
        'desc'  => '前端页面公告区域显示的内容',
    ],
];
