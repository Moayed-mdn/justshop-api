<?php

return [
    'bootstrap' => [
        'v2_enabled' => env('BOOTSTRAP_V2_ENABLED', false),
        'shadow_read' => env('BOOTSTRAP_SHADOW_READ', false),
        'response_version' => env('BOOTSTRAP_RESPONSE_VERSION', 'v1'),
    ],

    'membership' => [
        'dual_read' => env('MEMBERSHIP_DUAL_READ', false),
        'v2_authority' => env('MEMBERSHIP_V2_AUTHORITY', false),
    ],

    'policy' => [
        'normalized_readiness' => env('POLICY_NORMALIZED_READINESS', false),
        'enforcement_mode' => env('POLICY_ENFORCEMENT_MODE', 'legacy'),
    ],

    'rbac' => [
        'resolver_v2' => env('RBAC_RESOLVER_V2', false),
        'dual_resolve' => env('RBAC_DUAL_RESOLVE', false),
        'snapshot_mode' => env('RBAC_SNAPSHOT_MODE', false),
    ],

    'drift_detection' => [
        'enabled' => env('AUTHORIZATION_DRIFT_DETECTION_ENABLED', true),
        'warning_only' => env('AUTHORIZATION_DRIFT_WARNING_ONLY', true),
        'allowlist_path' => env('AUTHORIZATION_DRIFT_ALLOWLIST_PATH', base_path('docs/wave2/drift-allowlist.json')),
        'baseline_path' => env('AUTHORIZATION_DRIFT_BASELINE_PATH', storage_path('app/wave2/authorization-drift-baseline.json')),
        'report_path' => env('AUTHORIZATION_DRIFT_REPORT_PATH', storage_path('app/wave2/authorization-drift-report.json')),
        'triage_report_path' => env('AUTHORIZATION_TRIAGE_REPORT_PATH', storage_path('app/wave2/authorization-triage-report.json')),
        'policy_ownership_report_path' => env('POLICY_OWNERSHIP_REPORT_PATH', storage_path('app/wave2/policy-ownership-report.json')),
        'readiness_report_path' => env('WAVE2_READINESS_REPORT_PATH', storage_path('app/wave2/operational-readiness-report.json')),
    ],
];
