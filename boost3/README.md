# theme_boost3 (Moodle 4.5)

Grandchild theme of **Boost Union** with a consolidated **gear-style** menu (secondary navigation on course and similar pages). Site **administration** pages (`/admin/…`, `pagelayout=admin`) keep Boost’s default horizontal secondary navigation.

## Install

1. Copy this folder into your Moodle tree as `theme/boost3/` (folder name **`boost3`**, component **`theme_boost3`**).
2. Ensure **Boost Union** is installed under `theme/boost_union/`.
3. Purge caches (required after upgrades), then set **Boost3** as the site theme in *Site administration → Appearance → Themes*.

After updating theme files, run **Purge all caches** or `php admin/cli/purge_caches.php` so Mustache/SCSS changes apply.

## Requirements

- Moodle 4.5.x (matches `version.php` `requires` / `supported`).
- `theme_boost_union` MOODLE_405_STABLE (or compatible).

## Notes

- Configure colours, drawers, and most behaviour in **Boost Union**; this theme adds the gear menu and related SCSS only (not on admin pages).
- If you deploy from this repo, rename or symlink `boost3/` → `moodle/theme/boost3/` on the server.
