<?php

hook_add('invoice_paid', [\addons\market\MarketHooks::class, 'invoice_paid']);
hook_add('invoice_mark_cancelled', [\addons\market\MarketHooks::class, 'invoice_mark_cancelled']);
hook_add('invoice_delete', [\addons\market\MarketHooks::class, 'invoice_delete']);
hook_add('product_divert_delete', [\addons\market\MarketHooks::class, 'product_divert_delete']);
