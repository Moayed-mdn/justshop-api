<?php

declare(strict_types=1);

namespace App\Enums;

enum ErrorCode: string
{
    // --- Authentication (AUTH) ---
    case AUTH_001 = 'AUTH_001';
    case AUTH_002 = 'AUTH_002';
    case AUTH_003 = 'AUTH_003';
    case AUTH_004 = 'AUTH_004';
    case AUTH_005 = 'AUTH_005';
    case AUTH_006 = 'AUTH_006';
    case AUTH_007 = 'AUTH_007';
    case AUTH_008 = 'AUTH_008';

    // --- Order (ORD) ---
    case ORD_001 = 'ORD_001';
    case ORD_002 = 'ORD_002';
    case ORD_003 = 'ORD_003';

    // --- Payment (PMT) ---
    case PMT_001 = 'PMT_001';
    case PMT_002 = 'PMT_002';
    case PMT_003 = 'PMT_003';
    case PMT_004 = 'PMT_004';

    // --- System (SYS) ---
    case SYS_001 = 'SYS_001';
    case SYS_002 = 'SYS_002';

    // --- Validation (VAL) ---
    case VAL_001 = 'VAL_001';

    // --- Product (PRD) ---
    case PRD_001 = 'PRD_001';
    case PRD_002 = 'PRD_002';

    // --- Store (STR) ---
    case STR_001 = 'STR_001'; // Store Not Found
    case STR_002 = 'STR_002'; // Store Disabled
    case STR_003 = 'STR_003'; // Invalid store lifecycle transition
    case STORE_ACCESS_DENIED = 'STORE_ACCESS_DENIED';
    case IDENTITY_DOMAIN_MISMATCH = 'IDENTITY_DOMAIN_MISMATCH';
    case ACCESS_DENIED = 'ACCESS_DENIED';

    // --- Category (CAT) ---
    case CAT_001 = 'CAT_001'; // Category not found
    case CAT_002 = 'CAT_002'; // Category has children (cannot delete)
    case CAT_003 = 'CAT_003'; // Category has products (cannot delete)

    // --- Brand (BRD) ---
    case BRD_001 = 'BRD_001'; // Brand not found
    case BRD_002 = 'BRD_002'; // Brand has products (cannot delete)

    // --- Tag (TAG) ---
    case TAG_001 = 'TAG_001'; // Tag not found

    // --- Lead (LED) ---
    case LED_001 = 'LED_001'; // Lead not found

    // --- User (USR) ---
    case USR_001 = 'USR_001'; // User not found

    // --- Subscription (SUB) ---
    case SUB_001 = 'SUB_001'; // Subscription not found
    case SUB_002 = 'SUB_002'; // Subscription required (payment required)
    case SUB_003 = 'SUB_003'; // Trial already used
    case SUB_004 = 'SUB_004'; // Invalid subscription transition
    case SUB_005 = 'SUB_005'; // Feature not available in plan
    case SUB_006 = 'SUB_006'; // Quota exceeded

    // --- Billing (BIL) ---
    case BIL_001 = 'BIL_001'; // Billing account not found
    case BIL_002 = 'BIL_002'; // Invoice not found
    case BIL_003 = 'BIL_003'; // Payment transaction failed
    case BIL_004 = 'BIL_004'; // Checkout session creation failed
    case BIL_005 = 'BIL_005'; // Billing portal session creation failed
    case BIL_006 = 'BIL_006'; // Plan not found
    case BIL_007 = 'BIL_007'; // Subscription not found
    case BIL_008 = 'BIL_008'; // Active subscription required
    case BIL_009 = 'BIL_009'; // Invalid plan change operation
    case BIL_010 = 'BIL_010'; // Plan upgrade failed
    case BIL_011 = 'BIL_011'; // Subscription cancellation failed
    case BIL_012 = 'BIL_012'; // Cannot resume subscription in current status
    case BIL_013 = 'BIL_013'; // Resume subscription failed

    // --- Theme (THEME) ---
    case THEME_001 = 'THEME_001'; // Cannot delete default template
    case THEME_002 = 'THEME_002'; // Template in use (cannot delete)
}
