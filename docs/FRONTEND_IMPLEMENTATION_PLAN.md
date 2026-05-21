# Phase 2 — Layouts & Adaptive Shell

> **Parent:** [Frontend Implementation Plan](FRONTEND_IMPLEMENTATION_PLAN.md)  
> **PRD Ref:** §6 Adaptive Design Strategy, §15 Dark Mode  
> **Status:** ✅ Approved

---

## 1. Design Concept — Three Adaptive Layouts

*(Adaptive Layout Concept)*

The three layouts are designed as **distinct reading experiences** of the same notebook, not mere responsive reflows:

| Layout | Metaphor | Key Characteristics |
|--------|----------|-------------------|
| **Mobile** | A pocket journal | Bottom tab bar for thumb reach, stacked card entries, full-screen forms |
| **Tablet** | A planner on a desk | Collapsible sidebar as a "table of contents" page (starts collapsed/icon-only), 2-column card grid |
| **Desktop** | An open book spread | Fixed sidebar as the book's spine/index, topbar as the chapter header, table view for data |

---

## 2. Layout Architecture Diagrams

### 2.1 Mobile Layout (< 768px, touch device)

```
┌─────────────────────────────┐
│  MobileHeader               │
│  ┌─────────────────────┐    │
│  │ FlowDo (serif)    🔔│    │
│  └─────────────────────┘    │
├─────────────────────────────┤
│                             │
│  <router-view />            │
│  (Full height scroll area)  │
│                             │
│  Cards stacked vertically   │
│  48px min tap targets       │
│  16px base font             │
│                             │
├─────────────────────────────┤
│  MobileBottomNav            │
│  ┌──────┬──────┬──────┬───┐ │
│  │Tasks │Today │ Tags │⚙️ │ │
│  │ 📝  │ 📅(3)│  🏷️ │   │ │
│  └──────┴──────┴──────┴───┘ │
└─────────────────────────────┘
```

**Design Details:**
- **MobileHeader**: Fixed at top, 56px height. App name "FlowDo" in serif `font-heading`, notification bell icon with badge counter. Background: `surface-elevated` with `paper-border` bottom.
- **Content Area**: Scrollable `<router-view>`, padded 16px, cream paper background.
- **MobileBottomNav**: Fixed at bottom, 64px height. 4 tabs with Lucide icons + labels. Active tab uses `accent` color with sepia ink underline. Safe area padding for notched devices.
- **Due Today Badge**: Shows a clear numerical badge count (e.g., "3") when there are active tasks due today.

---

### 2.2 Tablet Layout (768px–1023px, touch device)

```
┌──────────────────────────────────────────┐
│                                          │
│  ┌────┐ ┌───────────────────────────────┐│
│  │Sidebar│ Content Area                 ││
│  │    │ │                               ││
│  │ ■  │ │  <router-view />              ││
│  │ 📅 │ │                               ││
│  │ 🏷️ │ │  2-column card grid           ││
│  │    │ │  or full-width content        ││
│  │ ── │ │                               ││
│  │ ⚙️  │ │                               ││
│  │    │ │                               ││
│  └────┘ └───────────────────────────────┘│
│   64px             remaining width
```

**Design Details:**
- **TabletSidebar**: Starts **collapsed (icon-only, 64px wide) by default** to maximize reading space. Can expand to 240px via toggle. Contains: app logo area (collapsed to single letter "F" or minimized icon), navigation list styled as standard icons, and bottom settings icon.
- **Content Area**: Takes remaining width. Page scroll area with 24px padding.
- **Collapse/Expand Animation**: 300ms `cubic-bezier(0.16, 1, 0.3, 1)` width transition. Text labels fade in/out with opacity transition.

---

### 2.3 Desktop Layout (≥ 1024px)

