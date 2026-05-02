<?php

namespace app\api\controller;

class MarketApiController
{
    private function getConfig()
    {
        $dbConfig = \think\Db::name('plugin')
            ->where('name', 'Market')->where('module', 'addons')
            ->value('config');
        $dbConfig = $dbConfig ? json_decode($dbConfig, true) : [];
        $Market = new \addons\market\MarketPlugin();
        return array_merge($Market->getDefaultConfig(), $dbConfig);
    }

    private function getUid()
    {
        $uid = cmf_get_current_user_id();
        if (!$uid) {
            $uid = session('user.id');
        }
        if (!$uid) {
            $token = input('token', '');
            if ($token) {
                $uid = \think\Db::name('clients')->where('token', $token)->value('id');
            }
        }
        return intval($uid);
    }

    private function needLogin()
    {
        $uid = $this->getUid();
        if (!$uid) {
            echo json_encode(['status' => 401, 'msg' => '请先登录']);
            exit;
        }
        return $uid;
    }

    public function config()
    {
        $config = $this->getConfig();
        $safeConfig = [
            'site_name'      => $config['site_name'] ?? 'RuiNexus Market',
            'allow_offline'  => intval($config['allow_offline'] ?? 1),
            'notice_content' => $config['notice_content'] ?? '',
        ];

        $fields = \think\Db::name('market_config_field')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        foreach ($fields as &$f) {
            $f['field_options'] = $f['field_options'] ? json_decode($f['field_options'], true) : null;
        }

        return json(['status' => 200, 'data' => $safeConfig, 'spec_fields' => $fields]);
    }

    public function list()
    {
        $param = input();

        $page   = max(1, intval($param['page'] ?? 1));
        $size   = max(1, min(50, intval($param['size'] ?? 20)));
        $sort   = $param['sort'] ?? 'time_desc';
        $keyword  = $param['keyword'] ?? '';
        $region   = $param['region'] ?? '';
        $priceMin = floatval($param['price_min'] ?? 0);
        $priceMax = floatval($param['price_max'] ?? 0);
        $productType = $param['product_type'] ?? '';

        $where = [];
        $where[] = ['a.status', '=', 1];

        if ($keyword !== '') {
            $where[] = ['a.title', 'like', "%{$keyword}%"];
        }
        if ($region !== '') {
            $where[] = ['h.dcim_area', '=', $region];
        }
        if ($priceMin > 0) {
            $where[] = ['a.sale_price', '>=', $priceMin];
        }
        if ($priceMax > 0) {
            $where[] = ['a.sale_price', '<=', $priceMax];
        }
        if ($productType !== '') {
            $where[] = ['a.product_type', '=', $productType];
        }

        $orderMap = [
            'price_asc'    => ['a.sale_price', 'asc'],
            'price_desc'   => ['a.sale_price', 'desc'],
            'time_desc'    => ['a.create_time', 'desc'],
            'time_asc'     => ['a.create_time', 'asc'],
            'remaining_asc' => ['h.nextduedate', 'asc'],
            'views_desc'   => ['a.views', 'desc'],
        ];

        $order = $orderMap[$sort] ?? ['a.is_featured', 'desc', 'a.sort_order', 'desc', 'a.id', 'desc'];

        $total = \think\Db::name('market_listing')->alias('a')
            ->leftJoin('host h', 'a.host_id = h.id')
            ->where($where)->count();

        $list = \think\Db::name('market_listing')->alias('a')
            ->field('a.id,a.title,a.sale_price,a.spec_data,a.product_name,a.product_type,a.nextduedate,a.regdate,a.is_featured,a.views,a.create_time')
            ->leftJoin('host h', 'a.host_id = h.id')
            ->where($where)
            ->order($order[0] ?? 'a.is_featured', $order[1] ?? 'desc')
            ->order($order[2] ?? 'a.sort_order', $order[3] ?? 'desc')
            ->order('a.id', 'desc')
            ->page($page, $size)
            ->select()->toArray();

        $hosts = [];
        if ($list) {
            $hostIds = array_column($list, 'host_id');
            $hosts   = \think\Db::name('host')
                ->field('id,nextduedate,domainstatus,dedicatedip')
                ->whereIn('id', $hostIds)
                ->select()->toArray();
            $hosts = array_column($hosts, null, 'id');
        }

        $specLabels = \think\Db::name('market_config_field')
            ->column('field_label', 'field_name');

        foreach ($list as &$v) {
            $hostId = \think\Db::name('market_listing')->where('id', $v['id'])->value('host_id');
            $host   = $hosts[$hostId] ?? [];
            $v['remaining_days'] = 0;
            if (!empty($host['nextduedate']) && $host['nextduedate'] > time()) {
                $v['remaining_days'] = ceil(($host['nextduedate'] - time()) / 86400);
            }
            $v['domainstatus'] = $host['domainstatus'] ?? '';
            $v['spec_data'] = $v['spec_data'] ? json_decode($v['spec_data'], true) : null;
        }

        return json([
            'status' => 200,
            'data'   => [
                'total'  => $total,
                'page'   => $page,
                'size'   => $size,
                'list'   => $list,
                'spec_labels' => $specLabels,
            ],
        ]);
    }

