<?php

namespace addons\market;

class MarketHooks
{
    public function invoice_paid($param)
    {
        $invoiceId = intval($param['invoiceid'] ?? 0);
        if ($invoiceId <= 0) {
            return;
        }

        $order = \think\Db::name('market_order')
            ->where('invoice_id', $invoiceId)
            ->find();
        if (!$order) {
            return;
        }

        if ($order['status'] != 0) {
            return;
        }

        $host = \think\Db::name('host')->where('id', $order['host_id'])->find();
        if (!$host) {
            return;
        }

        $config = $this->getMarketConfig();
        $feePercent = floatval($config['fee_percent'] ?? 5);
        $fee = round($order['amount'] * $feePercent / 100, 2);
        $sellerAmount = round($order['amount'] - $fee, 2);

        \think\Db::startTrans();
        try {
            $renewLogic = new \app\common\logic\Renew();
            $renewLogic->deleteRenewInvoice($order['host_id']);

            $upgradeLogic = new \app\common\logic\Upgrade();
            $upgradeLogic->deleteUpgradeInvoices($order['host_id']);

            $invoices = \think\Db::name('host')->alias('a')
                ->field('c.id as invoice_id')
                ->leftJoin('orders b', 'a.orderid = b.id')
                ->leftJoin('invoices c', 'b.invoiceid = c.id')
                ->where('a.id', $order['host_id'])
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

            \think\Db::name('host')
                ->where('id', $order['host_id'])
                ->update(['uid' => $order['buyer_uid']]);

            \think\Db::name('market_order')->where('id', $order['id'])->update([
                'status'        => 2,
                'fee'           => $fee,
                'seller_amount' => $sellerAmount,
                'transfer_time' => time(),
            ]);

            \think\Db::name('market_listing')
                ->where('id', $order['listing_id'])
                ->update(['status' => 2, 'update_time' => time()]);

            if ($sellerAmount > 0) {
                \think\Db::name('clients')
                    ->where('id', $order['seller_uid'])
                    ->setInc('credit', $sellerAmount);
                \credit_log([
                    'uid'    => $order['seller_uid'],
                    'desc'   => 'Market交易收入 #' . $order['id'],
                    'amount' => $sellerAmount,
                    'relid'  => $order['id'],
                ]);
            }

            $feeToEscrow = intval($config['fee_to_escrow'] ?? 0);
            $escrowUid = \addons\market\model\MarketModel::getEscrowUid();
            if ($feeToEscrow == 1 && $escrowUid > 0 && $fee > 0) {
                \think\Db::name('clients')
                    ->where('id', $escrowUid)
                    ->setInc('credit', $fee);
                \credit_log([
                    'uid'    => $escrowUid,
                    'desc'   => 'Market交易手续费 #' . $order['id'],
                    'amount' => $fee,
                    'relid'  => $order['id'],
                ]);
            }

            \think\Db::commit();

            \think\facade\Hook::listen('transfer_service', [
                'hostid'       => $order['host_id'],
                'uid'          => $order['seller_uid'],
                'transfer_uid' => $order['buyer_uid'],
            ]);
        } catch (\Exception $e) {
            \think\Db::rollback();
        }
    }

    public function invoice_mark_cancelled($param)
    {
        $this->handleInvoiceCancelledOrDeleted($param, '账单取消');
    }

    public function invoice_delete($param)
    {
        $this->handleInvoiceCancelledOrDeleted($param, '账单删除');
    }

    private function handleInvoiceCancelledOrDeleted($param, $reason)
    {
        $invoiceId = intval($param['invoiceid'] ?? 0);
        if ($invoiceId <= 0) {
            return;
        }

        $order = \think\Db::name('market_order')
            ->where('invoice_id', $invoiceId)
            ->find();
        if (!$order) {
            return;
        }

        if ($order['status'] != 0) {
            return;
        }

        \think\Db::name('market_order')->where('id', $order['id'])->update([
            'status' => 4,
            'remark' => $reason . '，订单自动取消',
        ]);

        \addons\market\model\MarketModel::unlockListing($order['listing_id']);
    }

    private function getMarketConfig()
    {
        return \addons\market\model\MarketModel::marketConfig();
    }
}