```
┌──────────────────────────────────────────────────────┐
│ ┌────────────┐┌──────────────────────────────────────┐│
│ │            ││  DesktopTopbar                       ││
│ │  Desktop   ││  ┌────────────────────────────────┐  ││
│ │  Sidebar   ││  │ 📄 My Tasks          🔔 🌙 👤 │  ││
│ │            ││  └────────────────────────────────┘  ││
│ │  FlowDo    ││──────────────────────────────────────││
│ │  ════════  ││                                      ││
│ │            ││  Content Area                        ││
│ │  ■ Tasks   ││  <router-view />                     ││
│ │    Today   ││                                      ││
│ │    Tags    ││  Data table with sortable headers    ││
│ │            ││  or side-panel forms                 ││
│ │  ────────  ││                                      ││
│ │  ⚙ Settings││                                      ││
│ │            ││                                      ││
│ └────────────┘└──────────────────────────────────────┘│
└──────────────────────────────────────────────────────┘
     240px                  remaining width
```

**Design Details:**
- **DesktopSidebar**: Fixed 240px, full height. Styled as a **notebook spine**: left edge has a 4px `accent` color border (leather binding), dark elevated background. Navigation items are styled as a chapter index with `font-heading` serif text. Active item: sepia ink left indicator bar (3px thick).
- **DesktopTopbar**: 64px height, fixed. Contains: current page title (serif heading), notification bell with due-today count badge, theme toggle (sun/moon icon), and user avatar/menu dropdown. Background: `surface-elevated`, bottom `paper-border`.
- **Content Area**: Scrollable main area, 32px padding.

---

### 2.4 Auth Layout (All platforms)

```
┌──────────────────────────────────────┐
│                                      │
│          Cream background            │
│          with paper texture          │
│                                      │
│     ┌──────────────────────┐         │
│     │  ╔══════════════╗    │         │
│     │  ║   FlowDo     ║    │         │
│     │  ║              ║    │         │
│     │  ║  [email   ] ║    │         │
│     │  ║  [password ] ║    │         │
│     │  ║              ║    │         │
│     │  ║  [ Login  ]  ║    │         │
│     │  ║              ║    │         │
│     │  ╚══════════════╝    │         │
│     │  stacked paper shadow│         │
│     └──────────────────────┘         │
│                                      │
└──────────────────────────────────────┘
```

**Design Details:**
- Centered paper card (max-width: 420px on desktop, full-width on mobile with margin).
- Card uses `surface-elevated` background, `paper-shadow-lg`, and `paper-border`.
- App title "FlowDo" in `font-heading` at the top of the card with 2rem size.
- Input fields styled with only bottom-border (like writing on a ruled line).
- No sidebar or nav bar — clean, focused authentication experience.

---

## 3. Composable Specifications

### 3.1 `useDeviceDetection.ts` — Platform Detection

Uses `@vueuse/core` `useBreakpoints` for reactive viewport detection, combined with touch capability check.

```typescript
// Composable Contract
type Platform = 'mobile' | 'tablet' | 'desktop'

interface UseDeviceDetectionReturn {
  platform: ComputedRef<Platform>       // Current active platform
  override: Ref<Platform | null>        // Manual override from localStorage
  isMobile: ComputedRef<boolean>        // Convenience helper
  isTablet: ComputedRef<boolean>        // Convenience helper
  isDesktop: ComputedRef<boolean>       // Convenience helper
  setOverride: (p: Platform | null) => void  // Set/clear override
}
```

**Detection Logic (per PRD §6.1):**

| Priority | Condition | Result |
|----------|-----------|--------|
| 1 | `override !== null` | Use override value |
| 2 | `width < 768 && isTouchDevice` | `'mobile'` |
| 3 | `768 ≤ width < 1024 && isTouchDevice` | `'tablet'` |
| 4 | Default | `'desktop'` |

---

### 3.2 `useTheme.ts` — Theme Switcher

Per PRD §15, manages Light/Dark/System theme with class-based toggling.

```typescript
// Composable Contract
type ThemePreference = 'light' | 'dark' | 'system'

interface UseThemeReturn {
  preference: Ref<ThemePreference>       // User's stored preference
  isDark: ComputedRef<boolean>           // Resolved: is dark active?
  setTheme: (t: ThemePreference) => void // Update preference
}
```

