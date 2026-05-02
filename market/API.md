# RuiNexus Market API 接口文档

> 版本: v1.9.2 | 开发者: RuiNexus / YeHuaiJing

---

## 基础信息

### 前端 API（面向买家/卖家）

- **入口**: `https://www.xxx.com/market_api.php?action={action}`
- **请求方式**: GET/POST（取决于接口）
- **响应格式**: JSON
- **响应结构**:
  ```json
  { "status": 200, "data": {...}, "msg": "..." }
  // 或
  { "status": 400, "msg": "错误信息" }
  ```
- **status 状态码**: `200` 成功 / `400` 参数错误 / `401` 需要登录 / `404` 不存在 / `500` 服务器错误

### 🔒 前端 API 认证方式（v1.9.0+）

所有需要登录的接口均采用 **JWT + Cache 双重验证**，7 层纵深防御：

| 层 | 校验项 | 说明 |
|----|--------|------|
| 1 | JWT 格式校验 | 必须为3段 `header.payload.signature` |
| 2 | Cache 存在检查 | `client_user_login_token_<jwt>` 必须存在 |
| 3 | JWT 签名解码 | HMAC-SHA256 + `config("jwtkey")`，防伪造 |
| 4 | Cache uid 匹配 | 缓存中的 uid 必须与 JWT 中一致 |
| 5 | 密码变更失效 | 改密后旧 JWT 自动作废 |
| 6 | IP 绑定（可选） | `home_ip_check=1` 时校验签发IP |
| 7 | 客户端状态检查 | `clients.status=1` 正常才放行 |

**JWT 传递方式**（二选一）：
- **Cookie**：同域时自动随请求发送（`userSetCookie()` 写入）
- **Authorization Header**：`Authorization: JWT <token>` 或 `Authorization: Bearer <token>`

> ⚠️ 不再支持 Session 或 `clients.token` 降级认证，必须持有有效 JWT。

### 后台 API（面向管理员）

- **入口**: 后台管理页面内 AJAX 调用 `shd_addon_url('market://AdminIndex/{method}')`
- **认证**: `PluginAdminBaseController` 框架级 Session 登录验证

---

## 前端 API（MarketApiController）

### 1. 系统配置 — `GET ?action=config` 🔓

> 🔓 无需登录

获取站点配置和自定义字段定义。

**请求参数**: 无

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "site_name": "RuiNexus Market",
    "allow_offline": 1,
    "notice_content": "欢迎来到二手服务器交易市场"
  },
  "spec_fields": [
    {
      "id": 1,
      "field_name": "cpu",
      "field_label": "CPU核心",
      "field_type": "number",
      "field_options": null,
      "sort_order": 0,
      "is_required": 1
    },
    {
      "id": 2,
      "field_name": "windows",
      "field_label": "是否支持Windows",
      "field_type": "radio",
      "field_options": ["是", "否"],
      "sort_order": 1,
      "is_required": 0
    }
  ]
}
```

---

### 2. 商品列表 — `GET ?action=list` 🔓

> 🔓 无需登录（登录后额外返回每条记录的 `is_favorited` 字段）

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| page | int | | 1 | 页码 |
| size | int | | 20 | 每页数量(最大50) |
| sort | string | | time_desc | time_desc / time_asc / price_asc / price_desc / remaining_asc / views_desc |
| keyword | string | | | 搜索关键词(标题) |
| product_type | string | | | 产品类型筛选 |
| price_min | float | | | 最低价 |
| price_max | float | | | 最高价 |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "total": 100,
    "page": 1,
    "size": 20,
    "list": [
      {
        "id": 7,
        "title": "测试服务器",
        "sale_price": "10.00",
        "spec_data": { "windows": "否", "cpu": "2" },
        "product_id": 1,
        "product_name": "测试服务器",
        "product_type": "server",
        "nextduedate": 1780354181,
        "regdate": 1720000000,
        "is_featured": 0,
        "views": 15,
        "create_time": 1740000000,
        "billing_cycle": "monthly",
        "original_amount": "100.00",
        "remaining_days": 30,
        "domainstatus": "Active",
        "is_favorited": false
      }
    ],
    "spec_labels": { "cpu": "CPU核心", "windows": "是否支持Windows" }
  }
}
```

> `remaining_days`: `null`=一次性/免费永久有效, `0`=已到期, `>0`=剩余天数

---

### 3. 商品详情 — `GET ?action=detail&id={id}` 🔓

