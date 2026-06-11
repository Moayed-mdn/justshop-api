# Theme System Usage Guide

**Quick Reference**: How to use the storefront theme system  
**Audience**: Developers  
**Last Updated**: June 6, 2026

---

## 🚀 Quick Start

### Using Theme Colors in Your Components

```vue
<template>
  <div class="custom-card">
    <h2>My Custom Component</h2>
    <button class="custom-button">Click Me</button>
  </div>
</template>

<style scoped>
.custom-card {
  background-color: var(--color-background);
  border: 1px solid var(--color-border);
  color: var(--color-text);
}

.custom-button {
  background-color: var(--color-primary);
  color: var(--white);
  font-family: var(--font-body);
  padding: var(--layout-spacing-unit);
  border-radius: var(--layout-border-radius);
}

.custom-button:hover {
  background-color: var(--btn-primary-hover);
}
</style>
```

---

## 🎨 Available CSS Variables

### Colors

```css
/* Brand Colors */
--color-primary          /* Primary brand color */
--color-secondary        /* Secondary brand color */
--color-accent           /* Accent color */

/* Base Colors */
--color-background       /* Page background */
--color-text             /* Default text color */
--color-text-light       /* Light/secondary text */
--color-text-dark        /* Dark/emphasis text */
--color-border           /* Border color */

/* State Colors */
--color-success          /* Success state */
--color-warning          /* Warning state */
--color-error            /* Error state */
--color-info             /* Info state */
```

### Typography

```css
/* Font Families */
--font-heading           /* Heading font (Google Fonts) */
--font-body              /* Body text font (Google Fonts) */
--font-display           /* Display/hero font */
--font-mono              /* Monospace font */

/* Font Sizes */
--font-size-base         /* Base font size (16px) */
--line-height-base       /* Base line height (1.5) */
```

### Layout

```css
/* Spacing & Sizing */
--layout-container-width   /* Max content width */
--layout-spacing-unit      /* Base spacing unit */
--layout-border-radius     /* Default border radius */
--layout-header-height     /* Header height */
--layout-footer-padding    /* Footer padding */
```

### Button Aliases

```css
/* Primary Button */
--btn-primary-bg         /* Primary button background */
--btn-primary-text       /* Primary button text */
--btn-primary-hover      /* Primary button hover */

/* Secondary Button */
--btn-secondary-bg       /* Secondary button background */
--btn-secondary-text     /* Secondary button text */
--btn-secondary-hover    /* Secondary button hover */

/* Accent Button */
--btn-accent-bg          /* Accent button background */
--btn-accent-text        /* Accent button text */
--btn-accent-hover       /* Accent button hover */
```

---

## 🧩 Using Theme Data in Components

### Access Theme Data

```vue
<script setup lang="ts">
import { useStoreTheme } from '~/composables/useStoreTheme'

const {
  theme,           // Full theme object
  loading,         // Loading state
  error,           // Error state
  colors,          // Theme colors
  typography,      // Theme typography
  layout,          // Theme layout
  fetchTheme,      // Fetch theme from API
  applyThemeTokens // Apply CSS variables
} = useStoreTheme()
</script>

<template>
  <div v-if="loading">Loading theme...</div>
  <div v-else-if="error">Error: {{ error.message }}</div>
  <div v-else>
    <p>Primary Color: {{ colors.primary }}</p>
    <p>Heading Font: {{ typography.heading }}</p>
  </div>
</template>
```

---

### Access Specific Section

```vue
<script setup lang="ts">
const { getSection } = useStoreTheme()

// Get header section
const headerSection = getSection('header')

// Get footer section
const footerSection = getSection('footer')
</script>

<template>
  <div v-if="headerSection">
    Header settings: {{ headerSection.settings }}
  </div>
</template>
```

---

### Access Navigation Menu

```vue
<script setup lang="ts">
import { useStoreNavigation } from '~/composables/useStoreNavigation'

const {
  menu,              // Menu object
  loading,           // Loading state
  topLevelItems,     // Root menu items
  hierarchicalItems, // Nested menu structure
  fetchMenu          // Fetch menu by handle
} = useStoreNavigation('main-menu')

onMounted(() => {
  fetchMenu()
})
</script>

<template>
  <nav v-if="menu">
    <ul>
      <li v-for="item in topLevelItems" :key="item.id">
        <NuxtLinkLocale :to="item.url">
          {{ item.label }}
        </NuxtLinkLocale>
      </li>
    </ul>
  </nav>
</template>
```

---

## 🎯 Using Theme Components

### Add Theme Header to Layout

```vue
<!-- layouts/default.vue -->
<template>
  <div>
    <ThemeHeader />
    <main>
      <slot />
    </main>
    <ThemeFooter />
  </div>
</template>

<script setup lang="ts">
// Components auto-imported by Nuxt
</script>
```