    public function detail($id)
    {
        $id = intval($id);
        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->find();
        if (!$listing || $listing['status'] != 1) {
            return json(['status' => 404, 'msg' => '商品不存在或已下架']);
        }

        \think\Db::name('market_listing')->where('id', $id)->setInc('views', 1);

        $host = \think\Db::name('host')->where('id', $listing['host_id'])->find();
        $listing['remaining_days'] = 0;
        if ($host && $host['nextduedate'] > time()) {
            $listing['remaining_days'] = ceil(($host['nextduedate'] - time()) / 86400);
        }
        $listing['host_domainstatus'] = $host['domainstatus'] ?? '';
        $listing['spec_data'] = $listing['spec_data'] ? json_decode($listing['spec_data'], true) : null;
        $listing['spec_labels'] = \think\Db::name('market_config_field')
            ->column('field_label', 'field_name');

        $seller = \think\Db::name('clients')->field('id,username')
            ->where('id', $listing['uid'])->find();
        $listing['seller_username'] = $seller['username'] ?? '';

        $uid = $this->getUid();
        $listing['is_favorited'] = false;
        if ($uid) {
            $listing['is_favorited'] = \think\Db::name('market_favorite')
                ->where('uid', $uid)->where('listing_id', $id)->count() > 0;
        }

        return json(['status' => 200, 'data' => $listing]);
    }

    public function buy()
    {
        $uid = $this->needLogin();
        $listingId = intval(input('listing_id'));
        $payType   = input('pay_type', 'online');

        if ($listingId <= 0 || !in_array($payType, ['online', 'offline'])) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $listingId)->find();
        if (!$listing || $listing['status'] != 1) {
            return json(['status' => 400, 'msg' => '商品已下架或已售出']);
        }
        if ($listing['uid'] == $uid) {
            return json(['status' => 400, 'msg' => '不能购买自己的商品']);
        }

        $config = $this->getConfig();

        if ($payType == 'offline' && intval($config['allow_offline'] ?? 1) != 1) {
            return json(['status' => 400, 'msg' => '当前不支持线下交易']);
        }

