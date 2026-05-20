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

    // --- Store (STR) ---
    case STR_001 = 'STR_001';
    case STR_002 = 'STR_002';

    // --- Category (CAT) ---
    case CAT_001 = 'CAT_001'; // Category not found
    case CAT_002 = 'CAT_002'; // Category has children (cannot delete)
    case CAT_003 = 'CAT_003'; // Category has products (cannot delete)

    // --- Brand (BRD) ---
    case BRD_001 = 'BRD_001'; // Brand not found
    case BRD_002 = 'BRD_002'; // Brand has products (cannot delete)

    // --- Tag (TAG) ---
    case TAG_001 = 'TAG_001'; // Tag not found
}