---

### Create Custom Block Component

**Step 1**: Create component in `app/components/theme/blocks/`

```vue
<!-- app/components/theme/blocks/CustomBlock.vue -->
<template>
  <div class="custom-block" :style="blockStyles">
    <h3>{{ block.settings?.title }}</h3>
    <p>{{ block.settings?.content }}</p>
  </div>
</template>

<script setup lang="ts">
import type { ThemeBlock } from '~~/types/theme'

const props = defineProps<{
  block: ThemeBlock
}>()

const blockStyles = computed(() => ({
  backgroundColor: props.block.settings?.backgroundColor,
  color: props.block.settings?.textColor,
  padding: props.block.settings?.padding || '1rem',
}))
</script>

<style scoped>
.custom-block {
  border-radius: var(--layout-border-radius);
}
</style>
```

**Step 2**: Register in section component

```vue
<!-- app/components/theme/sections/HeaderSection.vue -->
<script setup lang="ts">
import CustomBlock from '../blocks/CustomBlock.vue'

const blockComponentMap: Record<string, Component> = {
  logo: LogoBlock,
  navigation_menu: NavigationMenuBlock,
  custom: CustomBlock, // ← Add here
  // ... other blocks
}
</script>
```

**Step 3**: Add to backend theme configuration

```php
// In Laravel backend
$section->blocks()->create([
    'block_type' => 'custom',
    'position' => 1,
    'is_visible' => true,
    'settings' => [
        'title' => 'Welcome',
        'content' => 'Hello World',
        'backgroundColor' => '#f0f0f0',
        'textColor' => '#333333',
    ],
]);
```

---

## 🔧 Utility Functions

### Token Extraction

```typescript
import { extractThemeTokens, extractGoogleFonts } from '~/utils/themeTokens'

// Extract CSS tokens from theme
const tokens = extractThemeTokens(theme.value)
// Returns: { '--color-primary': '#003D29', ... }

// Extract Google Fonts
const fonts = extractGoogleFonts(theme.value)
// Returns: ['Inter', 'Roboto']
```

---

### CSS Injection

```typescript
import { injectThemeTokens, loadGoogleFonts } from '~/utils/cssInjector'

// Inject CSS variables
injectThemeTokens(tokens)

// Load Google Fonts
loadGoogleFonts(['Inter', 'Roboto'])

// Remove fonts
removeGoogleFonts()
```

---

### Font Loading

```typescript
import { 
  loadGoogleFonts, 
  loadCustomFont,
  isFontAvailable,
  waitForFontsLoad 
} from '~/utils/fontLoader'

// Load Google Fonts with options
loadGoogleFonts(['Inter'], [400, 500, 600, 700], 'swap')

// Load custom font
loadCustomFont('MyFont', '/fonts/myfont.woff2', 400, 'normal', 'woff2')

// Check if font is available
const available = await isFontAvailable('Inter')

// Wait for fonts to load
await waitForFontsLoad(3000) // 3 second timeout
```

---

## 🎨 Styling Best Practices

### Use CSS Variables

✅ **Good** - Uses dynamic theme:
```css
.button {
  background-color: var(--color-primary);
  color: var(--white);
}
```

❌ **Bad** - Hardcoded color:
```css
.button {
  background-color: #003D29;
  color: white;
}
```

---

### Use Token Aliases

✅ **Good** - Uses semantic alias:
```css
.primary-button {
  background-color: var(--btn-primary-bg);
  color: var(--btn-primary-text);
}

.primary-button:hover {
  background-color: var(--btn-primary-hover);
}
```

❌ **Bad** - Directly manipulates color:
```css
.primary-button:hover {
  background-color: color-mix(in srgb, var(--color-primary) 90%, black);
}
```

---

### Support Dark Mode

```css
.card {
  /* Use semantic colors that adapt to dark mode */
  background-color: var(--color-background);
  color: var(--color-text);
  border: 1px solid var(--color-border);
}

/* Dark mode automatically handled by CSS variables */
```

---

### Support RTL

```css
.menu-item {
  /* Use logical properties for RTL support */
  margin-inline-start: 1rem;  /* Instead of margin-left */
  padding-inline-end: 0.5rem; /* Instead of padding-right */
}
```

---

## 🐛 Debugging

### Check Applied Tokens

```javascript
// In browser console
const root = document.documentElement
const styles = getComputedStyle(root)

// Check specific token
console.log(styles.getPropertyValue('--color-primary'))
// Output: "#003D29"

// List all theme tokens
const allProps = Array.from(document.styleSheets)
  .flatMap(sheet => Array.from(sheet.cssRules || []))
  .filter(rule => rule.type === 1)
  .flatMap(rule => Array.from(rule.style))
  .filter(prop => prop.startsWith('--'))
console.table(allProps)
```