> 🔓 无需登录（登录后额外返回 `is_favorited` 字段）

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "id": 7,
    "uid": 2,
    "host_id": 1,
    "product_id": 1,
    "title": "测试服务器",
    "description": "高性能服务器",
    "sale_price": "10.00",
    "spec_data": { "cpu": "4", "windows": "是" },
    "spec_labels": { "cpu": "CPU核心", "windows": "是否支持Windows" },
    "product_name": "测试服务器",
    "product_type": "server",
    "billing_cycle": "monthly",
    "nextduedate": 1780354181,
    "regdate": 1720000000,
    "original_amount": "100.00",
    "remaining_days": 30,
    "host_domainstatus": "Active",
    "seller_username": "yehuaijing",
    "is_favorited": false
  }
}
```

> 每次访问此接口，商品浏览次数 `views` 自动 +1

---

### 4. 购买商品 — `POST ?action=buy` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| listing_id | int | ✓ | 商品ID |
| pay_type | string | | online(默认) / offline |

**线上支付响应**:
```json
{
  "status": 200,
  "data": { "order_id": 1, "invoice_id": 10, "pay_type": "online", "pay_url": "https://www.xxx.com/viewbilling?id=10" }
}
```

**线下交易响应**:
```json
{
  "status": 200,
  "data": { "order_id": 1, "pay_type": "offline" },
  "msg": "下单成功，请联系卖家完成交易"
}
```

---

### 5. 发布商品 — `POST ?action=create` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| host_id | int | ✓ | 要出售的host记录ID |
| sale_price | float | ✓ | 售价 |
| title | string | | 标题(默认取产品名) |
| description | string | | 商品描述 |
| spec_data | JSON string | | 自定义配置数据 `{"cpu":"4核","ram":"16G"}` |

**响应示例**:
```json
{ "status": 200, "data": { "id": 8 }, "msg": "发布成功，等待管理员审核" }
```

> 如果后台 `need_audit=0`（直接上架），则 `msg` 为"发布成功"，且会自动将host转移到中间账户（如已配置）

---

### 6. 修改商品 — `POST ?action=update&id={id}` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID (URL传参) |
| title | string | | 标题 |
| description | string | | 描述 |
| sale_price | float | | 售价(>=0) |
| spec_data | JSON string | | 自定义配置数据 |

**状态限制**: 仅 `status=1`(上架中) 或 `status=3`(已下架) 可修改

**响应示例**:
```json
{ "status": 200, "msg": "修改成功" }
```

---

### 7. 下架商品 — `POST ?action=delist&id={id}` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID (URL传参) |

**状态限制**: 仅 `status=0`(待审核)、`status=1`(上架中)、`status=3`(已下架) 可下架

**响应示例**:
```json
{ "status": 200, "msg": "下架成功" }
```

> 下架后产品自动从中间账户退还给原卖家（如已配置托管）

---

### 8. 我的服务器列表 — `GET ?action=my_hosts` 🔒

> 🔒 需要 JWT 鉴权

列出当前用户 Active 状态的 host 记录（含黑名单过滤、已在售标记）。

**请求参数**: 无（自动识别登录用户）

**响应示例**:
```json
{
  "status": 200,
  "data": [
    {
      "id": 1,
      "productid": 1,
      "regdate": 1720000000,
      "nextduedate": 1780354181,
      "billingcycle": "monthly",
      "domainstatus": "Active",
      "product_name": "测试服务器",
      "product_type": "server",
      "is_on_sale": false,
      "original_amount": 100.00,
      "remaining_days": 30
    }
  ]
}
```

> `remaining_days`: `null`=一次性/免费永久有效, `0`=已到期, `>0`=剩余天数

---

### 9. 我发布的商品 — `GET ?action=my_listings` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| page | int | | 1 | 页码 |
| size | int | | 20 | 每页数量(最大50) |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "total": 5,
    "list": [
      {
        "id": 7,
        "title": "测试服务器",
        "sale_price": "10.00",
        "spec_data": { "cpu": "4" },
        "status": 1,
        "billing_cycle": "monthly",
        "nextduedate": 1780354181,
        "create_time": 1740000000
      }
    ],
    "spec_labels": { "cpu": "CPU核心" }
  }
}
```

---

### 10. 我购买的订单 — `GET ?action=my_orders` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| page | int | | 1 | 页码 |
| size | int | | 20 | 每页数量(最大50) |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "total": 3,
    "list": [
      {
        "id": 1,
        "listing_id": 7,
        "host_id": 1,
        "seller_uid": 2,
        "buyer_uid": 3,
        "invoice_id": 10,
        "amount": "10.00",
        "fee": "0.00",
        "seller_amount": "0.00",
        "pay_type": "online",
        "status": 1,
        "title": "测试服务器",
        "create_time": 1740000000
      }
    ]
  }
}
```

---

### 11. 我卖出的订单 — `GET ?action=my_sales` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| page | int | | 1 | 页码 |
| size | int | | 20 | 每页数量(最大50) |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "total": 5,
    "list": [
      {
        "id": 1,
        "listing_id": 7,
        "host_id": 1,
        "seller_uid": 2,
        "buyer_uid": 3,
        "amount": "10.00",
        "pay_type": "online",
        "status": 1,
        "title": "测试服务器",
        "create_time": 1740000000
      }
    ]
  }
}
```

