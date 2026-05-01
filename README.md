# RuiNexus Market - Plugin

二手服务器转卖插件，用于魔方财务系统(zjmf-manger)。

## 开发者
RuiNexus / YeHuaiJing

## 安装
将 `market` 目录放入 `public/plugins/addons/` 下，然后在魔方后台插件管理中安装。

## 功能
- 卖家可从账户中已有的服务器(product/host)选择上架售卖
- 支持线上支付(走魔方支付网关)和线下交易
- 付款后自动通过魔方产品转移功能将服务器转给买家
- 后台可配置审核机制、手续费比例、禁止交易的产品ID
- 独立前端站点通过 API 调用展示
