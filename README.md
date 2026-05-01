# RuiNexus Market - Plugin

二手服务器转卖插件，用于魔方财务系统(zjmf-manger)。

## 开发者
RuiNexus / YeHuaiJing

## 仓库
https://github.com/RuiNexus/RuiNexus-Market

## 安装
将 `market` 目录放入魔方财务的 `public/plugins/addons/` 下，然后在魔方后台「插件管理」中安装。

## 功能
- 卖家从账户中已有的服务器(host)选择上架售卖，自动填充配置信息
- 支持线上支付(走魔方支付网关) + 线下交易双模式
- 付款后自动将服务器转移给买家（不需要 product_divert 插件）
- 后台可配置：是否审核 / 手续费比例 / 禁止交易的产品ID黑名单
- 独立前端站点通过 API 调用展示

## 文件结构
```
market/
├── Market.php                  # 插件入口(install/uninstall/config)
├── MarketHooks.php             # 钩子处理(invoice_paid→自动转移)
├── controller/
│   └── AdminController.php     # 后台管理(审核/推荐/删除/配置)
├── model/
│   └── MarketModel.php         # 数据模型
├── deploy/
│   └── MarketApiController.php # 将复制到 app/api/controller/
├── sql/
│   └── install.sql             # 建表 SQL(4张表)
├── lang/
│   └── zh-cn.php               # 中文语言文件
├── hooks.php                   # 钩子注册
└── menu.php                    # 后台菜单注册
```

## install() 流程
1. 执行建表 SQL (sh_market_config / listing / order / favorite)
2. 将 `deploy/MarketApiController.php` 复制到 `app/api/controller/`
3. 将路由规则写入 `data/route/api.php`
4. PluginLogic 自动注册钩子和后台菜单

## uninstall() 流程
1. 删除数据表
2. 删除 `app/api/controller/MarketApiController.php`
3. 从 `data/route/api.php` 移除路由