---

### Check Theme Data

```javascript
// In Vue DevTools or browser console
const { theme } = useStoreTheme()

console.log('Theme:', theme.value)
console.log('Sections:', theme.value?.sections)
console.log('Settings:', theme.value?.settings)
```

---

### Clear Cache

```typescript
// In component
const { clearCache, refresh } = useStoreTheme()

// Clear cache and refetch
await refresh()

// Or just clear cache
clearCache()
```

---

### Check Loaded Fonts

```javascript
// In browser console
// Check loaded fonts
console.log(document.fonts)

// Check specific font
document.fonts.check('16px Inter')
// Returns: true if loaded

// Wait for all fonts
await document.fonts.ready
console.log('All fonts loaded!')
```

---

## 📝 TypeScript Types

### Theme Type

```typescript
import type { 
  Theme, 
  ThemeSection, 
  ThemeBlock,
  ThemeSettings,
  ThemeTokens 
} from '~~/types/theme'

// Use in component
const theme: Ref<Theme | null> = ref(null)
const section: ThemeSection = { /* ... */ }
const block: ThemeBlock = { /* ... */ }
```

---

### Navigation Type

```typescript
import type { 
  NavigationMenu, 
  NavigationMenuItem 
} from '~~/types/navigation'

// Use in component
const menu: Ref<NavigationMenu | null> = ref(null)
const item: NavigationMenuItem = { /* ... */ }
```

---

## 🚀 Performance Tips

### 1. Use Cache

```typescript
// Theme automatically caches in sessionStorage
// Cache TTL: 5 minutes
// No manual caching needed!
```

---

### 2. Preload Critical Fonts

```typescript
import { preloadFont } from '~/utils/fontLoader'

// Preload critical fonts
preloadFont('/fonts/Inter-Regular.woff2', 'woff2')
```

---

### 3. Lazy Load Components

```vue
<script setup lang="ts">
// Lazy load theme components
const ThemeHeader = defineAsyncComponent(() => 
  import('~/components/theme/ThemeHeader.vue')
)
</script>
```

---

### 4. Code Split Utilities

```typescript
// Already done! Utilities use dynamic imports
const { extractThemeTokens } = await import('~/utils/themeTokens')
```

---

## 📚 Further Reading

- **SESSION_13_COMPLETE.md** - Foundation and composables
- **SESSION_14_COMPLETE.md** - Header components
- **SESSION_15_COMPLETE.md** - Footer components
- **SESSION_16_COMPLETE.md** - Token integration
- **STOREFRONT_THEME_INTEGRATION_COMPLETE.md** - Project summary

---

## 💡 Common Use Cases

### Use Case 1: Custom Branded Button

```vue
<template>
  <button class="brand-button">
    <slot />
  </button>
</template>

<style scoped>
.brand-button {
  background: linear-gradient(
    135deg, 
    var(--color-primary), 
    var(--color-secondary)
  );
  color: var(--white);
  font-family: var(--font-heading);
  padding: calc(var(--layout-spacing-unit) * 2);
  border-radius: var(--layout-border-radius);
  border: none;
  cursor: pointer;
  transition: transform 0.2s;
}

.brand-button:hover {
  transform: translateY(-2px);
}
</style>
```

---

### Use Case 2: Themed Card Component

```vue
<template>
  <div class="themed-card">
    <slot />
  </div>
</template>

<style scoped>
.themed-card {
  background-color: var(--card-bg);
  border: 1px solid var(--card-border);
  border-radius: var(--layout-border-radius);
  box-shadow: var(--card-shadow);
  padding: calc(var(--layout-spacing-unit) * 3);
  color: var(--color-text);
}

.themed-card:hover {
  border-color: var(--color-primary);
}
</style>
```

---

### Use Case 3: Dynamic Hero Section

```vue
<template>
  <section class="hero" :style="heroStyles">
    <div class="container">
      <h1>{{ title }}</h1>
      <p>{{ subtitle }}</p>
    </div>
  </section>
</template>

<script setup lang="ts">
const { colors, typography } = useStoreTheme()

const heroStyles = computed(() => ({
  backgroundColor: colors.value.primary,
  color: colors.value.background,
  fontFamily: typography.value.heading,
}))

defineProps<{
  title: string
  subtitle: string
}>()
</script>

<style scoped>
.hero {
  padding: 4rem 0;
  text-align: center;
}

.container {
  max-width: var(--layout-container-width);
  margin: 0 auto;
  padding: 0 var(--layout-spacing-unit);
}
</style>
```

---

**Happy Theming!** 🎨✨
