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

/**
 * Pages that render core participants_action_bar tertiary navigation in content.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_uses_participants_actionbar(moodle_page $page): bool {
    $pagetype = $page->pagetype ?? '';
    if (in_array($pagetype, [
        'course-view-participants',
        'enrol-otherusers',
        'enrol-instances',
        'group-index',
        'group-groupings',
        'group-overview',
    ], true)) {
        return true;
    }

    if ($page->url instanceof moodle_url) {
        $path = $page->url->get_path(false);
        $paths = [
            '/user/index.php',
            '/enrol/otherusers.php',
            '/enrol/instances.php',
            '/group/index.php',
            '/group/groupings.php',
            '/group/overview.php',
        ];
        foreach ($paths as $matchpath) {
            if ($path === $matchpath) {
                return true;
            }
        }
        if (preg_match('#^/admin/roles/(permissions|check|override|assign)\\.php#', $path)
            && $page->context && $page->context->contextlevel == CONTEXT_COURSE) {
            return true;
        }
    }

    return false;
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
    if (theme_boost3_page_uses_participants_actionbar($page)) {
        return false;
    }

    $pagetype = $page->pagetype ?? '';
    if ($pagetype === 'course-edit') {
        return true;
    }
    if ($pagetype !== '' && strpos($pagetype, 'admin-') === 0) {
        return true;
    }
    if ($page->url instanceof moodle_url) {
        $path = $page->url->get_path(false);
        if ($path === '/admin' || strpos($path, '/admin/') === 0) {
            return true;
        }
    }
    if ($page->pagelayout === 'admin') {
        return true;
    }
    return false;
}

/**
 * Whether the gear menu should be offered on this page.
 *
 * @param moodle_page $page
 * @return bool
 */
function theme_boost3_page_should_show_gear(moodle_page $page): bool {
    if (!isloggedin() || isguestuser()) {
        return false;
    }
    if ($page->pagelayout === 'popup' || $page->pagelayout === 'embedded') {
        return false;
    }
    if (theme_boost3_page_is_admin_page($page)) {
        return false;
    }
    return true;
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

    $excludedkeys = ['coursehome', 'questionbank', 'coursereports'];

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
 * Whether the current page is in tertiary (overflow) navigation mode.
 *
 * Split mode requires real section submenu data (layout url_select or settings/users subtree),
 * not navigation overflow state alone (which is also true on the course home page).
 *
 * @param moodle_page|null $page
 * @return bool
 */
function theme_boost3_page_has_navigation_overflow(?moodle_page $page = null): bool {
    global $PAGE;

    $page = $page ?? $PAGE;

    if (!$page->has_secondary_navigation() || !$page->secondarynav) {
        return false;
    }
    if (theme_boost3_page_is_admin_page($page)) {
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
 * Add Boost3 navigation flags to the drawers template context (root-level keys).
 *
 * @param array $templatecontext
 * @return array
 */
function theme_boost3_append_drawer_nav_flags(array $templatecontext): array {
    global $OUTPUT, $PAGE;

    $hasoverflow = theme_boost3_page_has_navigation_overflow($PAGE);
    $isadmin = theme_boost3_page_is_admin_page($PAGE);
    $showtabs = $PAGE->has_secondary_navigation() && ($isadmin || $hasoverflow);
    $usegear = theme_boost3_page_should_show_gear($PAGE);
    $hidetertiary = $usegear && $hasoverflow;

    $templatecontext['boost3_show_secondary_tabs'] = $showtabs;
    $templatecontext['boost3_use_gear_secondary_nav'] = $usegear;
    $templatecontext['boost3_hide_tertiary_overflow'] = $hidetertiary;

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
