<?php
namespace addons\market\controller;

use app\admin\controller\PluginAdminBaseController;

class AdminIndexController extends PluginAdminBaseController
{
    private $_config = [];
    private $lang;

    public function initialize()
    {
        parent::initialize();
        if (file_exists(dirname(__DIR__) . '/config/config.php')) {
            $con = require dirname(__DIR__) . '/config/config.php';
        } else {
            $con = [];
        }
        $this->_config = array_merge($con, $this->getPlugin()->getConfig());

        $lang = request()->languagesys;
        if (empty($lang)) {
            $lang = configuration('language') ? configuration('language') : config('default_lang');
        }
        if ($lang == 'CN') {
            $lang = 'chinese';
        } elseif ($lang == 'US') {
            $lang = 'english';
        } elseif ($lang == 'HK') {
            $lang = 'chinese_tw';
        }
        $this->lang = $lang;
    }

    public function index()
    {
        if ($this->lang == 'chinese') {
            $title = '商品列表';
        } elseif ($this->lang == 'english') {
            $title = 'Listings';
        } elseif ($this->lang == 'chinese_tw') {
            $title = '商品列表';
        }
        $this->assign('Title', $title);
        return $this->fetch('/index');
    }

    public function configure()
    {
        if ($this->lang == 'chinese') {
            $title = 'Market 配置';
        } elseif ($this->lang == 'english') {
            $title = 'Market Config';
        } elseif ($this->lang == 'chinese_tw') {
            $title = 'Market 配置';
        }
        $this->assign('Title', $title);
        $this->assign('config', $this->_config);

        $fields = \think\Db::name('market_config_field')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        $this->assign('fields', $fields);

        return $this->fetch('/config');
    }

    public function orders()
    {
        if ($this->lang == 'chinese') {
            $title = '交易订单';
        } elseif ($this->lang == 'english') {
            $title = 'Orders';
        } elseif ($this->lang == 'chinese_tw') {
            $title = '交易訂單';
        }
        $this->assign('Title', $title);
        return $this->fetch('/order');
    }

