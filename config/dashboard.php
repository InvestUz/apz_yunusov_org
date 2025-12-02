<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contract Status Values
    |--------------------------------------------------------------------------
    |
    | These values represent the different statuses a contract can have.
    | Used throughout the application for filtering and display purposes.
    |
    */
    'statuses' => [
        'active' => 'Амал қилувчи',
        'cancelled' => 'Бекор қилинган',
        'completed' => 'Якунланган',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Display Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for status display including colors and labels.
    |
    */
    'status_config' => [
        'active' => [
            'label_uz' => 'Амал қилувчи',
            'label_en' => 'Active',
            'code' => 'ACTIVE',
            'color' => '#28a745',
        ],
        'cancelled' => [
            'label_uz' => 'Бекор қилинган',
            'label_en' => 'Cancelled',
            'code' => 'CANCELLED',
            'color' => '#dc3545',
        ],
        'completed' => [
            'label_uz' => 'Якунланган',
            'label_en' => 'Completed',
            'code' => 'COMPLETED',
            'color' => '#007bff',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Period Filters
    |--------------------------------------------------------------------------
    |
    | Available period filters for dashboard statistics.
    |
    */
    'periods' => [
        'today',
        'week',
        'month',
        'quarter',
        'year',
        'all',
    ],

    /*
    |--------------------------------------------------------------------------
    | Amount Formatting
    |--------------------------------------------------------------------------
    |
    | Configuration for amount display formatting.
    |
    */
    'formatting' => [
        'currency_symbol' => 'сўм',
        'billion_divisor' => 1000000000,
        'million_divisor' => 1000000,
        'billion_suffix' => 'млрд',
        'million_suffix' => 'млн',
        'decimal_places' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Limits
    |--------------------------------------------------------------------------
    |
    | Limits for various dashboard queries and displays.
    |
    */
    'limits' => [
        'recent_contracts' => 5,
        'recent_payments' => 5,
        'top_districts' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Chart Colors
    |--------------------------------------------------------------------------
    |
    | Color scheme for charts and visualizations.
    |
    */
    'chart_colors' => [
        'primary' => '#1e40af',
        'secondary' => '#9ca3af',
        'success' => '#28a745',
        'danger' => '#dc3545',
        'warning' => '#ffc107',
        'info' => '#17a2b8',
    ],
];