---

## 4. Component Specifications

### 4.1 `AdaptiveRoot.vue`

The **routing wrapper** that switches the layout shell based on the detected platform.

```vue
<script setup lang="ts">
import { useDeviceDetection } from '@/composables/useDeviceDetection'
import MobileLayout from './mobile/MobileLayout.vue'
import TabletLayout from './tablet/TabletLayout.vue'
import DesktopLayout from './desktop/DesktopLayout.vue'

const { platform } = useDeviceDetection()

const layoutMap = {
  mobile: MobileLayout,
  tablet: TabletLayout,
  desktop: DesktopLayout,
} as const
</script>

<template>
  <component :is="layoutMap[platform]">
    <router-view />
  </component>
</template>
```

---

### 4.2 Desktop Components

#### `DesktopLayout.vue`
- CSS Grid: `grid-template-columns: 240px 1fr`
- Full viewport height: `min-h-screen`

#### `DesktopSidebar.vue`
- Fixed position, full height, table of contents styling.
- Main navigation: Tasks, Due Today, Tags, Settings.
- Spine-style left border (4px accent) with dark elevated background.

#### `DesktopTopbar.vue`
- Topbar: dynamically loads route title, showing notification bell, theme toggle, and user settings dropdown.

---

### 4.3 Tablet Components

#### `TabletLayout.vue`
- Flex row layout. Collapsible sidebar width transition (64px collapsed to 240px expanded). Collapsed by default.

#### `TabletSidebar.vue`
- Collapsed by default. Displays central Lucide icons. Expanded on toggle click showing text labels with transition.

---

### 4.4 Mobile Components

#### `MobileLayout.vue`
- Flex column: header (fixed top) → content (flex-1) → bottom nav (fixed bottom).

#### `MobileHeader.vue`
- 56px fixed top header with brand heading and notification bell.

#### `MobileBottomNav.vue`
- 64px bottom nav with Tasks, Due Today, Tags, Settings.
- Due Today tab displays a custom numerical badge count indicator.

---

### 4.5 `AuthLayout.vue`

- Centered flex-box with paper grain texture. Card body has stacked `paper-shadow-lg`, and `paper-border`.

---

## 5. Routing Integration

```typescript
const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/auth',
    component: AuthLayout,
    meta: { requiresGuest: true },
    children: [
      { path: 'login', name: 'login', component: () => import('@/features/auth/views/LoginView.vue') },
      { path: 'register', name: 'register', component: () => import('@/features/auth/views/RegisterView.vue') }
    ]
  },
  {
    path: '/login',
    redirect: '/auth/login'
  },
  {
    path: '/register',
    redirect: '/auth/register'
  },
  {
    path: '/',
    component: AdaptiveRoot,
    meta: { requiresAuth: true },
    children: [
      { path: 'dashboard', name: 'dashboard', component: () => import('@/features/tasks/views/TaskDashboard.vue') },
      { path: 'tasks/today', name: 'today-tasks', component: () => import('@/features/tasks/views/TodayTasksView.vue') },
      { path: 'tasks/new', name: 'add-task', component: () => import('@/features/tasks/views/AddTaskView.vue') },
      { path: 'tasks/:id/edit', name: 'edit-task', component: () => import('@/features/tasks/views/EditTaskView.vue') },
      { path: 'settings', name: 'settings', component: () => import('@/features/settings/views/SettingsView.vue') }
    ]
  }
]
```

---

## 6. Verification & Testing Plan

### Automated Unit Tests (Vitest)
- [ ] Test `useDeviceDetection.ts` logic with mock `window.innerWidth` and touch capabilities.
- [ ] Test `useTheme.ts` state changes and `localStorage` integration.
- [ ] Test `AdaptiveRoot.vue` layout selection.

### Manual Verification (User Handled)
- [ ] Toggle responsive viewports to verify layout swap.
- [ ] Confirm tablet sidebar is collapsed by default (icon only).
- [ ] Confirm "Due Today" shows badge count on mobile bottom nav.
- [ ] Confirm dark mode styles.
