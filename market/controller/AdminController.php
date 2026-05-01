<?php

/**
 * @adminMenuRoot(
 *     name='RuiNexus Market',
 *     action='index',
 *     icon='',
 *     order='99'
 * )
 */
class AdminController
{
    /**
     * @adminMenu(
     *     name='商品列表',
     *     display=true,
     *     order='1'
     * )
     */
    public function index()
    {
        $param = input();

        $page  = max(1, intval($param['page'] ?? 1));
        $limit = max(1, min(100, intval($param['limit'] ?? 20)));
        $status = isset($param['status']) ? intval($param['status']) : null;
        $keyword = $param['keyword'] ?? '';

        $where = [];
        $where[] = ['status', '<>', 4];
        if ($status !== null && $status !== '') {
            $where[] = ['status', '=', $status];
        }
        if ($keyword !== '') {
            $where[] = ['title', 'like', "%{$keyword}%"];
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

        foreach ($list as &$v) {
            $v['status_text'] = $statusMap[$v['status']] ?? '';
            $v['seller'] = \think\Db::name('clients')
                ->where('id', $v['uid'])->value('username');
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

    /**
     * @adminMenu(
     *     name='审核',
     *     display=false,
     *     order='2'
     * )
     */
    public function audit()
    {
        $id   = intval(input('id'));
        $act  = input('action');
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
            return json(['status' => 200, 'msg' => lang('market_audit_pass')]);
        }
        return json(['status' => 200, 'msg' => lang('market_audit_reject')]);
    }

    /**
     * @adminMenu(
     *     name='推荐',
     *     display=false,
     *     order='3'
     * )
     */
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

    /**
     * @adminMenu(
     *     name='删除',
     *     display=false,
     *     order='4'
     * )
     */
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

        \think\Db::name('market_listing')->where('id', $id)->update([
            'status'      => 4,
            'update_time' => time(),
        ]);

        return json(['status' => 200, 'msg' => lang('market_deleted')]);
    }

    /**
     * @adminMenu(
     *     name='配置',
     *     display=true,
     *     order='0'
     * )
     */
    public function configure()
    {
        $Market = new \addons\market\Market();
        $configDef = $Market->getConfig();

        $dbConfig = \think\Db::name('plugin')
            ->where('name', 'Market')->where('module', 'addons')
            ->value('config');
        $dbConfig = $dbConfig ? json_decode($dbConfig, true) : [];

        if (request()->isPost()) {
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

        return json([
            'status' => 200,
            'data'   => [
                'config_def'  => $configDef,
                'config_data' => $dbConfig,
            ],
        ]);
    }
}
