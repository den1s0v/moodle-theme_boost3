# theme_boost3: making Moodle 4.5 look a bit like Moodle 3.9+.

Grandchild theme of **Boost Union** with an optional consolidated **gear-style** menu for course navigation.

## Install

1. Copy this folder into your Moodle tree as `theme/boost3/` (folder name **`boost3`**, component **`theme_boost3`**).
2. Ensure **Boost Union** is installed under `theme/boost_union/`.
3. Purge caches (required after upgrades), then set **Boost3** as the site theme in *Site administration → Appearance → Themes*.

After updating theme files, run **Purge all caches** or `php admin/cli/purge_caches.php` so Mustache/SCSS changes apply.

## Requirements

- Moodle 4.5.x (matches `version.php` `requires` / `supported`).
- `theme_boost_union` MOODLE_405_STABLE (or compatible).

## Theme settings

*Site administration → Appearance → Themes → Boost3 settings*

| Setting | Description |
|---------|-------------|
| **Gear-style course menu** | When enabled (default), Boost3 navigation behaviour below applies. When disabled, the site uses standard Boost horizontal secondary tabs and the tertiary `url_select` with no gear menu. Purge caches after toggling. |

## Navigation behaviour (gear menu enabled)

| Page type | Course tabs (secondary) | Gear menu |
|-----------|-------------------------|-----------|
| Site **administration** (`/admin/…`) | Standard horizontal tabs | Hidden |
| **Course** pages (home, modules, etc.) | In the gear menu | Course tabs (Курс, Участники, …) |
| **Course subsection** with tertiary nav (e.g. Enrolled users, Groups) | Standard horizontal tabs | Section links (former `tertiary-navigation` dropdown) |

On subsection pages the default `tertiary-navigation` url_select block is hidden; those links appear in the gear menu instead. Action buttons in the participants action bar (e.g. **Enrol users**) stay visible next to the gear menu.

## Notes

- Configure colours, drawers, and most behaviour in **Boost Union**; this theme adds the gear menu and related SCSS only (not on admin pages).
- The gear icon is an inline SVG in `templates/theme_boost3/gear_menu.mustache` (Bootstrap Icons gear-fill, `currentColor` for styling in `scss/post.scss`).
- If you deploy from this repo, rename or symlink `boost3/` → `moodle/theme/boost3/` on the server.
