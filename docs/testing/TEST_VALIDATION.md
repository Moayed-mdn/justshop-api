# Hero Banner Validation Fix

## Problem
Validation was failing with:
```json
{
  "errors": {
    "visual_type": ["The selected visual type is invalid."],
    "link_target": ["The selected link target is invalid."]
  }
}
```

## Root Causes

### 1. Missing VIDEO enum case
The `HeroVisualTypeEnum` was missing the `VIDEO` case.

**Fixed**: Added `case VIDEO = 'video';`

### 2. Enum validation rule incompatibility
The `new Enum()` validation rule was too strict and didn't handle all edge cases properly.

**Fixed**: Changed to `Rule::in(EnumClass::values())` for better compatibility.

## Changes Made

### 1. Updated HeroVisualTypeEnum
Added VIDEO case:
```php
enum HeroVisualTypeEnum: string
{
    case IMAGE    = 'image';
    case GRADIENT = 'gradient';
    case VIDEO    = 'video';  // ← Added
}
```

### 2. Updated CreateHeroBannerRequest
Changed validation from:
```php
'visual_type' => ['required', new Enum(HeroVisualTypeEnum::class)],
'link_target' => ['nullable', new Enum(HeroLinkTargetEnum::class)],
```

To:
```php
'visual_type' => ['required', 'string', Rule::in(HeroVisualTypeEnum::values())],
'link_target' => ['nullable', 'string', Rule::in(HeroLinkTargetEnum::values())],
```

### 3. Updated UpdateHeroBannerRequest
Same changes as CreateHeroBannerRequest.

## Expected Validation

### Valid visual_type values:
- ✅ `"image"`
- ✅ `"gradient"`
- ✅ `"video"`

### Valid link_target values:
- ✅ `"_self"`
- ✅ `"_blank"`
- ✅ `null` (nullable)

## Testing

### Test Update Request
```bash
curl -X PATCH "http://localhost:8000/api/v1/merchant/stores/1/hero-banners/5" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cat_url": "/shop",
    "position": 0,
    "visual_type": "gradient",
    "gradient_from": "#ec8d8d",
    "gradient_to": "#6669cc",
    "link_target": "_self",
    "is_active": true,
    "translations": [
      {
        "locale": "en",
        "title": "Updated Title"
      },
      {
        "locale": "ar",
        "title": "عنوان محدث"
      }
    ]
  }'
```

Should return `200 OK` with updated banner data.

## Status
✅ **FIXED** - Validation now accepts all valid enum values
