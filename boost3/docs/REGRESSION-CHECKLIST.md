# Regression checklist — theme_boost3 navigation

Run after theme upgrade, Boost Union merge, or navigation logic changes. Purge all caches first.

## Settings matrix (smoke)

| legacy drawer | gear menu | Expected |
|---------------|-----------|----------|
| off | off | Standard Boost secondary tabs; course index tree in left drawer |
| off | on | Gear on course pages; overflow → gear |
| on | on | Legacy flat drawer; whitelisted tabs in drawer; rest in gear |
| on | off | Legacy drawer; excluded tabs still in **automatic gear fallback** (no lost tabs) |

## Page types

- [x] **Course home** (`course/view.php`) — only course title active in drawer; my courses entries not all active
- [ ] **Course section** (`course/section.php` or `#section-N`) — not run on m45 id=2
- [x] **Participants** (`user/index.php`) — gear shows tertiary; in-content combobox hidden when gear effective
- [ ] **Question bank** — not run
- [x] **Reports / excluded tab** — gear on overflow pages (LTI coursetools, course home)
- [x] **Course edit** (`course/edit.php`) — no legacy drawer; standard admin UI (fixed 2026-06-13)
- [x] **Course tool** (`/mod/lti/coursetools.php`) — legacy drawer; not site admin
- [x] **Site admin** (`/admin/settings.php`) — no legacy drawer

See [REGRESSION-RESULTS-2026-06-13.md](REGRESSION-RESULTS-2026-06-13.md) for m45 run details.

## Drawer / toggle

- [ ] Hamburger visible only when drawer has content (`boost3_legacy_nav_toggle`)
- [ ] Empty legacy config (no site keys, no my courses) — no dead hamburger
- [ ] Toggle opens/closes drawer; aria-expanded updates

## Formats

- [ ] **topics / weeks** — section links in drawer (per `legacydrawersectionformats`)
- [ ] **singleactivity / other** — no section list unless format added to setting

## Union upgrade

After Boost Union update, diff and merge:

- `templates/theme_boost/drawers.mustache`
- `templates/theme_boost/navbar.mustache`
- `layout/drawers.php`

Preserve `theme_boost3/*` partial includes and `theme_boost3_append_drawer_nav_flags()` call.
