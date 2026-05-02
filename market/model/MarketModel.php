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

    public static function marketConfig()
    {
        $configFile = dirname(__DIR__) . '/config.php';
        $defaults = [];
        if (file_exists($configFile)) {
            $tempArr = (include $configFile);
            if (!empty($tempArr) && is_array($tempArr)) {
                foreach ($tempArr as $key => $value) {
                    if (($value['type'] ?? '') == 'group') {
                        foreach ($value['options'] as $gvalue) {
                            foreach ($gvalue['options'] as $ikey => $ivalue) {
                                $defaults[$ikey] = $ivalue['value'];
                            }
                        }
                    } else {
                        $defaults[$key] = $value['value'] ?? '';
                    }
                }
            }
        }

        $keyValues = \think\Db::name('market_config')->column('value', 'key');

        return array_merge($defaults, $keyValues);
    }

    public static function saveMarketConfig($data)
    {
        if (!is_array($data)) {
            return false;
        }
        $now = time();
        foreach ($data as $key => $value) {
            $exists = \think\Db::name('market_config')->where('key', $key)->find();
            if ($exists) {
                \think\Db::name('market_config')->where('key', $key)->update(['value' => $value]);
            } else {
                \think\Db::name('market_config')->insert(['key' => $key, 'value' => $value]);
            }
        }
        return true;
    }

    public static function marketConfigValue($key, $default = null)
    {
        $value = \think\Db::name('market_config')->where('key', $key)->value('value');
        if ($value !== null) {
            return $value;
        }
        $configFile = dirname(__DIR__) . '/config.php';
        if (file_exists($configFile)) {
            $tempArr = (include $configFile);
            if (!empty($tempArr) && is_array($tempArr) && isset($tempArr[$key])) {
                return $tempArr[$key]['value'] ?? $default;
            }
        }
        return $default;
    }

    public static function getEscrowUid()
    {
        $config = self::marketConfig();
        return intval($config['escrow_uid'] ?? 0);
    }
}
