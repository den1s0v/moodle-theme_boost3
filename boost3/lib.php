<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY IMPLIED WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme Boost3 - Library.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/navigation_channel_profile.php');
require_once(__DIR__ . '/classes/page_classifier.php');
require_once(__DIR__ . '/classes/navigation_matrix.php');
require_once(__DIR__ . '/classes/navigation_policy.php');
require_once(__DIR__ . '/classes/active_state_resolver.php');
require_once(__DIR__ . '/classes/breadcrumb_builder.php');
require_once(__DIR__ . '/classes/boostnavbar.php');
require_once(__DIR__ . '/classes/grade_navigation_builder.php');

/**
 * Whether the Moodle-3-style gear navigation is enabled in theme settings.
 *
 * @return bool
 */
function theme_boost3_gear_navigation_enabled(): bool {
    $value = get_config('theme_boost3', 'enablegearmenu');
    if ($value === false) {
        return false;
    }
    return (bool) $value;
}

/**
 * Whether the Moodle-3-style legacy left navigation drawer is enabled in theme settings.
 *
 * @return bool
 */
function theme_boost3_legacy_drawer_enabled(): bool {
    $value = get_config('theme_boost3', 'enablelegacydrawer');
    if ($value === false) {
        return false;
    }
    return (bool) $value;
}

/**
 * Navigation node keys for the legacy drawer site section (one per line or comma-separated).
 *
 * @return string[]
 */
function theme_boost3_legacy_drawer_site_keys(): array {
    $raw = get_config('theme_boost3', 'legacydrawersitekeys');
    if ($raw === false || trim((string) $raw) === '') {
        return ['home', 'contentbank'];
    }

    $keys = [];
    foreach (preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $key) {
        $key = clean_param($key, PARAM_ALPHANUMEXT);
        if ($key !== '') {
            $keys[] = $key;
        }
    }

    return $keys ?: ['home', 'contentbank'];
}

/**
 * How legacy drawer builds links to course sections.
 *
 * @return string sectionpage|anchor
 */
function theme_boost3_legacy_drawer_section_link_mode(): string {
    $mode = get_config('theme_boost3', 'legacydrawersectionlinks');
    if ($mode === 'anchor') {
        return 'anchor';
    }
    return 'sectionpage';
}

/**
 * Secondary navigation tab keys shown in the legacy drawer course block (ordered).
 *
 * @return string[]
 */
