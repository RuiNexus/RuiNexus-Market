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

    public static function getPriceFromPricing($billingCycle, $pricingRow)
    {
        if (empty($pricingRow)) {
            return 0;
        }

        $cycle = strtolower($billingCycle);

        if ($cycle === 'free') {
            return 0;
        }

        if (isset($pricingRow[$cycle]) && floatval($pricingRow[$cycle]) > 0) {
            return floatval($pricingRow[$cycle]);
        }

        if (floatval($pricingRow['monthly'] ?? 0) > 0) {
            return floatval($pricingRow['monthly']);
        }

        return floatval($pricingRow['onetime'] ?? 0);
    }

    public static function getEscrowUid()
    {
        $config = self::marketConfig();
        return intval($config['escrow_uid'] ?? 0);
    }

    public static function lockListing($listingId)
    {
        \think\Db::name('market_listing')->where('id', $listingId)->update([
            'status'      => 5,
            'update_time' => time(),
        ]);
    }

    public static function unlockListing($listingId)
    {
        \think\Db::name('market_listing')->where('id', $listingId)->update([
            'status'      => 1,
            'update_time' => time(),
        ]);
    }

    public static function cancelExpiredOrders($listingId = null)
    {
        $expireMinutes = intval(self::marketConfigValue('order_expire_minutes', 15));

        $query = \think\Db::name('market_order')
            ->where('status', 0);
        if ($listingId) {
            $query->where('listing_id', $listingId);
        }
        $expiredOrders = $query->where('expire_time', '>', 0)
            ->where('expire_time', '<', time())
            ->select()
            ->toArray();

        foreach ($expiredOrders as $order) {
            if ($order['invoice_id'] > 0) {
                \think\Db::name('invoices')
                    ->where('id', $order['invoice_id'])
                    ->useSoftDelete('delete_time', time())
                    ->delete();
            }

            \think\Db::name('market_order')->where('id', $order['id'])->update([
                'status' => 4,
                'remark' => '订单超时自动取消',
            ]);

            self::unlockListing($order['listing_id']);
        }

        return count($expiredOrders);
    }
}
