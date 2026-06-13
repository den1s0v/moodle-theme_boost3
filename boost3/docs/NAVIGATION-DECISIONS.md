# Navigation display decisions — theme_boost3

Single source of truth for **which UI channels** appear on each page kind when legacy drawer and/or gear menu are enabled.

**Code (authoritative):** [`classes/navigation_matrix.php`](../classes/navigation_matrix.php) + [`classes/page_classifier.php`](../classes/page_classifier.php) → resolved by [`classes/navigation_policy.php`](../classes/navigation_policy.php).  
**This document** is a human-readable mirror — update both when UX rules change.

**Breadcrumbs implementation:** [`classes/breadcrumb_builder.php`](../classes/breadcrumb_builder.php), [`classes/boostnavbar.php`](../classes/boostnavbar.php).

**Requires:** `enablelegacydrawer=1` for legacy drawer + M3.9 breadcrumbs unless noted.

---

## Channels

| Channel | Meaning |
|---------|---------|
| **Legacy drawer** | Flat M3.9 left nav (`legacy_nav_drawer`) — replaces course index tree |
| **Secondary tabs** | Horizontal `core/moremenu` under header |
| **Gear** | Consolidated dropdown (`gear_menu`) — excluded secondary tabs + tertiary overflow |
| **Breadcrumbs** | M3.9 chain via `breadcrumb_builder` + `boostnavbar` |
| **Tertiary in content** | `#action_bar` / layout `url_select` |

---

## Matrix (legacy drawer ON)

| Page kind | Example | Legacy drawer | Secondary tabs | Gear | Breadcrumbs | Notes |
|-----------|---------|:-------------:|:--------------:|:----:|:-----------:|-------|
| `course_format` | `course/view.php` | yes | no | yes* | yes | Gear in header row; overflow tabs in gear |
| `course_secondary` | badges, LTI tools | yes | no | yes* | yes | Gear in header when overflow/excluded |
| **`gradebook`** | **`/grade/*`** | **yes** | **no** | **no** | **yes** | **M3.9 two-row tabs** in content; no gear; core tertiary hidden |
| `participants` | `user/index.php`, `renameroles.php` | yes | no | yes* | yes | Tertiary in gear; action buttons stay in content |
| **`course_admin`** | **`course/edit.php`** | **yes** | **yes** | **no** | **core** | **M3.9 drawer + horizontal course tabs; no gear** (tabs are enough) |
| `site_admin` | `/admin/settings.php` | no | yes | no | no | Standard Boost Union |
| `module` | `mod/*/view.php` | yes | no | yes* | yes (M3.9) | Core mod crumbs + ЛК/Мои курсы prefix |

\*Gear when `enablegearmenu=1` **or** automatic fallback (excluded secondary tabs / overflow).

---

## Deliberate exceptions

### `course/edit.php` (accepted 2026-06-13)

- **Legacy drawer:** ON — same flat nav as M3.9 (user reverted exclusion from `legacy_drawer_active_for_page`).
- **Secondary tabs:** ON — Moodle 4.x horizontal course navigation; adds value without removing M3.9 drawer.
- **Gear:** OFF — tabs already expose course navigation; no duplicate menu.
- **Breadcrumbs:** core/Union chain — `Личный кабинет → Курсы → {категория} → {курс} → Настройки` (category breadcrumbs from Boost Union; not replaced by M3.9 builder).
- **Must not** fall back to M4.5 course index tree when legacy mode is active (`layout/drawers.php`).

### Site administration

- No legacy drawer, no gear, no M3.9 breadcrumbs.

### Gradebook (`/grade/*`)

- **Legacy drawer:** ON — «Оценки» in drawer (same as M3.9).
- **Gear:** OFF — internal gradebook nav is not consolidated into gear (unlike other course secondary pages with overflow).
- **Grade navigation:** Two rows of `nav-tabs` in `.grade-navigation` via [`classes/grade_navigation_builder.php`](../classes/grade_navigation_builder.php) (data from core `grade_get_plugin_info()`).
- **Core tertiary dropdown:** hidden when custom tabs render (`theme-boost3-grade-tabs` body class).
- **Breadcrumbs:** M3.9 chain (same builder as other course pages).

See [plan-gradebook-m39.md](plan-gradebook-m39.md) for spatial tab map (row 1.x / 2.x).

---

## Gear placement

| Condition | Gear position |
|-----------|---------------|
| Legacy drawer ON + gear shown | Inline in `#page-header` (next to H1) |
| Legacy OFF + overflow (split) | Secondary strip beside horizontal tabs |
| Legacy OFF + gear-only (course home) | Inline in header |

---

## Settings

| Setting | Default | Role |
|---------|---------|------|
| `enablelegacydrawer` | 0 | Master switch for legacy drawer + breadcrumb builder |
| `enablegearmenu` | 0 | Gear menu; when off, gear still appears for excluded tabs if legacy drawer on |
| `legacydrawercoursekeys` | editsettings, participants, … | Tabs shown **in drawer** (not in gear) |

---

## Regression

See [REGRESSION-CHECKLIST.md](REGRESSION-CHECKLIST.md) and latest [REGRESSION-RESULTS-*.md](REGRESSION-RESULTS-2026-06-13.md).
