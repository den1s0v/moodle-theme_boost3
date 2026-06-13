<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Page kind detection for navigation policy (layer 1).
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

use moodle_page;
use moodle_url;

/**
 * Classifies moodle_page into a navigation kind.
 */
class page_classifier {

    /** Site administration UI (drawer excluded). */
    public const KIND_SITE_ADMIN = 'site_admin';

    /** Course settings form (/course/edit.php): legacy drawer + secondary tabs, no gear. */
    public const KIND_COURSE_ADMIN = 'course_admin';

    /** Main course format view (course home, no active secondary tab). */
    public const KIND_COURSE_FORMAT = 'course_format';

    /** Course page with an active secondary navigation tab. */
    public const KIND_COURSE_SECONDARY = 'course_secondary';

    /** Participants / users subtree pages. */
    public const KIND_PARTICIPANTS = 'participants';

    /** Gradebook pages (/grade/* in course context). */
    public const KIND_GRADEBOOK = 'gradebook';

    /** Activity module in a real course (CONTEXT_MODULE). */
    public const KIND_MODULE = 'module';

    /** Standard logged-in page. */
    public const KIND_STANDARD = 'standard';

    /**
     * @param moodle_page $page
     * @return string One of the KIND_* constants.
     */
    public static function classify(moodle_page $page): string {
        if (self::uses_participants_actionbar($page)) {
            return self::KIND_PARTICIPANTS;
        }

        if (self::is_gradebook_page($page)) {
            return self::KIND_GRADEBOOK;
        }

        if (self::is_course_scoped_page($page)) {
            if (self::is_course_edit_page($page)) {
                return self::KIND_COURSE_ADMIN;
            }
            if (self::is_course_format_view($page)) {
                return self::KIND_COURSE_FORMAT;
            }
            return self::KIND_COURSE_SECONDARY;
        }

        if (self::is_activity_module_page($page)) {
            return self::KIND_MODULE;
        }

        if (self::is_site_admin_for_drawer($page)) {
            return self::KIND_SITE_ADMIN;
        }

        if (self::is_admin_for_gear_and_tabs($page)) {
            return self::KIND_COURSE_ADMIN;
        }

        return self::KIND_STANDARD;
    }