---

### 12. 收藏/取消收藏 — `POST ?action=favorite&id={id}` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID (URL传参) |

**响应示例**:
```json
{ "status": 200, "data": { "favorited": true }, "msg": "收藏成功" }
// 或
{ "status": 200, "data": { "favorited": false }, "msg": "已取消收藏" }
```

---

### 13. 我的收藏列表 — `GET ?action=favorites` 🔒

> 🔒 需要 JWT 鉴权

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| page | int | | 1 | 页码 |
| size | int | | 20 | 每页数量(最大50) |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "total": 2,
    "list": [
      {
        "id": 7,
        "title": "测试服务器",
        "sale_price": "10.00",
        "spec_data": { "cpu": "4" },
        "billing_cycle": "monthly",
        "fav_time": 1740000000
      }
    ],
    "spec_labels": { "cpu": "CPU核心" }
  }
}
```

---

### 14. 自定义字段定义 — `GET ?action=fields` 🔓

> 🔓 无需登录

获取管理员配置的自定义字段定义列表。

**请求参数**: 无

**响应示例**:
```json
{
  "status": 200,
  "data": [
    {
      "id": 1,
      "field_name": "cpu",
      "field_label": "CPU核心",
      "field_type": "number",
      "field_options": null,
      "sort_order": 0,
      "is_required": 1
    }
  ]
}
```

---

## 后台 API（AdminIndexController）

> 路径格式: `shd_addon_url('market://AdminIndex/{method}')`

### A1. 商品列表 — `GET /AdminIndex/getList`

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| page | int | | 1 | 页码 |
| limit | int | | 20 | 每页数量(最大100) |
| status | int | | | 筛选: 0待审核 1上架中 2已售出 3已下架 |
| keyword | string | | | 搜索产品名称 |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "total": 50,
    "page": 1,
    "limit": 20,
    "list": [
      {
        "id": 7,
        "uid": 2,
        "seller": "yehuaijing",
        "title": "测试服务器",
        "sale_price": "10.00",
        "product_name": "测试服务器",
        "spec_data": { "cpu": "4" },
        "billing_cycle": "monthly",
        "nextduedate": 1780354181,
        "nextduedate_text": "2026/06/02",
        "is_featured": 0,
        "status": 1,
        "status_text": "上架中"
      }
    ],
    "spec_labels": { "cpu": "CPU核心" }
  }
}
```

> `nextduedate_text`: `"一次性"`=onetime/free产品, `"Y/m/d"`=有到期日, `""`=无数据

---

### A2. 订单列表 — `GET /AdminIndex/getOrders`

**请求参数**:

