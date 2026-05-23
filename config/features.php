<?php

declare(strict_types=1);

/**
 * Feature Flag Configuration
 * 
 * This is the canonical feature flag registry for the platform.
 * All feature flags MUST be registered here with complete metadata.
 * 
 * Governance Rules:
 * - Every flag MUST have an owner
 * - Every flag MUST have an expiry milestone
 * - Every flag MUST have a blast radius classification
 * - Flags without metadata MUST fail governance validation
 * 
 * Naming Convention: <domain>.<capability>.<mode>
 * Modes: enabled, shadow, dual_read, dual_write, authority, kill, strict
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Observability Flags
    |--------------------------------------------------------------------------
    */
    'observability.events.enabled' => [
        'default' => env('OBSERVABILITY_EVENTS_ENABLED', true),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable structured event logging for observability',
        'blast_radius' => 'platform-wide',
        'rollback_effect' => 'disable event emission, preserve core functionality',
        'expiry_milestone' => 'permanent',
        'category' => 'observability',
        'introduced_wave' => 'wave1',
        'kill_switch' => false,
    ],

    'observability.tracing.enabled' => [
        'default' => env('OBSERVABILITY_TRACING_ENABLED', true),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable request correlation and trace context propagation',
        'blast_radius' => 'platform-wide',
        'rollback_effect' => 'disable correlation IDs, preserve core functionality',
        'expiry_milestone' => 'permanent',
        'category' => 'observability',
        'introduced_wave' => 'wave1',
        'kill_switch' => false,
    ],

    'observability.log_redaction.enabled' => [
        'default' => env('OBSERVABILITY_LOG_REDACTION_ENABLED', true),
        'owner' => 'security-team',
        'business_owner' => 'security',
        'description' => 'Enable sensitive data redaction in logs',
        'blast_radius' => 'platform-wide',
        'rollback_effect' => 'disable redaction, may expose sensitive data',
        'expiry_milestone' => 'permanent',
        'category' => 'security',
        'introduced_wave' => 'wave1',
        'kill_switch' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Bootstrap Flags (Wave 2)
    |--------------------------------------------------------------------------
    */
    'bootstrap.v2.enabled' => [
        'default' => env('BOOTSTRAP_V2_ENABLED', false),
        'owner' => 'platform-team',
        'business_owner' => 'product',
        'description' => 'Enable decomposed bootstrap resolution',
        'blast_radius' => 'frontend-wide',
        'rollback_effect' => 'revert to v1 bootstrap authority',
        'expiry_milestone' => 'wave2-completion',
        'category' => 'bootstrap',
        'introduced_wave' => 'wave2',
        'kill_switch' => true,
    ],

    'bootstrap.shadow_read' => [
        'default' => env('BOOTSTRAP_SHADOW_READ', false),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable shadow bootstrap resolution for parity telemetry',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable shadow resolution, no functional impact',
        'expiry_milestone' => 'wave2-parity-proven',
        'category' => 'bootstrap',
        'introduced_wave' => 'wave2',
        'kill_switch' => true,
    ],

    'bootstrap.response_version' => [
        'default' => env('BOOTSTRAP_RESPONSE_VERSION', 'v1'),
        'owner' => 'platform-team',
        'business_owner' => 'product',
        'description' => 'Bootstrap response version selector (v1|v2)',
        'blast_radius' => 'frontend-wide',
        'rollback_effect' => 'revert to v1',
        'expiry_milestone' => 'wave2-completion',
        'category' => 'bootstrap',
        'introduced_wave' => 'wave2',
        'kill_switch' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity & Auth Flags (Wave 3)
    |--------------------------------------------------------------------------
    */
    'identity.context_split.enabled' => [
        'default' => env('IDENTITY_CONTEXT_SPLIT_ENABLED', false),
        'owner' => 'auth-team',
        'business_owner' => 'security',
        'description' => 'Enable explicit identity context resolution',
        'blast_radius' => 'auth-domain',
        'rollback_effect' => 'revert to implicit identity resolution',
        'expiry_milestone' => 'wave3-completion',
        'category' => 'identity',
        'introduced_wave' => 'wave3',
        'kill_switch' => true,
    ],

    'identity.actor_resolution.v2' => [
        'default' => env('IDENTITY_ACTOR_RESOLUTION_V2', false),
        'owner' => 'auth-team',
        'business_owner' => 'security',
        'description' => 'Enable v2 actor classification (merchant/customer/super_admin)',
        'blast_radius' => 'auth-domain',
        'rollback_effect' => 'revert to legacy actor inference',
        'expiry_milestone' => 'wave3-completion',
        'category' => 'identity',
        'introduced_wave' => 'wave3',
        'kill_switch' => true,
    ],

    'auth.guard_split.enabled' => [
        'default' => env('AUTH_GUARD_SPLIT_ENABLED', false),
        'owner' => 'auth-team',
        'business_owner' => 'security',
        'description' => 'Enable separate merchant/customer guards',
        'blast_radius' => 'critical-auth',
        'rollback_effect' => 'revert to shared guard, may require session reset',
        'expiry_milestone' => 'wave3-completion',
        'category' => 'auth',
        'introduced_wave' => 'wave3',
        'kill_switch' => true,
    ],

    'auth.customer_guard.shadow' => [
        'default' => env('AUTH_CUSTOMER_GUARD_SHADOW', false),
        'owner' => 'auth-team',
        'business_owner' => 'operations',
        'description' => 'Enable shadow customer guard resolution (non-authoritative)',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable shadow guard, no functional impact',
        'expiry_milestone' => 'wave3-guard-split-proven',
        'category' => 'auth',
        'introduced_wave' => 'wave3',
        'kill_switch' => true,
    ],

    'auth.session_cookie.v2' => [
        'default' => env('AUTH_SESSION_COOKIE_V2', false),
        'owner' => 'auth-team',
        'business_owner' => 'security',
        'description' => 'Enable v2 session cookie naming for guard isolation',
        'blast_radius' => 'critical-auth',
        'rollback_effect' => 'revert to v1 cookie naming',
        'expiry_milestone' => 'wave3-completion',
        'category' => 'auth',
        'introduced_wave' => 'wave3',
        'kill_switch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Membership Flags (Wave 2)
    |--------------------------------------------------------------------------
    */
    'membership.dual_read' => [
        'default' => env('MEMBERSHIP_DUAL_READ', false),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable dual-read membership resolution for parity',
        'blast_radius' => 'admin-domain',
        'rollback_effect' => 'disable dual-read, legacy source remains authoritative',
        'expiry_milestone' => 'wave2-membership-parity',
        'category' => 'membership',
        'introduced_wave' => 'wave2',
        'kill_switch' => true,
    ],

    'membership.dual_write' => [
        'default' => env('MEMBERSHIP_DUAL_WRITE', false),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable dual-write membership mutations',
        'blast_radius' => 'admin-domain',
        'rollback_effect' => 'disable dual-write, legacy source remains authoritative',
        'expiry_milestone' => 'wave2-membership-cutover',
        'category' => 'membership',
        'introduced_wave' => 'wave2',
        'kill_switch' => true,
    ],

    'membership.v2_authority' => [
        'default' => env('MEMBERSHIP_V2_AUTHORITY', false),
        'owner' => 'platform-team',
        'business_owner' => 'product',
        'description' => 'Make v2 membership source authoritative',
        'blast_radius' => 'admin-domain',
        'rollback_effect' => 'revert to legacy membership authority',
        'expiry_milestone' => 'wave2-completion',
        'category' => 'membership',
        'introduced_wave' => 'wave2',
        'kill_switch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy & RBAC Flags (Wave 2/4)
    |--------------------------------------------------------------------------
    */
    'policy.normalized_readiness' => [
        'default' => env('POLICY_NORMALIZED_READINESS', false),
        'owner' => 'platform-team',
        'business_owner' => 'security',
        'description' => 'Enable policy normalization telemetry',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable telemetry, no functional impact',
        'expiry_milestone' => 'wave4-policy-complete',
        'category' => 'authorization',
        'introduced_wave' => 'wave2',
        'kill_switch' => false,
    ],

    'policy.enforcement_mode' => [
        'default' => env('POLICY_ENFORCEMENT_MODE', 'legacy'),
        'owner' => 'platform-team',
        'business_owner' => 'security',
        'description' => 'Policy enforcement mode (legacy|strict|log_only)',
        'blast_radius' => 'platform-wide',
        'rollback_effect' => 'revert to legacy enforcement',
        'expiry_milestone' => 'wave4-completion',
        'category' => 'authorization',
        'introduced_wave' => 'wave4',
        'kill_switch' => false,
    ],

    'rbac.resolver.v2' => [
        'default' => env('RBAC_RESOLVER_V2', false),
        'owner' => 'platform-team',
        'business_owner' => 'security',
        'description' => 'Enable v2 permission resolver',
        'blast_radius' => 'admin-domain',
        'rollback_effect' => 'revert to v1 resolver',
        'expiry_milestone' => 'wave4-completion',
        'category' => 'authorization',
        'introduced_wave' => 'wave4',
        'kill_switch' => true,
    ],

    'rbac.snapshot_mode' => [
        'default' => env('RBAC_SNAPSHOT_MODE', false),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable RBAC snapshot mode for parity comparison',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable snapshot, no functional impact',
        'expiry_milestone' => 'wave4-rbac-parity',
        'category' => 'authorization',
        'introduced_wave' => 'wave4',
        'kill_switch' => false,
    ],

    'rbac.dual_resolve' => [
        'default' => env('RBAC_DUAL_RESOLVE', false),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable dual permission resolution for parity',
        'blast_radius' => 'admin-domain',
        'rollback_effect' => 'disable dual-resolve, v1 remains authoritative',
        'expiry_milestone' => 'wave4-rbac-parity',
        'category' => 'authorization',
        'introduced_wave' => 'wave4',
        'kill_switch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Checkout Flags (Wave 5)
    |--------------------------------------------------------------------------
    */
    'checkout.hardening.enabled' => [
        'default' => env('CHECKOUT_HARDENING_ENABLED', false),
        'owner' => 'commerce-team',
        'business_owner' => 'product',
        'description' => 'Enable checkout ownership and integrity hardening',
        'blast_radius' => 'critical-revenue',
        'rollback_effect' => 'revert to legacy checkout, may require manual reconciliation',
        'expiry_milestone' => 'wave5-completion',
        'category' => 'checkout',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    'checkout.webhook.idempotency' => [
        'default' => env('CHECKOUT_WEBHOOK_IDEMPOTENCY', false),
        'owner' => 'commerce-team',
        'business_owner' => 'operations',
        'description' => 'Enable webhook idempotency enforcement',
        'blast_radius' => 'payment-domain',
        'rollback_effect' => 'disable idempotency checks',
        'expiry_milestone' => 'wave5-completion',
        'category' => 'checkout',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    'checkout.ownership_assertions' => [
        'default' => env('CHECKOUT_OWNERSHIP_ASSERTIONS', false),
        'owner' => 'commerce-team',
        'business_owner' => 'security',
        'description' => 'Enable strict checkout ownership assertions',
        'blast_radius' => 'checkout-domain',
        'rollback_effect' => 'disable assertions, log-only mode',
        'expiry_milestone' => 'wave5-completion',
        'category' => 'checkout',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Async Flags (Wave 5)
    |--------------------------------------------------------------------------
    */
    'async.domain_events.enabled' => [
        'default' => env('ASYNC_DOMAIN_EVENTS_ENABLED', false),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable async domain event emission',
        'blast_radius' => 'platform-wide',
        'rollback_effect' => 'disable async events, sync path remains',
        'expiry_milestone' => 'wave5-completion',
        'category' => 'async',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    'async.listener.orders.enabled' => [
        'default' => env('ASYNC_LISTENER_ORDERS_ENABLED', false),
        'owner' => 'commerce-team',
        'business_owner' => 'operations',
        'description' => 'Enable async order event listeners',
        'blast_radius' => 'order-domain',
        'rollback_effect' => 'disable listeners, sync path remains',
        'expiry_milestone' => 'wave5-completion',
        'category' => 'async',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    'async.listener.orders.kill' => [
        'default' => env('ASYNC_LISTENER_ORDERS_KILL', false),
        'owner' => 'commerce-team',
        'business_owner' => 'operations',
        'description' => 'Emergency kill switch for order listeners',
        'blast_radius' => 'order-domain',
        'rollback_effect' => 'immediately stop order listener processing',
        'expiry_milestone' => 'wave5-stable',
        'category' => 'async',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    'async.replay.pause' => [
        'default' => env('ASYNC_REPLAY_PAUSE', false),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Pause async event replay processing',
        'blast_radius' => 'async-domain',
        'rollback_effect' => 'pause replay, queue continues',
        'expiry_milestone' => 'operational',
        'category' => 'async',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Enterprise Flags (Wave 6)
    |--------------------------------------------------------------------------
    */
    'enterprise.readiness.enabled' => [
        'default' => env('ENTERPRISE_READINESS_ENABLED', false),
        'owner' => 'platform-team',
        'business_owner' => 'product',
        'description' => 'Enable enterprise capability readiness checks',
        'blast_radius' => 'enterprise-only',
        'rollback_effect' => 'disable enterprise features',
        'expiry_milestone' => 'wave6-completion',
        'category' => 'enterprise',
        'introduced_wave' => 'wave6',
        'kill_switch' => false,
    ],

    'enterprise.audit.strict_mode' => [
        'default' => env('ENTERPRISE_AUDIT_STRICT_MODE', false),
        'owner' => 'security-team',
        'business_owner' => 'compliance',
        'description' => 'Enable strict audit mode for enterprise tenants',
        'blast_radius' => 'enterprise-only',
        'rollback_effect' => 'revert to standard audit mode',
        'expiry_milestone' => 'wave6-completion',
        'category' => 'enterprise',
        'introduced_wave' => 'wave6',
        'kill_switch' => false,
    ],
];