    /**
     * Gradebook UI under /grade/ in a real course (not site admin).
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function is_gradebook_page(moodle_page $page): bool {
        if (!self::is_course_scoped_page($page)) {
            return false;
        }
        if (!$page->url instanceof moodle_url) {
            return false;
        }
        $path = $page->url->get_path(false);
        return (strpos($path, '/grade/') === 0);
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    public static function is_course_scoped_page(moodle_page $page): bool {
        if (!$page->context || $page->context->contextlevel != CONTEXT_COURSE) {
            return false;
        }
        $courseid = (int) ($page->course->id ?? $page->context->instanceid ?? 0);
        return $courseid > 0 && $courseid != SITEID;
    }

    /**
     * Course settings form (/course/edit.php).
     *
     * Moodle 4.x uses pagelayout admin; pagetype is not reliably course-edit.
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function is_course_edit_page(moodle_page $page): bool {
        $pagetype = $page->pagetype ?? '';
        if ($pagetype === 'course-edit') {
            return true;
        }
        return self::url_path_is($page, '/course/edit.php');
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    public static function is_course_section_page(moodle_page $page): bool {
        return self::url_path_is($page, '/course/section.php');
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    public static function is_course_format_view(moodle_page $page): bool {
        if (self::is_course_section_page($page)) {
            return false;
        }
        $pagetype = $page->pagetype ?? '';
        if ($pagetype !== 'course-view' && strpos($pagetype, 'course-view-') !== 0) {
            return false;
        }
        if ($page->has_secondary_navigation() && $page->secondarynav) {
            foreach ($page->secondarynav->children as $child) {
                if (!is_object($child) || empty($child->isactive)) {
                    continue;
                }
                $key = $child->key ?? '';
                if ($key !== '' && $key !== 'coursehome') {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    public static function is_activity_module_page(moodle_page $page): bool {
        if (!$page->context || $page->context->contextlevel != CONTEXT_MODULE) {
            return false;
        }
        $courseid = (int) ($page->course->id ?? 0);
        return $courseid > SITEID;
    }

    /**
     * Site admin UI where the legacy drawer must not appear.
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function is_site_admin_for_drawer(moodle_page $page): bool {
        if (self::is_course_scoped_page($page)) {
            return false;
        }

        $pagetype = $page->pagetype ?? '';
        if ($pagetype !== '' && strpos($pagetype, 'admin-') === 0) {
            return true;
        }

        return self::url_path_is_admin($page);
    }

    /**
     * Admin UI where gear menu and legacy drawer behaviour differ from course pages.
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function is_admin_for_gear_and_tabs(moodle_page $page): bool {
        if (self::uses_participants_actionbar($page)) {
            return false;
        }

        $pagetype = $page->pagetype ?? '';
        if (self::is_course_edit_page($page)) {
            return true;
        }
        if ($pagetype !== '' && strpos($pagetype, 'admin-') === 0) {
            return true;
        }
        if (self::url_path_is_admin($page)) {
            return true;
        }
        if ($page->pagelayout === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Pages that render core participants_action_bar tertiary navigation in content.
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function uses_participants_actionbar(moodle_page $page): bool {
        if ($page->context && $page->context->contextlevel == CONTEXT_COURSE && $page->settingsnav) {
            $activenode = $page->settingsnav->find_active_node();
            if (is_object($activenode) && self::settingsnav_node_is_under_users($activenode)) {
                return true;
            }
            if (self::has_active_participants_secondary_tab($page)) {
                $usersnode = $page->settingsnav->find('users', null);
                if (is_object($usersnode) && method_exists($usersnode, 'has_children') && $usersnode->has_children()) {
                    return true;
                }
            }
        }

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
                '/enrol/renameroles.php',
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
     * @param moodle_page $page
     * @return bool
     */
    public static function has_active_participants_secondary_tab(moodle_page $page): bool {
        if (!$page->secondarynav) {
            return false;
        }

        $activenode = $page->secondarynav->find_active_node();
        if (is_object($activenode) && ($activenode->key ?? '') === 'participants') {
            return true;
        }

        foreach ($page->secondarynav->children as $child) {
            if (is_object($child) && !empty($child->isactive) && ($child->key ?? '') === 'participants') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object|null $node
     * @return bool
     */
    protected static function settingsnav_node_is_under_users($node): bool {
        while (is_object($node)) {
            if (($node->key ?? '') === 'users') {
                return true;
            }
            $node = $node->parent ?? null;
        }
        return false;
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    protected static function url_path_is_admin(moodle_page $page): bool {
        if (!$page->url instanceof moodle_url) {
            return false;
        }
        $path = $page->url->get_path(false);
        return ($path === '/admin' || strpos($path, '/admin/') === 0);
    }

    /**
     * Whether a URL points at the course settings form.
     *
     * @param string $url Absolute or relative URL string.
     * @return bool
     */
    public static function url_is_course_edit(string $url): bool {
        if ($url === '') {
            return false;
        }
        try {
            return (new moodle_url($url))->get_path(false) === '/course/edit.php';
        } catch (\moodle_exception $e) {
            return false;
        }
    }

    /**
     * @param moodle_page $page
     * @param string $path
     * @return bool
     */
    protected static function url_path_is(moodle_page $page, string $path): bool {
        $url = $page->url ?? null;
        if ($url instanceof moodle_url) {
            return $url->get_path(false) === $path;
        }
        if (is_object($url) && method_exists($url, 'get_path')) {
            return $url->get_path(false) === $path;
        }
        return false;
    }
}
