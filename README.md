# RuiNexus Market - Plugin

> 魔方财务(zjmf-manger)二手服务器转卖插件

---

## ⚠️ 开发阶段

**本插件当前处于开发阶段（v1.x），可能存在功能不完善或有 Bug 的情况。**

- 生产环境使用前请充分测试
- 数据库结构可能随版本变更（安装时自动迁移）
- API 接口可能存在不兼容变更
- 发现任何问题请 [提交 Issue](https://github.com/RuiNexus/RuiNexus-Market/issues) 或联系开发者

### 已知功能限制（请务必注意）

| 功能 | 状态 | 配置建议 |
|---|---|---|
| 线下交易 | ⚠️ 可能无法正常使用 | 请**禁用**线下交易模式 |
| 手续费比例(%) | ⚠️ 可能无法正常使用 | 请设置为 **0** |
| 订单有效期(分钟) | ⚠️ 可能存在异常 | 保持默认值即可 |
| 联系邮箱 | ⚠️ 暂不支持 | 暂时无法通过插件配置 |
| 站点名称 | ⚠️ 暂不支持 | 暂时无法通过插件配置真实信息 |

---

## 简介

RuiNexus Market 是一个基于魔方财务系统的二手服务器转卖插件。卖家可以从自己账户下已有的服务器（host）中选择上架售卖，买家可通过独立前端站点浏览和购买。

### 核心特性

- **零依赖交付**：自行实现产品转移逻辑，不依赖第三方插件
- **中间账户托管**：上架后产品自动转入中间账户，防卖家篡改；付款后自动转给买家
- **自定义配置字段**：支持文本框/下拉/单选/多选/数字等动态配置字段
- **购买锁定机制**：下单后商品锁定，过期未支付自动解锁
- **线上+线下双模式**：线上走魔方支付网关，线下标记后管理员确认
- **审核机制**：后台可配置需审核或直接上架
- **产品黑名单**：禁止特定 product_id 的商品交易
- **手续费**：可配置百分比，支持入中间账户余额
- **JWT 认证**：API 使用 JWT + Cache 双重验证，安全可靠

---

## 安装

1. 将 `market` 目录放入魔方财务的 `public/plugins/addons/` 下
2. 在魔方后台「插件管理」中找到「RuiNexus Market」并启用
3. 安装过程自动完成：
   - 创建/迁移数据库表
   - 复制 API 控制器到 `app/api/controller/MarketApiController.php`
   - 复制独立 API 入口到 `public/market_api.php`

### 卸载

卸载插件**不会删除数据库表**，保留所有交易数据。重装时自动检测并迁移表结构。

---

## 配置

进入魔方后台 → 插件管理 → RuiNexus Market → 配置：

| 配置项 | 说明 |
|---|---|
| 站点名称 | 前端展示的站点名 |
| 手续费比例(%) | 从交易金额中扣除的手续费 |
| 需审核才上架 | 0-直接上架 / 1-需管理员审核 |
| 产品黑名单 | 禁止交易的 product_id 列表（逗号分隔） |
| 中间账户 UID | 托管账户的用户 ID（0=不启用托管） |
| 手续费入中间账户 | 是否将手续费转入中间账户余额 |
| 订单有效期(分钟) | 未支付订单自动取消时间 |
| 公告 | 前端站点顶部公告 |

---

## 文件结构

```
market/
├── MarketPlugin.php             # 插件入口（install/uninstall/config）
├── MarketHooks.php              # 钩子处理（支付回调/订单取消等）
├── config.php                   # 配置项定义
├── hooks.php                    # 钩子注册
├── menu.php                     # 后台菜单注册
├── API.md                       # API 接口文档
├── controller/
│   └── AdminIndexController.php # 后台管理（商品列表/订单/配置/手动上架/JWT校验）
├── model/
│   └── MarketModel.php          # 数据模型（host转移/配置读写/工具方法）
├── deploy/
│   ├── MarketApiController.php  # API控制器（部署时复制到 app/api/controller/）
│   └── market_api.php           # 独立API入口（部署时复制到 public/）
├── template/
│   └── admin/
│       ├── index.tpl            # 商品列表（审核/推荐/删除/编辑配置/编辑备注）
│       ├── order.tpl            # 交易订单（筛选/取消订单）
│       ├── config.tpl           # 系统配置 + 自定义配置字段管理
│       └── manual_publish.tpl   # 手动上架
└── lang/
    ├── zh-cn.php                # 中文语言文件
    └── en-us.php                # 英文语言文件
```

---

## 数据库表

| 表名 | 说明 |
|---|---|
| `market_config` | 插件配置（key-value，独立于魔方 plugin 表） |
| `market_config_field` | 自定义配置字段定义 |
| `market_listing` | 商品信息 |
| `market_order` | 交易订单 |
| `market_favorite` | 收藏记录 |

---

## 前端

前端独立部署在另一个仓库：[RuiNexus-Market-Frontend](https://github.com/RuiNexus/RuiNexus-Market-Frontend)

---

## 开发者

RuiNexus / YeHuaiJing

## Issues

问题反馈：[https://github.com/RuiNexus/RuiNexus-Market/issues](https://github.com/RuiNexus/RuiNexus-Market/issues)