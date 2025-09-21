<?php

return [
    /**
     * This is the name of the table that contains the roles used to classify users
     * (for spatie-laravel-permissions it is the `roles` table
     */
    'roles_model' => "\\Spatie\\Permission\\Models\\Role",

    /**
     * The model associated with login and authentication
     */
    'users_model' => "\\App\\Models\\User",

    /**
     * The Namespace in which application models ar located
     */
    'models_path' => "\\App\Models",


    // Enable or disable the approval workflow globally
    'enabled' => true,

    // Default approval strategy for models unless overridden at model-level
    // Options: 'single' (one approver), 'majority' (more than half), 'unanimous' (all approvers)
    'default_strategy' => 'single',

    // Number of required approvals for 'majority' strategy; if null, it will be computed
    'majority_threshold' => null,

    // API settings for mobile integration
    'api' => [
        // Route prefix under the application's API group
        'prefix' => 'approvals',
        // Middleware to apply to the package routes. You can add your auth guard here (e.g., 'auth:sanctum')
        'middleware' => ['api'],
    ],

    // Table names if/when migrations are added (placeholders for now)
    'tables' => [
        'approval_requests' => 'approval_requests',
        'approval_actions'  => 'approval_actions',
    ],
];