function theme_boost3_legacy_drawer_course_keys(): array {
    $raw = get_config('theme_boost3', 'legacydrawercoursekeys');
    if ($raw === false || trim((string) $raw) === '') {
        return ['editsettings', 'participants', 'competencies', 'grades'];
    }

    $keys = [];
    foreach (preg_split('/\R+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $line) {
        foreach (preg_split('/[\s,]+/', $line, -1, PREG_SPLIT_NO_EMPTY) as $key) {
            $key = clean_param($key, PARAM_ALPHANUMEXT);
            if ($key !== '' && $key !== 'coursehome') {
                $keys[] = $key;
            }
        }
    }

    return $keys ?: ['editsettings', 'participants', 'competencies', 'grades'];
}

/**
 * Alternate secondary-navigation keys that map to a configured drawer key.
 *
 * @param string $key Configured drawer key.
 * @return string[]
 */
function theme_boost3_parse_key_alias_map(string $configname): array {
    $raw = get_config('theme_boost3', $configname);
    if ($raw === false || trim((string) $raw) === '') {
        return [];
    }

    $map = [];
    foreach (preg_split('/\R+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '=') === false) {
            continue;
        }
        [$canonical, $aliases] = explode('=', $line, 2);
        $canonical = clean_param(trim($canonical), PARAM_ALPHANUMEXT);
        if ($canonical === '') {
            continue;
        }
        $variants = [];
        foreach (preg_split('/[\s,]+/', trim($aliases), -1, PREG_SPLIT_NO_EMPTY) as $alias) {
            $alias = clean_param($alias, PARAM_ALPHANUMEXT);
            if ($alias !== '') {
                $variants[] = $alias;
            }
        }
        if ($variants !== []) {
            $map[$canonical] = $variants;
        }
    }

    return $map;
}

/**
 * Alternate secondary-navigation keys that map to a configured drawer key.
 *
 * Site-specific aliases can be set in legacydrawercoursekeyaliases (canonical=alias1,alias2).
 *
 * @param string $key Configured drawer key.
 * @return string[]
 */
function theme_boost3_legacy_drawer_course_key_variants(string $key): array {
    $configured = theme_boost3_parse_key_alias_map('legacydrawercoursekeyaliases');
    if (isset($configured[$key])) {
        return $configured[$key];
    }

    $defaults = [
        'editsettings' => ['editsettings', 'settings', 'courseedit'],
        'participants' => ['participants', 'users'],
        'grades' => ['grades', 'gradeadmin', 'gradebooksetup'],
        'competencies' => ['competencies', 'competency'],
    ];

    return $defaults[$key] ?? [$key];
}

/**
 * Course formats that support flat section links in the legacy drawer.
 *
 * @return string[]
 */
function theme_boost3_legacy_drawer_section_formats(): array {
    $raw = get_config('theme_boost3', 'legacydrawersectionformats');
    if ($raw === false || trim((string) $raw) === '') {
        return ['topics', 'weeks'];
    }

    $formats = [];
    foreach (preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $format) {
        $format = clean_param($format, PARAM_ALPHANUMEXT);
        if ($format !== '') {
            $formats[] = $format;
        }
    }

    return $formats ?: ['topics', 'weeks'];
}

/**
 * Whether the current course format supports section links in the legacy drawer.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_legacy_drawer_supports_section_links(moodle_page $page): bool {
    if (!theme_boost3_page_is_course_scoped_page($page)) {
        return false;
    }
    $format = $page->course->format ?? '';
    return in_array($format, theme_boost3_legacy_drawer_section_formats(), true);
}

/**
 * Secondary navigation keys excluded from settingsnav overflow resolution.
 *
 * @return string[]
 */
function theme_boost3_gear_overflow_excluded_keys(): array {
    $raw = get_config('theme_boost3', 'legacydrawergearexcludedkeys');
    if ($raw === false || trim((string) $raw) === '') {
        return ['coursehome', 'questionbank', 'coursereports'];
    }

    $keys = [];
    foreach (preg_split('/[\s,]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $key) {
        $key = clean_param($key, PARAM_ALPHANUMEXT);
        if ($key !== '') {
            $keys[] = $key;
        }
    }

    return $keys ?: ['coursehome', 'questionbank', 'coursereports'];
}

/**
 * Whether a secondary navigation tab key belongs in the legacy drawer (not the gear menu).
 *
 * @param string $key
 * @return bool
 */
function theme_boost3_legacy_drawer_course_key_allowed(string $key): bool {
    if ($key === '' || $key === 'coursehome') {
        return false;
    }

    foreach (theme_boost3_legacy_drawer_course_keys() as $allowed) {
        if (in_array($key, theme_boost3_legacy_drawer_course_key_variants($allowed), true)) {
            return true;
        }
    }

    return false;
}

/**
 * Whether a secondary navigation node exposes a direct link.
 *
 * @param object $node
 * @return bool
 */
function theme_boost3_secondary_nav_node_has_link($node): bool {
    if (!is_object($node) || !method_exists($node, 'action')) {
        return false;
    }
    $action = $node->action();
    if ($action instanceof moodle_url) {
        return true;
    }
    if (is_string($action) && $action !== '' && $action !== '#') {
        return true;
    }
    return false;
}

/**
 * Whether the gear menu should list course tabs excluded from the legacy drawer.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_has_legacy_gear_secondary_items(moodle_page $page): bool {
    if (!theme_boost3_legacy_drawer_active_for_page($page) || !$page->has_secondary_navigation() || !$page->secondarynav) {
        return false;
    }

    foreach ($page->secondarynav->children as $child) {
        if (!is_object($child) || empty($child->key) || $child->key === 'coursehome') {
            continue;
        }
        if (!theme_boost3_legacy_drawer_course_key_allowed($child->key) && theme_boost3_secondary_nav_node_has_link($child)) {
            return true;
        }
    }

    return false;
}

/**
 * Global gates for legacy drawer (setting, login, layout) — before page-kind matrix.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_legacy_drawer_globally_available(moodle_page $page): bool {
    if (!theme_boost3_legacy_drawer_enabled()) {
        return false;
    }
    if (!isloggedin() || isguestuser()) {
        return false;
    }
    if (in_array($page->pagelayout, ['popup', 'embedded', 'maintenance', 'redirect'], true)) {
        return false;
    }
    return true;
}

/**
 * Whether the legacy left drawer should render on this page.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_legacy_drawer_active_for_page(moodle_page $page): bool {
    if (!theme_boost3_legacy_drawer_globally_available($page)) {
        return false;
    }
    $kind = \theme_boost3\page_classifier::classify($page);
    $profile = \theme_boost3\navigation_matrix::for_kind($kind);
    return $profile->legacydrawerwhenenabled;
}

/**
 * Whether the page operates in a real course context (not site front page).
 *
 * Covers course settings, /admin/tool/* course tools, course role UI, etc.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_is_course_scoped_page(moodle_page $page): bool {
    return \theme_boost3\page_classifier::is_course_scoped_page($page);
}

/**
 * Whether the page is the course settings form (/course/edit.php).
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_is_course_edit_page(moodle_page $page): bool {
    return \theme_boost3\page_classifier::is_course_edit_page($page);
}

/**
 * Whether the page is a dedicated course section view (/course/section.php).
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_is_course_section_page(moodle_page $page): bool {
    return \theme_boost3\page_classifier::is_course_section_page($page);
}

/**
 * Whether the page is the main course view (topics/weeks/singleactivity, not a secondary tab).
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_is_course_format_view(moodle_page $page): bool {
    return \theme_boost3\page_classifier::is_course_format_view($page);
}

/**
 * Whether the page is site administration UI (not course settings / course tools).
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_is_site_admin_page(moodle_page $page): bool {
    return \theme_boost3\page_classifier::is_site_admin_for_drawer($page);
}

/**
 * Whether Moodle 3.9-style breadcrumbs should be injected for this page.
 *
 * @param moodle_page|null $page
 * @return bool
 */
function theme_boost3_should_populate_legacy_breadcrumbs(?moodle_page $page = null): bool {
    global $PAGE;

    $page = $page ?? $PAGE;
    return \theme_boost3\navigation_policy::breadcrumbs_mode_for_page($page)
        === \theme_boost3\navigation_channel_profile::BREADCRUMBS_LEGACY_M39;
}

/**
 * Whether horizontal secondary navigation tabs should be shown.
 *
 * @param moodle_page|null $page
 * @return bool
 */
function theme_boost3_should_show_secondary_tabs(?moodle_page $page = null): bool {
    global $PAGE;

    $page = $page ?? $PAGE;
    $policy = \theme_boost3\navigation_policy::resolve($page);

    return $policy->show_secondary_tabs;
}

/**
 * Whether the participants secondary tab is active.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_has_active_participants_secondary_tab(moodle_page $page): bool {
    return \theme_boost3\page_classifier::has_active_participants_secondary_tab($page);
}

/**
 * Pages that render core participants_action_bar tertiary navigation in content.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_uses_participants_actionbar(moodle_page $page): bool {
    return \theme_boost3\page_classifier::uses_participants_actionbar($page);
}

/**
 * Whether the page is site administration UI.
 *
 * Course enrol/user pages use pagelayout "admin" but are not site admin.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_is_admin_page(moodle_page $page): bool {
    return \theme_boost3\page_classifier::is_admin_for_gear_and_tabs($page);
}

/**
 * Whether the gear menu should be offered on this page.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_should_show_gear(moodle_page $page): bool {
    $policy = \theme_boost3\navigation_policy::resolve($page);
    return $policy->show_gear;
}

/**
 * Settings navigation keys to try for a given secondary navigation tab key.
 *
 * @param string $secondarykey
 * @return string[]
 */
function theme_boost3_secondary_settingsnav_keys(string $secondarykey): array {
    $aliases = [
        'participants' => ['users'],
        'grades' => ['gradeadmin', 'grades', 'gradebooksetup'],
        'coursetools' => ['coursetools', 'courseadmin'],
    ];

    return $aliases[$secondarykey] ?? [$secondarykey];
}

/**
 * Resolve the settings navigation subtree that holds tertiary/section links.
 *
 * @param moodle_page $page
 * @param navigation_node|null $activenode
 * @return navigation_node|null
 */
function theme_boost3_resolve_settingsnav_menunode(moodle_page $page, $activenode = null) {
    if (!$page->settingsnav) {
        return null;
    }

    $excludedkeys = theme_boost3_gear_overflow_excluded_keys();

    if ($activenode === null && $page->secondarynav) {
        $activenode = $page->secondarynav->find_active_node();
        if (!$activenode) {
            foreach ($page->secondarynav->children as $child) {
                if (is_object($child) && !empty($child->isactive)) {
                    $activenode = $child;
                    break;
                }
            }
        }
    }

    if ($activenode && !in_array($activenode->key, $excludedkeys, true)) {
        foreach (theme_boost3_secondary_settingsnav_keys($activenode->key) as $key) {
            $menunode = $page->settingsnav->find($key, null);
            if (is_object($menunode) && method_exists($menunode, 'has_children') && $menunode->has_children()) {
                return $menunode;
            }
        }
    }

    if (theme_boost3_page_uses_participants_actionbar($page)) {
        $usersnode = $page->settingsnav->find('users', null);
        if (is_object($usersnode) && method_exists($usersnode, 'has_children') && $usersnode->has_children()) {
            return $usersnode;
        }
    }

    $activeleaf = $page->settingsnav->find_active_node();
    if (is_object($activeleaf) && is_object($activeleaf->parent) && method_exists($activeleaf->parent, 'has_children')
        && $activeleaf->parent->has_children() && !empty($activeleaf->parent->children)) {
        $parentkey = $activeleaf->parent->key ?? '';
        if (!in_array($parentkey, ['root', 'frontpage', 'mycourses'], true)) {
            return $activeleaf->parent;
        }
    }

    return null;
}

/**
 * Detect tertiary overflow navigation without requiring gear to be enabled.
 *
 * Used by navigation_policy to decide effective gear when legacy drawer is on.
 *
 * @param moodle_page|null $page
 * @return bool
 */
function theme_boost3_page_detect_navigation_overflow(?moodle_page $page = null): bool {
    global $PAGE;

    $page = $page ?? $PAGE;

    if (!$page->has_secondary_navigation() || !$page->secondarynav) {
        return false;
    }
    if (theme_boost3_page_is_admin_page($page)) {
        return false;
    }
    if (\theme_boost3\page_classifier::is_gradebook_page($page)) {
        return false;
    }
    if ($page->secondarynav->get_overflow_menu_data() !== null) {
        return true;
    }
    if (theme_boost3_page_uses_participants_actionbar($page)) {
        return theme_boost3_resolve_settingsnav_menunode($page) !== null;
    }

    $pagetype = $page->pagetype ?? '';
    if (strpos($pagetype, 'course-view-') === 0) {
        return false;
    }

    if (!method_exists($page, 'get_navigation_overflow_state') || !$page->get_navigation_overflow_state()) {
        return false;
    }

    return theme_boost3_resolve_settingsnav_menunode($page) !== null;
}

/**
 * Whether the current page is in tertiary (overflow) navigation mode for gear UI.
 *
 * @param moodle_page|null $page
 * @return bool
 */
function theme_boost3_page_has_navigation_overflow(?moodle_page $page = null): bool {
    global $PAGE;

    $page = $page ?? $PAGE;
    $policy = \theme_boost3\navigation_policy::resolve($page);

    if (!$policy->gear_effective) {
        return false;
    }

    return theme_boost3_page_detect_navigation_overflow($page);
}

/**
 * Whether the gear menu should sit in the page header row (course home gear-only mode).
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_should_inline_gear_with_header(moodle_page $page): bool {
    $policy = \theme_boost3\navigation_policy::resolve($page);
    return $policy->gear_inline_header;
}

/**
 * Add Boost3 navigation flags to the drawers template context (root-level keys).
 *
 * @param array $templatecontext
 * @return array
 */
function theme_boost3_append_drawer_nav_flags(array $templatecontext, bool $legacynavdrawer = false): array {
    global $PAGE;

    $policy = \theme_boost3\navigation_policy::resolve($PAGE, $legacynavdrawer);

    $templatecontext['boost3_legacy_drawer'] = $policy->legacy_drawer_eligible;
    $templatecontext['boost3_legacy_nav_toggle'] = $policy->legacy_nav_toggle;
    $templatecontext['boost3_show_secondary_tabs'] = $policy->show_secondary_tabs;
    $templatecontext['boost3_use_gear_secondary_nav'] = $policy->use_gear_secondary_nav;
    $templatecontext['boost3_gear_inline_header'] = $policy->gear_inline_header;
    $templatecontext['boost3_hide_tertiary_overflow'] = $policy->hide_tertiary_overflow;
    $templatecontext['boost3_show_grade_navigation'] = $policy->show_grade_navigation;
    $templatecontext['boost3_participants_gear'] = $policy->participants_gear;
    $templatecontext['boost3_gear_effective'] = $policy->gear_effective;

    return $templatecontext;
}

/**
 * Returns the main SCSS content (Boost Union stack + this theme).
 *
 * @param \core\output\theme_config $theme Active theme config.
 * @return string
 */
function theme_boost3_get_main_scss_content($theme) {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost_union/lib.php');

    $scss = theme_boost_union_get_main_scss_content(\core\output\theme_config::load('boost_union'));
    $scss .= file_get_contents($CFG->dirroot . '/theme/boost3/scss/post.scss');

    return $scss;
}

/**
 * Prepend SCSS for this theme.
 *
 * @param \core\output\theme_config $theme Active theme config.
 * @return string
 */
function theme_boost3_get_pre_scss($theme) {
    global $CFG;

    $scss = '';
    $prepath = $CFG->dirroot . '/theme/boost3/scss/pre.scss';
    if (is_readable($prepath)) {
        $scss .= file_get_contents($prepath);
    }

    return $scss;
}

/**
 * Extra SCSS hook (Moodle also calls parent themes; keep for extensions).
 *
 * @param \core\output\theme_config $theme Active theme config.
 * @return string
 */
function theme_boost3_get_extra_scss($theme) {
    return '';
}

/**
 * Delegate CSS URL rewriting (e.g. Boost Union flavours) to Boost Union.
 *
 * @param mixed $urls Reference to URLs list.
 */
function theme_boost3_alter_css_urls(&$urls) {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost_union/lib.php');
    theme_boost_union_alter_css_urls($urls);
}

/**
 * Inject HTML into the top navbar (next to messages / user menu).
 *
 * Moodle calls theme_<name>_render_navbar_output for the active theme only.
 * Boost Union's starred-courses popover is forwarded here when Boost3 is active.
 *
 * @return string
 */
function theme_boost3_render_navbar_output() {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost_union/locallib.php');

    return theme_boost_union_get_navbar_starredcoursespopover();
}
