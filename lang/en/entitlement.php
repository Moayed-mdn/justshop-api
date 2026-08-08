<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Entitlement Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for entitlement and quota
    | related messages throughout the application.
    |
    */

    'quota_exceeded' => 'You have reached your limit for :feature. Current limit: :limit. Please upgrade your plan to increase this limit.',
    'quota_exceeded_generic' => 'You have reached your subscription limit. Please upgrade your plan.',
    
    // Specific features
    'quota_exceeded_stores' => 'You have reached the maximum number of stores (:limit). Please upgrade your plan to create more stores.',
    'quota_exceeded_products' => 'You have reached the maximum number of products (:limit). Please upgrade your plan to add more products.',
    'quota_exceeded_users' => 'You have reached the maximum number of team members (:limit). Please upgrade your plan to invite more users.',
    
    // Feature not available
    'feature_not_available' => 'This feature is not available on your current plan. Please upgrade to access :feature.',
    'feature_not_available_generic' => 'This feature is not available on your current plan.',
    
    // Subscription required
    'subscription_required' => 'An active subscription is required to access this feature.',
    'subscription_expired' => 'Your subscription has expired. Please renew to continue using this feature.',
    
    // Access messages
    'write_access_restricted' => 'Write access is restricted. Subscription status: :status',
    'read_access_restricted' => 'Access is restricted. Subscription status: :status',
];
