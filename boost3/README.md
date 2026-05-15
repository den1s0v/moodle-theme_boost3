# theme_boost3 (Moodle 4.5)

Grandchild theme of **Boost Union** with a consolidated **gear-style** menu (secondary navigation + common course actions) and SCSS that hides the horizontal secondary navigation bar.

## Install

1. Copy this folder into your Moodle tree as `theme/boost3/` (folder name **`boost3`**, component **`theme_boost3`**).
2. Ensure **Boost Union** is installed under `theme/boost_union/`.
3. Purge caches, then set **Boost3** as the site theme (or a user theme) in *Site administration → Appearance → Themes*.

## Requirements

- Moodle 4.5.x (matches `version.php` `requires` / `supported`).
- `theme_boost_union` MOODLE_405_STABLE (or compatible).

## Notes

- Configure colours, drawers, and most behaviour in **Boost Union**; this theme adds the gear menu and related SCSS only.
- If you deploy from this repo, rename or symlink `boost3/` → `moodle/theme/boost3/` on the server.