    public function getList()
    {
        $param = input();
        $page  = max(1, intval($param['page'] ?? 1));
        $limit = max(1, min(100, intval($param['limit'] ?? 20)));
        $status = isset($param['status']) && $param['status'] !== '' ? intval($param['status']) : null;
        $keyword = $param['keyword'] ?? '';

        $where = [];
        $where[] = ['status', '<>', 4];
        if ($status !== null) {
            $where[] = ['status', '=', $status];
        }
        if ($keyword !== '') {
            $where[] = ['product_name', 'like', "%{$keyword}%"];
        }

        $total = \think\Db::name('market_listing')->where($where)->count();
        $list  = \think\Db::name('market_listing')
            ->where($where)
            ->order('sort_order', 'desc')
            ->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $statusMap = [
            0 => lang('market_status_0'),
            1 => lang('market_status_1'),
            2 => lang('market_status_2'),
            3 => lang('market_status_3'),
        ];

        $specLabels = \think\Db::name('market_config_field')
            ->column('field_label', 'field_name');

        foreach ($list as &$v) {
            $v['status_text'] = $statusMap[$v['status']] ?? '';
            $v['seller'] = \think\Db::name('clients')
                ->where('id', $v['uid'])->value('username');
            $v['spec_data'] = $v['spec_data'] ? json_decode($v['spec_data'], true) : null;
            $v['spec_labels'] = $specLabels;
        }

        return json([
            'status' => 200,
            'data'   => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'list'  => $list,
                'spec_labels' => $specLabels,
            ],
        ]);
    }

    public function getOrders()
    {
        $param = input();
        $page  = max(1, intval($param['page'] ?? 1));
        $limit = max(1, min(100, intval($param['limit'] ?? 20)));
        $status = isset($param['status']) && $param['status'] !== '' ? intval($param['status']) : null;

        $where = [];
        if ($status !== null) {
            $where[] = ['o.status', '=', $status];
        }

        $total = \think\Db::name('market_order')->alias('o')
            ->leftJoin('market_listing l', 'o.listing_id = l.id')
            ->where($where)
            ->count();
        $list  = \think\Db::name('market_order')->alias('o')
            ->field('o.*,l.title as listing_title')
            ->leftJoin('market_listing l', 'o.listing_id = l.id')
            ->where($where)
            ->order('o.id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$v) {
            $v['seller']    = \think\Db::name('clients')->where('id', $v['seller_uid'])->value('username');
            $v['buyer']     = \think\Db::name('clients')->where('id', $v['buyer_uid'])->value('username');
            $v['status_text'] = lang('market_order_status_' . $v['status']);
            $v['pay_type_text'] = ($v['pay_type'] == 'offline') ? lang('market_pay_offline') : lang('market_pay_online');
        }

        return json([
            'status' => 200,
            'data'   => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'list'  => $list,
            ],
        ]);
    }

    public function audit()
    {
        $id  = intval(input('id'));
        $act = input('action');
        $reason = input('reason', '');

        if ($id <= 0 || !in_array($act, ['pass', 'reject'])) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
        }

        if ($listing['status'] != 0) {
            return json(['status' => 400, 'msg' => '只有待审核的商品可以操作']);
        }

        $newStatus = ($act === 'pass') ? 1 : 3;
        \think\Db::name('market_listing')->where('id', $id)->update([
            'status'      => $newStatus,
            'update_time' => time(),
        ]);

        if ($act === 'pass') {
            $escrowUid = \addons\market\model\MarketModel::getEscrowUid();
            if ($escrowUid > 0 && $escrowUid != $listing['uid']) {
                try {
                    \addons\market\model\MarketModel::transferHost($listing['host_id'], $escrowUid);
                } catch (\Exception $e) {
                }
            }
            return json(['status' => 200, 'msg' => lang('market_audit_pass')]);
        }
        return json(['status' => 200, 'msg' => lang('market_audit_reject')]);
    }

    public function feature()
    {
        $id = intval(input('id'));

        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
        }

        $newFeatured = $listing['is_featured'] ? 0 : 1;
        \think\Db::name('market_listing')->where('id', $id)->update([
            'is_featured' => $newFeatured,
            'update_time' => time(),
        ]);

        $msg = $newFeatured ? lang('market_featured_on') : lang('market_featured_off');
        return json(['status' => 200, 'msg' => $msg, 'is_featured' => $newFeatured]);
    }

    public function delete()
    {
        $id = intval(input('id'));

        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
        }

        if ($listing['status'] == 2) {
            return json(['status' => 400, 'msg' => '已售出的商品不能删除']);
        }

        if (in_array($listing['status'], [0, 1])) {
            $escrowUid = \addons\market\model\MarketModel::getEscrowUid();
            if ($escrowUid > 0 && $escrowUid != $listing['uid']) {
                try {
                    \addons\market\model\MarketModel::transferHost($listing['host_id'], $listing['uid']);
                } catch (\Exception $e) {
                }
            }
        }

        \think\Db::name('market_listing')->where('id', $id)->update([
            'status'      => 4,
            'update_time' => time(),
        ]);

        return json(['status' => 200, 'msg' => lang('market_deleted')]);
    }

    public function updateSpec()
    {
        $id = intval(input('id'));

        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
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
                $requiredFields = \think\Db::name('market_config_field')
                    ->where('is_required', 1)
                    ->column('field_label', 'field_name');
                foreach ($requiredFields as $fieldName => $fieldLabel) {
                    $val = $specData[$fieldName] ?? null;
                    if ($val === null || $val === '' || (is_array($val) && count($val) === 0)) {
                        return json(['status' => 400, 'msg' => '请填写必填项：' . $fieldLabel]);
                    }
                }
                $specData = json_encode($specData, JSON_UNESCAPED_UNICODE);
            }
        } else {
            $specData = '';
        }

        \think\Db::name('market_listing')->where('id', $id)->update([
            'spec_data'   => $specData,
            'update_time' => time(),
        ]);

        return json(['status' => 200, 'msg' => '配置信息已更新']);
    }

    public function updateNotes()
    {
        $id = intval(input('id'));

        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }

        $listing = \think\Db::name('market_listing')->where('id', $id)->find();
        if (!$listing) {
            return json(['status' => 400, 'msg' => '商品不存在']);
        }

        $notes = input('notes', '');

        \think\Db::name('market_listing')->where('id', $id)->update([
            'notes'       => $notes,
            'update_time' => time(),
        ]);

        return json(['status' => 200, 'msg' => '备注已更新']);
    }

    public function configPost()
    {
        $Market = new \addons\market\MarketPlugin();
        $configDef = $Market->getConfigOptions();

        $post = input('post.');
        $saveConfig = [];
        foreach ($configDef as $key => $def) {
            $saveConfig[$key] = $post[$key] ?? $def['default'] ?? '';
        }
        $saveJson = json_encode($saveConfig, JSON_UNESCAPED_UNICODE);

        \think\Db::name('plugin')
            ->where('name', 'Market')->where('module', 'addons')
            ->update(['config' => $saveJson]);

        return json(['status' => 200, 'msg' => lang('market_config_saved')]);
    }

    public function getFields()
    {
        $fields = \think\Db::name('market_config_field')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        foreach ($fields as &$f) {
            if ($f['field_options']) {
                $f['field_options'] = json_decode($f['field_options'], true);
            }
        }
        return json(['status' => 200, 'data' => $fields]);
    }

    public function saveField()
    {
        $id = intval(input('id', 0));
        $fieldName = input('field_name', '');
        $fieldLabel = input('field_label', '');
        $fieldType = input('field_type', 'input');
        $sortOrder = intval(input('sort_order', 0));
        $isRequired = intval(input('is_required', 0));

        if ($fieldName === '' || $fieldLabel === '') {
            return json(['status' => 400, 'msg' => '字段标识和显示名不能为空']);
        }
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $fieldName)) {
            return json(['status' => 400, 'msg' => '字段标识只能是小写字母、数字和下划线，且以字母开头']);
        }

        $optionsRaw = input('field_options', '');
        $fieldOptions = '';
        if (in_array($fieldType, ['dropdown', 'radio', 'checkbox']) && $optionsRaw !== '') {
            $lines = explode("\n", str_replace("\r\n", "\n", $optionsRaw));
            $lines = array_map('trim', $lines);
            $lines = array_filter($lines);
            $fieldOptions = json_encode(array_values($lines), JSON_UNESCAPED_UNICODE);
        }

        $now = time();
        $data = [
            'field_name'    => $fieldName,
            'field_label'   => $fieldLabel,
            'field_type'    => $fieldType,
            'field_options' => $fieldOptions,
            'sort_order'    => $sortOrder,
            'is_required'   => $isRequired,
            'update_time'   => $now,
        ];

        if ($id > 0) {
            \think\Db::name('market_config_field')->where('id', $id)->update($data);
        } else {
            $data['create_time'] = $now;
            \think\Db::name('market_config_field')->insert($data);
        }

        return json(['status' => 200, 'msg' => '保存成功']);
    }

    public function deleteField()
    {
        $id = intval(input('id', 0));
        if ($id <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }
        \think\Db::name('market_config_field')->where('id', $id)->delete();
        return json(['status' => 200, 'msg' => '删除成功']);
    }

    public function manualPublish()
    {
        if ($this->lang == 'chinese') {
            $title = '手动上架';
        } elseif ($this->lang == 'english') {
            $title = 'Manual Publish';
        } elseif ($this->lang == 'chinese_tw') {
            $title = '手動上架';
        }
        $this->assign('Title', $title);
        $this->assign('config', $this->_config);

        $fields = \think\Db::name('market_config_field')
            ->order('sort_order', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
        foreach ($fields as &$f) {
            if ($f['field_options']) {
                $f['field_options'] = json_decode($f['field_options'], true);
            }
        }
        $this->assign('specFields', $fields);

        return $this->fetch('/manual_publish');
    }

    public function searchUserHosts()
    {
        $uid = intval(input('uid'));

        if ($uid <= 0) {
            return json(['status' => 400, 'msg' => '请输入用户ID']);
        }

        $user = \think\Db::name('clients')->field('id,username,email')->where('id', $uid)->find();
        if (!$user) {
            return json(['status' => 400, 'msg' => '用户不存在']);
        }

        $config = $this->_config;
        $blacklist = array_filter(array_map('intval', explode(',', $config['product_blacklist'] ?? '')));

        $escrowHostIds = \think\Db::name('market_listing')
            ->where('uid', $uid)
            ->whereIn('status', [0, 1])
            ->column('host_id');

        $hosts = \think\Db::name('host')->alias('h')
            ->field('h.id,h.domain,h.dedicatedip,h.os,h.port,h.domainstatus,h.productid,h.regdate,h.nextduedate,p.name as product_name,p.type as product_type,p.pay_type')
            ->leftJoin('products p', 'h.productid = p.id')
            ->where(function ($query) use ($uid, $escrowHostIds) {
                $query->where('h.uid', $uid);
                if ($escrowHostIds) {
                    $query->whereOr('h.id', 'in', $escrowHostIds);
                }
            })
            ->select()
            ->toArray();

        foreach ($hosts as &$host) {
            $host['can_publish'] = true;
            $host['reason']      = '';

            if ($host['domainstatus'] != 'Active') {
                $host['can_publish'] = false;
                $host['reason']      = '仅支持 Active 状态的服务器';
            }
            if (in_array($host['productid'], $blacklist)) {
                $host['can_publish'] = false;
                $host['reason']      = '该产品类型禁止交易';
            }

            $exists = \think\Db::name('market_listing')
                ->where('host_id', $host['id'])
                ->whereIn('status', [0, 1])
                ->count();
            if ($exists > 0) {
                $host['can_publish'] = false;
                $host['reason']      = '已在出售中';
            }

            $price = \think\Db::name('pricing')
                ->where('type', 'product')
                ->where('relid', $host['productid'])
                ->find();
            $host['original_amount'] = 0;
            if ($price && floatval($price['monthly'] ?? 0) > 0) {
                $host['original_amount'] = floatval($price['monthly']);
            } elseif ($price) {
                $host['original_amount'] = floatval($price['onetime'] ?? 0);
            }

            $payTypes = json_decode($host['pay_type'] ?? '{}', true);
            $host['billing_cycle'] = $payTypes['pay_type'] ?? '';
        }

        return json([
            'status' => 200,
            'data'   => [
                'user'  => $user,
                'hosts' => $hosts,
            ],
        ]);
    }

    public function doManualPublish()
    {
        $uid       = intval(input('uid'));
        $hostId    = intval(input('host_id'));
        $desc      = input('description', '');
        $salePrice = floatval(input('sale_price', 0));

        if ($uid <= 0 || $hostId <= 0) {
            return json(['status' => 400, 'msg' => '参数错误']);
        }
        if ($salePrice <= 0) {
            return json(['status' => 400, 'msg' => '请输入有效的售价']);
        }

        $host = \think\Db::name('host')->where('id', $hostId)->where('uid', $uid)->find();
        if (!$host) {
            return json(['status' => 400, 'msg' => '服务器不存在或不属于该用户']);
        }
        if ($host['domainstatus'] != 'Active') {
            return json(['status' => 400, 'msg' => '只能出售状态为 Active 的服务器']);
        }

        $config = $this->_config;
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

        $payType = json_decode($product['pay_type'] ?? '{}', true);
        $billingCycle = $payType['pay_type'] ?? '';

        $title = $product['name'] ?? '未命名服务器';

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

        $listingData = [
            'uid'             => $uid,
            'host_id'         => $hostId,
            'product_id'      => $host['productid'],
            'title'           => $title,
            'description'     => $desc,
            'sale_price'      => $salePrice,
            'spec_data'       => $specData ? json_encode($specData, JSON_UNESCAPED_UNICODE) : '',
            'product_name'    => $product['name'] ?? '',
            'product_type'    => $product['type'] ?? '',
            'billing_cycle'   => $billingCycle,
            'nextduedate'     => intval($host['nextduedate'] ?? 0),
            'regdate'         => intval($host['regdate'] ?? 0),
            'original_amount' => $originalAmount,
            'status'          => 1,
            'create_time'     => time(),
            'update_time'     => time(),
        ];

        $id = \think\Db::name('market_listing')->insertGetId($listingData);

        $escrowUid = \addons\market\model\MarketModel::getEscrowUid();
        if ($escrowUid > 0 && $escrowUid != $uid) {
            try {
                \addons\market\model\MarketModel::transferHost($hostId, $escrowUid);
            } catch (\Exception $e) {
            }
        }

        return json(['status' => 200, 'data' => ['id' => $id], 'msg' => '上架成功']);
    }
}
