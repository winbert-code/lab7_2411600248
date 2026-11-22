<?php

return [
    /*
     * Reorder point = (average daily usage x lead time) + safety stock.
     */
    'usage_window_days' => 30,
    'default_lead_time_days' => 7,
    'default_daily_usage' => 1,
    'default_safety_stock' => 0,

    /*
     * Simplified EOQ: sqrt((2 x annual demand x ordering cost) / holding cost).
     */
    'ordering_cost' => 500,
    'holding_cost_rate' => 0.20,
    'minimum_order_quantity' => 1,

    'tax_rate' => 12,
    'high_value_threshold' => 50000,
    'dedupe_days' => 7,
    'cache_ttl_seconds' => 60,
];
