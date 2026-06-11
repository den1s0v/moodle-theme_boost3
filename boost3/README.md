# theme_boost3

Child theme of **Boost Union** for Moodle **4.5**. Brings Moodle 3.9-style **legacy left drawer** and **gear menu** while keeping Boost Union features.

## Requirements

- Moodle 4.5.x
- Parent theme: `theme_boost_union`

Install as `moodle/theme/boost3/`. Set as default theme. **Purge all caches** after install or upgrade.

## Navigation features (both default **off**)

| Setting | Purpose |
|---------|---------|
| **Legacy left navigation drawer** | Flat course / site / my courses list instead of activity tree |
| **Gear-style course menu** | Consolidates secondary tabs and tertiary overflow |

### Behaviour matrix

| Legacy | Gear | Secondary tabs | Course tabs not in drawer whitelist |
|--------|------|----------------|-------------------------------------|
| off | off | Standard Boost | In horizontal tabs |
| off | on | Hidden on simple pages; gear or overflow | In gear |
| on | on | Hidden | Drawer + gear (excluded tabs) |
| on | off | Hidden | **Automatic gear fallback** — excluded tabs still reachable |

Project decision: with legacy drawer on, horizontal secondary tabs are hidden. Tabs not listed in `legacydrawercoursekeys` must not disappear — gear is **effectively enabled** for those pages even when the gear setting is off.

## Key settings

- **legacydrawercoursekeys** — secondary nav keys in the drawer (default: editsettings, participants, competencies, grades)
- **legacydrawercoursekeyaliases** — map keys to site-specific node names (`canonical=alias1,alias2`)
- **legacydrawersitekeys** — global_navigation keys for site block (default: home, contentbank)
- **legacydrawersectionformats** — formats that get flat section links (default: topics, weeks)
- **legacydrawersectionlinks** — `sectionpage` or `anchor`
- **legacydrawergearexcludedkeys** — keys skipped for settingsnav overflow in gear

See language strings in theme settings for the full key catalog.

Navigation matrix: [docs/NAVIGATION-DECISIONS.md](docs/NAVIGATION-DECISIONS.md)

## Architecture (reliability)

- **`classes/page_classifier.php`** — page kind detection (KIND_*)
- **`classes/navigation_matrix.php`** — declarative channel defaults per kind
- **`classes/navigation_channel_profile.php`** — channel profile DTO
- **`classes/navigation_policy.php`** — resolves matrix + runtime modifiers (settings, overflow)
- **`classes/active_state_resolver.php`** — unified active highlighting (course home vs section vs my courses)
- **`lib.php`** — settings parsers, thin wrappers, template flags
- **`classes/output/core_renderer.php`** — builds drawer and gear item trees only

## Forked Union templates

These files override Boost Union / Boost — review on every Union upgrade:

- `templates/theme_boost/drawers.mustache`
- `templates/theme_boost/navbar.mustache`
- `layout/drawers.php`

Boost3-specific fragments: `templates/theme_boost3/*`, `amd/src/legacy_drawer_toggle.js`.

Regression steps: [docs/REGRESSION-CHECKLIST.md](docs/REGRESSION-CHECKLIST.md)  
Design notes: [docs/plan-left-drawer-m39.md](docs/plan-left-drawer-m39.md)

## Development

Version bump in `version.php` triggers Moodle upgrade and SCSS rebuild. Purge caches after template or AMD changes.
