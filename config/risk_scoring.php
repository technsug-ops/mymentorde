<?php

return [
    'factors' => [
        'no_senior'         => ['points' => 15, 'description' => 'Senior ataması yok'],
        'payment_issue'     => ['points' => 20, 'description' => 'Ödeme durumu sorunlu'],
        'pending_approvals' => ['points_per' => 10, 'max' => 30, 'description' => 'Bekleyen onay başına'],
        'overdue_outcomes'  => ['points_per' => 10, 'max' => 30, 'description' => 'Geciken outcome başına'],
        'no_recent_note'    => ['points' => 10, 'days' => 14, 'description' => 'Not yok (14 gün)'],
        'pending_amount'    => ['divisor' => 250, 'min' => 5, 'max' => 20, 'description' => 'Açık tahsilat'],
    ],
    'levels' => [
        'low'      => ['min' => 0,  'max' => 20],
        'medium'   => ['min' => 21, 'max' => 40],
        'high'     => ['min' => 41, 'max' => 60],
        'critical' => ['min' => 61, 'max' => 100],
    ],
    'history_limit' => 30,
];
