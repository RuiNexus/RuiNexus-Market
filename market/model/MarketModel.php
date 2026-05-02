<?php

namespace addons\market\model;

use think\Model;

class MarketModel extends Model
{
    public static function transferHost($hostId, $newUid)
    {
        $renewLogic = new \app\common\logic\Renew();
        $renewLogic->deleteRenewInvoice($hostId);

        $upgradeLogic = new \app\common\logic\Upgrade();
        $upgradeLogic->deleteUpgradeInvoices($hostId);

        $invoices = \think\Db::name('host')->alias('a')
            ->field('c.id as invoice_id')
            ->leftJoin('orders b', 'a.orderid = b.id')
            ->leftJoin('invoices c', 'b.invoiceid = c.id')
            ->where('a.id', $hostId)
            ->where('c.status', 'Unpaid')
            ->where('c.type', '<>', 'credit_limit')
            ->select()->toArray();
        $invoiceIds = array_column($invoices, 'invoice_id');
        if ($invoiceIds) {
            \think\Db::name('invoices')
                ->whereIn('id', $invoiceIds)
                ->useSoftDelete('delete_time', time())
                ->delete();
        }

        \think\Db::name('host')->where('id', $hostId)->update(['uid' => $newUid]);

        return true;
    }

    public static function getEscrowUid()
    {
        $config = \think\Db::name('plugin')
            ->where('name', 'Market')->where('module', 'addons')
            ->value('config');
        $config = $config ? json_decode($config, true) : [];
        $defaultConfig = (new \addons\market\MarketPlugin())->getDefaultConfig();
        $config = array_merge($defaultConfig, $config);
        return intval($config['escrow_uid'] ?? 0);
    }
}