| 参数 | 类型 | 必填 | 默认 | 说明 |
|------|------|------|------|------|
| page | int | | 1 | 页码 |
| limit | int | | 20 | 每页数量(最大100) |
| status | int | | | 订单状态筛选 |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "total": 10,
    "page": 1,
    "limit": 20,
    "list": [
      {
        "id": 1,
        "listing_id": 7,
        "host_id": 1,
        "seller_uid": 2,
        "buyer_uid": 3,
        "amount": "10.00",
        "pay_type": "online",
        "status": 1,
        "listing_title": "测试服务器",
        "seller": "yehuaijing",
        "buyer": "buyer01",
        "status_text": "已完成",
        "pay_type_text": "线上支付"
      }
    ]
  }
}
```

---

### A3. 审核商品 — `POST /AdminIndex/audit`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID |
| action | string | ✓ | `pass` 通过 / `reject` 驳回 |
| reason | string | | 驳回原因 |

> 审核通过后自动将host转移至中间账户（如已配置escrow_uid）

**响应示例**:
```json
{ "status": 200, "msg": "审核通过" }
```

---

### A4. 推荐/取消推荐 — `POST /AdminIndex/feature`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID |

**响应示例**:
```json
{ "status": 200, "msg": "已推荐", "is_featured": 1 }
```

---

### A5. 删除商品 — `POST /AdminIndex/delete`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID |

> 软删除（status=4），已售出(status=2)不可删除。删除前自动退回中间账户产品。

**响应示例**:
```json
{ "status": 200, "msg": "已删除" }
```

---

### A6. 编辑配置信息 — `POST /AdminIndex/updateSpec`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID |
| spec_data | JSON string | | 配置数据 `{"cpu":"4核","ram":"16G"}` |

> 后端校验：必填字段非空、number字段必须为数字

**响应示例**:
```json
{ "status": 200, "msg": "配置信息已更新" }
```

---

### A7. 编辑卖家备注 — `POST /AdminIndex/updateNotes`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 商品ID |
| notes | string | | 备注内容 |

**响应示例**:
```json
{ "status": 200, "msg": "备注已更新" }
```

---

### A8. 保存系统配置 — `POST /AdminIndex/configPost`

**请求参数**:

| 参数 | 类型 | 说明 |
|------|------|------|
| site_name | string | 站点名称 |
| fee_percent | number | 手续费比例(%) |
| contact_email | string | 联系邮箱 |
| contact_qq | string | 联系QQ |
| need_audit | int | 0直接上架 / 1需要审核 |
| allow_offline | int | 0禁止 / 1允许线下交易 |
| escrow_uid | int | 中间账户UID(0不启用) |
| product_blacklist | string | 禁止交易的产品ID(逗号分隔) |
| notice_content | string | 公告内容 |

**响应示例**:
```json
{ "status": 200, "msg": "配置保存成功" }
```

---

### A9. 获取自定义字段列表 — `GET /AdminIndex/getFields`

**请求参数**: 无

**响应示例**:
```json
{
  "status": 200,
  "data": [
    {
      "id": 1,
      "field_name": "cpu",
      "field_label": "CPU核心",
      "field_type": "number",
      "field_options": null,
      "sort_order": 0,
      "is_required": 1
    }
  ]
}
```

---

### A10. 新增/编辑自定义字段 — `POST /AdminIndex/saveField`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | | 0=新增, >0=编辑 |
| field_name | string | ✓ | 字段标识(小写字母开头，如 cpu) |
| field_label | string | ✓ | 显示名(如 CPU核心) |
| field_type | string | | input / number / dropdown / radio / checkbox |
| field_options | string | | 选项(每行一个，仅 dropdown/radio/checkbox 需要) |
| sort_order | int | | 排序 |
| is_required | int | | 0否 / 1必填 |

**字段类型说明**:

| 类型 | 说明 | 需要选项 |
|------|------|------|
| input | 文本框 | |
| number | 数字输入(仅允许数字) | |
| dropdown | 下拉选择 | ✓ |
| radio | 单选 | ✓ |
| checkbox | 多选 | ✓ |

**响应示例**:
```json
{ "status": 200, "msg": "保存成功" }
```

---

### A11. 删除自定义字段 — `POST /AdminIndex/deleteField`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| id | int | ✓ | 字段ID |

**响应示例**:
```json
{ "status": 200, "msg": "删除成功" }
```

---

### A12. 搜索用户Host列表 — `GET /AdminIndex/searchUserHosts`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| uid | int | ✓ | 用户ID |

**响应示例**:
```json
{
  "status": 200,
  "data": {
    "user": { "id": 2, "username": "yehuaijing", "email": "test@example.com" },
    "hosts": [
      {
        "id": 1,
        "domain": "example.com",
        "dedicatedip": "1.2.3.4",
        "os": "CentOS 7",
        "port": 22,
        "domainstatus": "Active",
        "productid": 1,
        "product_name": "测试服务器",
        "product_type": "server",
        "billing_cycle": "monthly",
        "original_amount": 100.00,
        "can_publish": true,
        "reason": ""
      }
    ]
  }
}
```

> `can_publish=false` 时，`reason` 说明原因（如"仅支持 Active 状态的服务器"、"已在出售中"）

---

### A13. 手动上架 — `POST /AdminIndex/doManualPublish`

**请求参数**:

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| uid | int | ✓ | 用户ID |
| host_id | int | ✓ | 主机ID |
| sale_price | float | ✓ | 售价 |
| description | string | | 描述 |
| spec_data | JSON string | | 自定义配置数据 |

> 手动上架跳过审核，直接设为 status=1（上架中），并自动转移host到中间账户

**响应示例**:
```json
{ "status": 200, "data": { "id": 8 }, "msg": "上架成功" }
```

---

## 商品状态码

| status | 含义 | 说明 |
|--------|------|------|
| 0 | 待审核 | 需管理员审核通过后上架 |
| 1 | 上架中 | 正在出售 |
| 2 | 已售出 | 交易已完成 |
| 3 | 已下架 | 卖家自行下架或管理员驳回 |
| 4 | 已删除 | 软删除(不在列表中显示) |

---

## 订单状态码

| status | 含义 |
|--------|------|
| 0 | 待付款/待确认 |
| 1 | 已完成 |
| 2 | 已取消 |
