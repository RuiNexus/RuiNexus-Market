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
├── MarketPlugin.php             # 插件入口(install/uninstall/config)
├── MarketHooks.php              # 钩子处理(invoice_paid→自动转移)
├── config.php                   # 配置项定义
├── hooks.php                    # 钩子注册
├── menu.php                     # 后台菜单注册
├── controller/
│   └── AdminIndexController.php # 后台管理(商品列表/订单/手动上架/配置)
├── model/
│   └── MarketModel.php          # 数据模型(host转移/工具方法)
├── deploy/
│   ├── MarketApiController.php  # API控制器(部署时复制到 app/api/controller/)
│   └── market_api.php           # 独立API入口(部署时复制到 public/)
├── template/
│   └── admin/
│       ├── index.tpl            # 商品列表
│       ├── order.tpl            # 交易订单
│       ├── config.tpl           # 系统配置
│       └── manual_publish.tpl   # 手动上架
└── lang/
    ├── zh-cn.php                # 中文语言文件
    └── en-us.php                # 英文语言文件
```

## install() 流程
1. `runSql()` — 动态建表(含列级自动迁移)，不依赖 install.sql
2. `deployApi()` — 将 `deploy/MarketApiController.php` 复制到 `app/api/controller/`
3. `deployEntry()` — 将 `deploy/market_api.php` 复制到 `public/market_api.php`（独立入口，不依赖路由系统）
4. PluginLogic 自动注册钩子和后台菜单

## uninstall() 流程
1. `dropTables()` — **空操作**（保留数据，卸载重装不丢失）
2. `removeApi()` — 删除 `app/api/controller/MarketApiController.php`
3. `removeEntry()` — 删除 `public/market_api.php`
