<?php

/**
 * Pipeline Stage Mapping Konfigürasyonu
 * PipelineProgressService ve SalesPipelineController ile senkronize.
 */
return [

    /**
     * Stage agırlıkları — sadece daha yüksek değere ilerleme izin verilir.
     * SalesPipelineController::STAGE_WEIGHTS ile tutarlı olmalı.
     */
    'stage_weights' => [
        'new'         => 0.10,
        'contacted'   => 0.20,
        'verified'    => 0.30,
        'follow_up'   => 0.35,
        'interested'  => 0.45,
        'qualified'   => 0.55,
        'sales_ready' => 0.70,
        'champion'    => 0.90,
    ],

    /**
     * ProcessOutcome türü → hedef stage mapping.
     */
    'outcome_to_stage' => [
        'acceptance'             => 'qualified',
        'conditional_acceptance' => 'qualified',
    ],

    /**
     * Üniversite başvurusu kabul durumları → hedef stage.
     */
    'university_accepted_statuses' => ['accepted', 'conditional_accepted'],
    'university_accepted_stage'    => 'sales_ready',

    /**
     * Vize onayı → champion stage.
     */
    'visa_doc_code'   => 'VIS-ERTEIL',
    'visa_skip_statuses' => ['expected', 'archived'],
    'visa_approved_stage' => 'champion',

    /**
     * Stage label ve renkleri (UI için).
     */
    'stage_labels' => [
        'new'         => 'Yeni',
        'contacted'   => 'İletişime Geçildi',
        'verified'    => 'Doğrulandı',
        'follow_up'   => 'Takipte',
        'interested'  => 'İlgili',
        'qualified'   => 'Nitelikli',
        'sales_ready' => 'Satışa Hazır',
        'champion'    => 'Champion',
    ],

    'stage_colors' => [
        'new'         => '#94a3b8',
        'contacted'   => '#60a5fa',
        'verified'    => '#34d399',
        'follow_up'   => '#fbbf24',
        'interested'  => '#f97316',
        'qualified'   => '#a78bfa',
        'sales_ready' => '#2563eb',
        'champion'    => '#16a34a',
    ],
];