        \think\Db::startTrans();
        try {
            $orderData = [
                'listing_id'  => $listingId,
                'host_id'     => $listing['host_id'],
                'seller_uid'  => $listing['uid'],
                'buyer_uid'   => $uid,
                'invoice_id'  => 0,
                'amount'      => $listing['sale_price'],
                'fee'         => 0,
                'seller_amount' => 0,
                'pay_type'    => $payType,
                'status'      => 0,
                'create_time' => time(),
            ];

            $orderId = \think\Db::name('market_order')->insertGetId($orderData);

            \think\Db::name('market_listing')->where('id', $listingId)->update([
                'status'      => 2,
                'update_time' => time(),
            ]);

            if ($payType == 'offline') {
                \think\Db::commit();
                return json([
                    'status' => 200,
                    'data'   => ['order_id' => $orderId, 'pay_type' => 'offline'],
                    'msg'    => '下单成功，请联系卖家完成交易',
                ]);
            }

            $invoiceData = [
                'uid'         => $uid,
                'create_time' => time(),
                'due_time'    => time(),
                'subtotal'    => $listing['sale_price'],
                'total'       => $listing['sale_price'],
                'status'      => 'Unpaid',
                'type'        => 'market',
                'notes'       => 'RuiNexus Market - ' . $listing['title'],
            ];

            $invoiceId = \think\Db::name('invoices')->insertGetId($invoiceData);

            $itemData = [
                'uid'        => $uid,
                'invoice_id' => $invoiceId,
                'rel_id'     => $listing['host_id'],
                'type'       => 'market',
                'description' => '二手服务器 - ' . $listing['title'],
                'amount'     => $listing['sale_price'],
                'due_time'   => strtotime('+1 day'),
            ];
            \think\Db::name('invoice_items')->insert($itemData);

            \think\Db::name('market_order')->where('id', $orderId)
                ->update(['invoice_id' => $invoiceId]);

            \think\Db::commit();

            $rootUrl = getRootUrl();
            return json([
                'status' => 200,
                'data'   => [
                    'order_id'   => $orderId,
                    'invoice_id' => $invoiceId,
                    'pay_type'   => 'online',
                    'pay_url'    => rtrim($rootUrl, '/') . '/viewbilling?id=' . $invoiceId,
                ],
            ]);

        } catch (\Exception $e) {
            \think\Db::rollback();
            return json(['status' => 400, 'msg' => '下单失败: ' . $e->getMessage()]);
        }
    }

    public function create()
    {
        $uid = $this->needLogin();
        $hostId = intval(input('host_id'));

        if ($hostId <= 0) {
            return json(['status' => 400, 'msg' => '请选择要出售的服务器']);
        }

        $host = \think\Db::name('host')->where('id', $hostId)->where('uid', $uid)->find();
        if (!$host) {
            return json(['status' => 400, 'msg' => '服务器不存在或不属于您']);
        }
        if ($host['domainstatus'] != 'Active') {
            return json(['status' => 400, 'msg' => '只能出售状态为 Active 的服务器']);
        }

        $config = $this->getConfig();
        $blacklist = array_filter(array_map('intval', explode(',', $config['product_blacklist'] ?? '')));
        if (in_array($host['productid'], $blacklist)) {
            return json(['status' => 400, 'msg' => '该产品类型不允许在市场上交易']);
        }

        $exists = \think\Db::name('market_listing')
            ->where('host_id', $hostId)
            ->whereIn('status', [0, 1])
            ->count();
        if ($exists > 0) {
            return json(['status' => 400, 'msg' => '该服务器已在出售中']);
        }

        $product = \think\Db::name('products')
            ->field('name,type,pay_type')
            ->where('id', $host['productid'])->find();

        $price = \think\Db::name('pricing')
            ->where('type', 'product')
            ->where('relid', $host['productid'])
            ->find();

        $originalAmount = 0;
        if ($price && floatval($price['monthly'] ?? 0) > 0) {
            $originalAmount = floatval($price['monthly']);
        } elseif ($price) {
            $originalAmount = floatval($price['onetime'] ?? 0);
        }

        $title       = input('title', $product['name'] ?? '');
        $description = input('description', '');
        $salePrice   = floatval(input('sale_price', 0));

        if ($salePrice <= 0) {
            return json(['status' => 400, 'msg' => '请输入有效的售价']);
        }

        $specData = input('spec_data', '', null);
        if ($specData !== '' && is_string($specData)) {
            $decoded = json_decode($specData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $specData = $decoded;
            }
        } elseif (is_array($specData)) {
        } else {
            $specData = null;
        }

        $needAudit = intval($config['need_audit'] ?? 1);
        $initialStatus = $needAudit ? 0 : 1;

        $payType = json_decode($product['pay_type'] ?? '{}', true);
        $billingCycle = '';
        if ($payType && isset($payType['pay_type'])) {
            $billingCycle = $payType['pay_type'];
        }

        $listingData = [
            'uid'             => $uid,
            'host_id'         => $hostId,
            'product_id'      => $host['productid'],
            'title'           => $title,
            'description'     => $description,
            'sale_price'      => $salePrice,
            'spec_data'       => $specData ? json_encode($specData, JSON_UNESCAPED_UNICODE) : '',
            'product_name'    => $product['name'] ?? '',
            'product_type'    => $product['type'] ?? '',
            'billing_cycle'   => $billingCycle,
            'nextduedate'     => intval($host['nextduedate'] ?? 0),
            'regdate'         => intval($host['regdate'] ?? 0),
            'original_amount' => $originalAmount,
            'status'          => $initialStatus,
            'create_time'     => time(),
            'update_time'     => time(),
        ];

        $id = \think\Db::name('market_listing')->insertGetId($listingData);

        if ($initialStatus == 1) {
            $escrowUid = \addons\market\model\MarketModel::getEscrowUid();
            if ($escrowUid > 0 && $escrowUid != $uid) {
                try {
                    \addons\market\model\MarketModel::transferHost($hostId, $escrowUid);
                } catch (\Exception $e) {
                }
            }
        }

        $msg = $needAudit ? '发布成功，等待管理员审核' : '发布成功';
        return json(['status' => 200, 'data' => ['id' => $id], 'msg' => $msg]);
    }

    public function update($id)
    {
        $uid = $this->needLogin();
        $id  = intval($id);

        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->where('uid', $uid)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
        }
        if (!in_array($listing['status'], [1, 3])) {
            return json(['status' => 400, 'msg' => '当前状态不允许修改']);
        }

        $update = [];
        $title = input('title', '');
        if ($title !== '') {
            $update['title'] = $title;
        }
        $description = input('description', '');
        if ($description !== '') {
            $update['description'] = $description;
        }
        $salePrice = floatval(input('sale_price', -1));
        if ($salePrice >= 0) {
            $update['sale_price'] = $salePrice;
        }
        $specData = input('spec_data', '', null);
        if ($specData !== '') {
            if (is_string($specData)) {
                $decoded = json_decode($specData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $specData = $decoded;
                }
            }
            if (is_array($specData)) {
                $update['spec_data'] = json_encode($specData, JSON_UNESCAPED_UNICODE);
            }
        }

        if (empty($update)) {
            return json(['status' => 400, 'msg' => '没有需要修改的内容']);
        }

        $update['update_time'] = time();
        \think\Db::name('market_listing')->where('id', $id)->update($update);

        return json(['status' => 200, 'msg' => '修改成功']);
    }

    public function delist($id)
    {
        $uid = $this->needLogin();
        $id  = intval($id);

        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->where('uid', $uid)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
        }
        if (!in_array($listing['status'], [0, 1, 3])) {
            return json(['status' => 400, 'msg' => '当前状态不允许下架']);
        }

        \think\Db::name('market_listing')->where('id', $id)->update([
            'status'      => 3,
            'update_time' => time(),
        ]);

        if (in_array($listing['status'], [0, 1])) {
            $escrowUid = \addons\market\model\MarketModel::getEscrowUid();
            if ($escrowUid > 0 && $escrowUid != $uid) {
                try {
                    \addons\market\model\MarketModel::transferHost($listing['host_id'], $uid);
                } catch (\Exception $e) {
                }
            }
        }

        return json(['status' => 200, 'msg' => '下架成功']);
    }

    public function myHosts()
    {
        $uid = $this->needLogin();

        $config = $this->getConfig();
        $blacklist = array_filter(array_map('intval', explode(',', $config['product_blacklist'] ?? '')));

        $escrowHostIds = \think\Db::name('market_listing')
            ->where('uid', $uid)
            ->whereIn('status', [0, 1])
            ->column('host_id');

        $hosts = \think\Db::name('host')->alias('h')
            ->field('h.id,h.productid,h.regdate,h.nextduedate,h.domainstatus,p.name as product_name,p.type as product_type')
            ->leftJoin('products p', 'h.productid = p.id')
            ->where(function ($query) use ($uid, $escrowHostIds) {
                $query->where('h.uid', $uid)->where('h.domainstatus', 'Active');
                if ($escrowHostIds) {
                    $query->whereOr('h.id', 'in', $escrowHostIds);
                }
            })
            ->select()->toArray();

        if ($blacklist) {
            $hosts = array_filter($hosts, function ($h) use ($blacklist) {
                return !in_array($h['productid'], $blacklist);
            });
        }

        $onSaleHostIds = \think\Db::name('market_listing')
            ->whereIn('status', [0, 1])
            ->column('host_id');

        foreach ($hosts as &$h) {
            $h['is_on_sale'] = in_array($h['id'], $onSaleHostIds);

            $price = \think\Db::name('pricing')
                ->where('type', 'product')
                ->where('relid', $h['productid'])
                ->find();
            $h['original_amount'] = 0;
            if ($price && floatval($price['monthly'] ?? 0) > 0) {
                $h['original_amount'] = floatval($price['monthly']);
            } elseif ($price) {
                $h['original_amount'] = floatval($price['onetime'] ?? 0);
            }

            if ($h['nextduedate'] > time()) {
                $h['remaining_days'] = ceil(($h['nextduedate'] - time()) / 86400);
            } else {
                $h['remaining_days'] = 0;
            }
        }

        return json(['status' => 200, 'data' => array_values($hosts)]);
    }

    public function myListings()
    {
        $uid = $this->needLogin();
        $page = max(1, intval(input('page', 1)));
        $size = max(1, min(50, intval(input('size', 20))));

        $total = \think\Db::name('market_listing')
            ->where('uid', $uid)->where('status', '<>', 4)->count();
        $list  = \think\Db::name('market_listing')
            ->where('uid', $uid)->where('status', '<>', 4)
            ->order('id', 'desc')->page($page, $size)->select()->toArray();

        $specLabels = \think\Db::name('market_config_field')
            ->column('field_label', 'field_name');

        foreach ($list as &$v) {
            $v['spec_data'] = $v['spec_data'] ? json_decode($v['spec_data'], true) : null;
        }

        return json(['status' => 200, 'data' => ['total' => $total, 'list' => $list, 'spec_labels' => $specLabels]]);
    }

    public function myOrders()
    {
        $uid  = $this->needLogin();
        $page = max(1, intval(input('page', 1)));
        $size = max(1, min(50, intval(input('size', 20))));

        $total = \think\Db::name('market_order')->alias('o')
            ->leftJoin('market_listing l', 'o.listing_id = l.id')
            ->where('o.buyer_uid', $uid)->count();
        $list  = \think\Db::name('market_order')->alias('o')
            ->field('o.*,l.title')
            ->leftJoin('market_listing l', 'o.listing_id = l.id')
            ->where('o.buyer_uid', $uid)
            ->order('o.id', 'desc')->page($page, $size)->select()->toArray();

        return json(['status' => 200, 'data' => ['total' => $total, 'list' => $list]]);
    }

    public function mySales()
    {
        $uid  = $this->needLogin();
        $page = max(1, intval(input('page', 1)));
        $size = max(1, min(50, intval(input('size', 20))));

        $total = \think\Db::name('market_order')->alias('o')
            ->leftJoin('market_listing l', 'o.listing_id = l.id')
            ->where('o.seller_uid', $uid)->count();
        $list  = \think\Db::name('market_order')->alias('o')
            ->field('o.*,l.title')
            ->leftJoin('market_listing l', 'o.listing_id = l.id')
            ->where('o.seller_uid', $uid)
            ->order('o.id', 'desc')->page($page, $size)->select()->toArray();

        return json(['status' => 200, 'data' => ['total' => $total, 'list' => $list]]);
    }

    public function favorite($id)
    {
        $uid = $this->needLogin();
        $id  = intval($id);

        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
        }

        $fav = \think\Db::name('market_favorite')
            ->where('uid', $uid)->where('listing_id', $id)->find();

        if ($fav) {
            \think\Db::name('market_favorite')->where('id', $fav['id'])->delete();
            return json(['status' => 200, 'data' => ['favorited' => false], 'msg' => '已取消收藏']);
        }

        \think\Db::name('market_favorite')->insert([
            'uid'         => $uid,
            'listing_id'  => $id,
            'create_time' => time(),
        ]);
        return json(['status' => 200, 'data' => ['favorited' => true], 'msg' => '收藏成功']);
    }

    public function favorites()
    {
        $uid  = $this->needLogin();
        $page = max(1, intval(input('page', 1)));
        $size = max(1, min(50, intval(input('size', 20))));

        $total = \think\Db::name('market_favorite')->alias('f')
            ->leftJoin('market_listing l', 'f.listing_id = l.id')
            ->where('f.uid', $uid)->where('l.status', 'in', [0, 1])->count();

        $list  = \think\Db::name('market_favorite')->alias('f')
            ->field('l.*,f.create_time as fav_time')
            ->leftJoin('market_listing l', 'f.listing_id = l.id')
            ->where('f.uid', $uid)->where('l.status', 'in', [0, 1])
            ->order('f.id', 'desc')->page($page, $size)->select()->toArray();

        $specLabels = \think\Db::name('market_config_field')
            ->column('field_label', 'field_name');

        foreach ($list as &$v) {
            $v['spec_data'] = $v['spec_data'] ? json_decode($v['spec_data'], true) : null;
        }

        return json(['status' => 200, 'data' => ['total' => $total, 'list' => $list, 'spec_labels' => $specLabels]]);
    }

    public function fields()
    {
        $fields = \think\Db::name('market_config_field')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        foreach ($fields as &$f) {
            $f['field_options'] = $f['field_options'] ? json_decode($f['field_options'], true) : null;
        }
        return json(['status' => 200, 'data' => $fields]);
    }
}
