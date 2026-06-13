# Regression results — m45 (2026-06-13)

**Environment:** https://m45.umu.vstu.ru, user juliet, course id=2 («Тест LD»).  
**Settings (assumed):** `enablelegacydrawer=1`, `enablegearmenu=1` (or effective gear via overflow).

## Code changes in this session

1. **Stage B** — `navigation_policy`: gear in `#page-header` when legacy drawer is on (removed forced `gear_inline_header=false`).
2. **Fix** — `theme_boost3_legacy_drawer_active_for_page()`: exclude `pagetype=course-edit`.

Re-test after auto-sync + purge caches.

---

## Settings matrix

| legacy | gear | Status | Notes |
|--------|------|--------|-------|
| on | on | **smoke OK** | Primary test config on m45 |
| off | off | not tested | Requires theme settings toggle |
| off | on | not tested | |
| on | off (fallback) | not tested | Automatic gear for excluded tabs |

---

## Page types (legacy+gear on)

| Check | Result | Notes |
|-------|--------|-------|
| Course home | **PASS** | Drawer: only «Тест LD» active; gear present; no secondary tabs |
| Course section | not tested | No section.php link verified for id=2 |
| Participants | **PASS** | Drawer: «Участники» active; breadcrumb ЛК→Мои курсы→1k→Участники; no `#action_bar` tertiary in HTML |
| Question bank | not tested | |
| Reports / excluded tab | **partial** | Gear on course home / LTI coursetools (overflow tabs) |
| Course edit | **FAIL → fixed** | Was: legacy drawer + toggle on edit page. Fixed: exclude `course-edit` |
| Course tool (LTI) | **PASS** | Legacy drawer; gear; no site-admin treatment |
| Site admin | **PASS** | No legacy drawer/toggle; standard secondary on settings |

---

## Drawer / toggle

| Check | Result |
|-------|--------|
| Toggle when drawer has content | **PASS** |
| Empty config hamburger | not tested |
| Toggle aria-expanded | not tested (manual) |

---

## Stage B — gear in header (legacy on)

| Page | Before fix | After fix (expected) |
|------|------------|----------------------|
| course/view.php | gear in `.secondary-navigation` strip | gear in `#page-header .header-actions-container` |
| user/index.php | gear in secondary strip | gear in header |
| mod/lti/coursetools.php | gear in secondary strip | gear in header |

---

## Known minor issues (not fixed this session)

- Course home breadcrumb shows «Курсы» instead of «Мои курсы» on id=2 (builder/nav node label).
- LTI coursetools: no active drawer item (page not in drawer whitelist — expected).

---

## Post-deploy verification

```text
1. Purge all caches on m45
2. course/view.php?id=2 — gear next to H1, no secondary-gear strip
3. course/edit.php?id=2 — NO legacy drawer, horizontal tabs visible
4. user/index.php?id=2 — gear in header, participants tertiary not duplicated
```

### Verification after sync (2026-06-13 ~14:53 UTC)

| Check | Status |
|-------|--------|
| course/edit — no legacy drawer | **PASS** (synced) |
| Stage B — gear in header | **PENDING** — `navigation_policy.php` not yet on m45; still in `.secondary-navigation` strip |
| Purge caches | Done via admin UI |
