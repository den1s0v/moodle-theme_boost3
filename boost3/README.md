# theme_boost3 (Moodle 4.5)

Grandchild theme of **Boost Union** with a consolidated **gear-style** menu for course navigation.

## Install

1. Copy this folder into your Moodle tree as `theme/boost3/` (folder name **`boost3`**, component **`theme_boost3`**).
2. Ensure **Boost Union** is installed under `theme/boost_union/`.
3. Purge caches (required after upgrades), then set **Boost3** as the site theme in *Site administration → Appearance → Themes*.

After updating theme files, run **Purge all caches** or `php admin/cli/purge_caches.php` so Mustache/SCSS changes apply.

## Requirements

- Moodle 4.5.x (matches `version.php` `requires` / `supported`).
- `theme_boost_union` MOODLE_405_STABLE (or compatible).

## Navigation behaviour

| Page type | Course tabs (secondary) | Gear menu |
|-----------|-------------------------|-----------|
| Site **administration** (`/admin/…`) | Standard horizontal tabs | Hidden |
| **Course** pages (home, modules, etc.) | In the gear menu | Course tabs (Курс, Участники, …) |
| **Course subsection** with tertiary nav (e.g. Enrolled users, Groups) | Standard horizontal tabs | Section links (former `tertiary-navigation` dropdown) |

On subsection pages the default `tertiary-navigation` url_select block is hidden; those links appear in the gear menu instead.

## Notes

- Configure colours, drawers, and most behaviour in **Boost Union**; this theme adds the gear menu and related SCSS only (not on admin pages).
- If you deploy from this repo, rename or symlink `boost3/` → `moodle/theme/boost3/` on the server.
