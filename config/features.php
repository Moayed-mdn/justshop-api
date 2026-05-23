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

    /*
    |--------------------------------------------------------------------------
    | Auth Authority Flags (Wave 4)
    |--------------------------------------------------------------------------
    */
    'auth.guard_split.shadow' => [
        'default' => env('AUTH_GUARD_SPLIT_SHADOW', true),
        'owner' => 'security-team',
        'business_owner' => 'security',
        'description' => 'Enable shadow guard evaluation for parity comparison',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable shadow evaluation, no functional impact',
        'expiry_milestone' => 'wave4-readiness-proven',
        'category' => 'auth',
        'introduced_wave' => 'wave4',
        'kill_switch' => true,
    ],

    'auth.guard_split.enabled' => [
        'default' => env('AUTH_GUARD_SPLIT_ENABLED', false),
        'owner' => 'security-team',
        'business_owner' => 'security',
        'description' => 'Enable runtime guard split (dark launch mode)',
        'blast_radius' => 'auth-wide',
        'rollback_effect' => 'revert to shared web guard authority',
        'expiry_milestone' => 'wave5-activation',
        'category' => 'auth',
        'introduced_wave' => 'wave4',
        'kill_switch' => true,
    ],

    'auth.guard_split.enforce' => [
        'default' => env('AUTH_GUARD_SPLIT_ENFORCE', false),
        'owner' => 'security-team',
        'business_owner' => 'security',
        'description' => 'Enforce guard isolation (disable silent fallbacks)',
        'blast_radius' => 'critical-auth',
        'rollback_effect' => 'revert to shadow/fallback mode',
        'expiry_milestone' => 'wave5-completion',
        'category' => 'auth',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    'auth.session_cookie.v2' => [
        'default' => env('AUTH_SESSION_COOKIE_V2', false),
        'owner' => 'auth-team',
        'business_owner' => 'security',
        'description' => 'Enable v2 session cookie naming for guard isolation',
        'blast_radius' => 'critical-auth',
        'rollback_effect' => 'revert to v1 cookie naming',
        'expiry_milestone' => 'wave4-completion',
        'category' => 'auth',
        'introduced_wave' => 'wave4',
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
    | Policy & RBAC Flags (Wave 4)
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
        'description' => 'Enable checkout isolation and hardening',
        'blast_radius' => 'critical-commerce',
        'rollback_effect' => 'revert to legacy checkout behavior',
        'expiry_milestone' => 'wave5-completion',
        'category' => 'commerce',
        'introduced_wave' => 'wave5',
        'kill_switch' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Authority Flags (Wave 6)
    |--------------------------------------------------------------------------
    */
    'platform.authority.enabled' => [
        'default' => env('PLATFORM_AUTHORITY_ENABLED', true),
        'owner' => 'platform-team',
        'business_owner' => 'security',
        'description' => 'Enable explicit platform authority enforcement',
        'blast_radius' => 'platform-domain',
        'rollback_effect' => 'disable platform authority enforcement, platform routes become inaccessible',
        'expiry_milestone' => 'permanent',
        'category' => 'platform',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],

    'platform.support.enabled' => [
        'default' => env('PLATFORM_SUPPORT_ENABLED', true),
        'owner' => 'platform-team',
        'business_owner' => 'operations',
        'description' => 'Enable support authority domain',
        'blast_radius' => 'support-domain',
        'rollback_effect' => 'disable support routes',
        'expiry_milestone' => 'permanent',
        'category' => 'platform',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],

    'platform.impersonation.enabled' => [
        'default' => env('PLATFORM_IMPERSONATION_ENABLED', false),
        'owner' => 'platform-team',
        'business_owner' => 'security',
        'description' => 'Enable governed impersonation lifecycle',
        'blast_radius' => 'critical-auth',
        'rollback_effect' => 'disable impersonation, no active sessions affected',
        'expiry_milestone' => 'wave6-impersonation-approval',
        'category' => 'platform',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],

    'platform.impersonation.require_approval' => [
        'default' => env('PLATFORM_IMPERSONATION_REQUIRE_APPROVAL', true),
        'owner' => 'platform-team',
        'business_owner' => 'security',
        'description' => 'Require approval workflow for impersonation requests',
        'blast_radius' => 'support-domain',
        'rollback_effect' => 'allow impersonation without approval (not recommended)',
        'expiry_milestone' => 'permanent',
        'category' => 'platform',
        'introduced_wave' => 'wave6',
        'kill_switch' => false,
    ],

    'enterprise.membership.lifecycle.enabled' => [
        'default' => env('ENTERPRISE_MEMBERSHIP_LIFECYCLE_ENABLED', false),
        'owner' => 'platform-team',
        'business_owner' => 'product',
        'description' => 'Enable enterprise membership lifecycle vocabulary on store_user',
        'blast_radius' => 'merchant-domain',
        'rollback_effect' => 'revert to simple role-based membership',
        'expiry_milestone' => 'wave6-enterprise-completion',
        'category' => 'enterprise',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],

    'enterprise.authority.inheritance.enabled' => [
        'default' => env('ENTERPRISE_AUTHORITY_INHERITANCE_ENABLED', false),
        'owner' => 'platform-team',
        'business_owner' => 'product',
        'description' => 'Enable authority inheritance model (org-level, delegated)',
        'blast_radius' => 'merchant-domain',
        'rollback_effect' => 'revert to store-scoped authority only',
        'expiry_milestone' => 'wave7-enterprise',
        'category' => 'enterprise',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],

    'provider.separation.preparation' => [
        'default' => env('PROVIDER_SEPARATION_PREPARATION', true),
        'owner' => 'auth-team',
        'business_owner' => 'security',
        'description' => 'Enable provider governance telemetry (preparation only, no split)',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable provider telemetry, no functional impact',
        'expiry_milestone' => 'wave7-provider-cutover',
        'category' => 'auth',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],

    'multi_session.governance.enabled' => [
        'default' => env('MULTI_SESSION_GOVERNANCE_ENABLED', true),
        'owner' => 'auth-team',
        'business_owner' => 'security',
        'description' => 'Enable multi-session coexistence governance and anomaly detection',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable session coexistence detection, no functional impact',
        'expiry_milestone' => 'permanent',
        'category' => 'auth',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],

    'authorization.policy_registry.enabled' => [
        'default' => env('AUTHORIZATION_POLICY_REGISTRY_ENABLED', true),
        'owner' => 'platform-team',
        'business_owner' => 'security',
        'description' => 'Enable policy ownership registry for actor-domain governance',
        'blast_radius' => 'observability-only',
        'rollback_effect' => 'disable policy registry, no functional impact',
        'expiry_milestone' => 'permanent',
        'category' => 'authorization',
        'introduced_wave' => 'wave6',
        'kill_switch' => true,
    ],
];
