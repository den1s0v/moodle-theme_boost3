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
 * Central navigation visibility policy for legacy drawer and gear menu.
 *
 * Project decision: one resolver decides drawer/gear/tabs for each page kind.
 * Gear can be "effectively" enabled when legacy drawer is on but excluded
 * secondary tabs would otherwise disappear (gear setting off).
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

/**
 * Navigation visibility policy.
 */
class navigation_policy {
    /** Site administration UI (drawer excluded). */
    public const KIND_SITE_ADMIN = 'site_admin';

    /** Course settings / course-edit admin UI. */
    public const KIND_COURSE_ADMIN = 'course_admin';

    /** Main course format view (course home, no active secondary tab). */
    public const KIND_COURSE_FORMAT = 'course_format';

    /** Course page with an active secondary navigation tab. */
    public const KIND_COURSE_SECONDARY = 'course_secondary';

    /** Participants / users subtree pages. */
    public const KIND_PARTICIPANTS = 'participants';

    /** Standard logged-in page. */
    public const KIND_STANDARD = 'standard';

    /**
     * Classify the current page for navigation decisions.
     *
     * @param \moodle_page $page
     * @return string One of the KIND_* constants.
     */
    public static function classify(\moodle_page $page): string {
        if (theme_boost3_page_uses_participants_actionbar($page)) {
            return self::KIND_PARTICIPANTS;
        }

        if (theme_boost3_page_is_course_scoped_page($page)) {
            $pagetype = $page->pagetype ?? '';
            if ($pagetype === 'course-edit') {
                return self::KIND_COURSE_ADMIN;
            }
            if (theme_boost3_page_is_course_format_view($page)) {
                return self::KIND_COURSE_FORMAT;
            }
            return self::KIND_COURSE_SECONDARY;
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
     * Resolve all navigation channel flags for template context and renderer.
     *
     * @param \moodle_page $page
     * @param bool $legacynavdrawer True when legacy drawer HTML was rendered (non-empty content).
     * @return \stdClass Policy object with boolean flags.
     */
    public static function resolve(\moodle_page $page, bool $legacynavdrawer = false): \stdClass {
        $legacyeligible = theme_boost3_legacy_drawer_active_for_page($page);
        $gearenabled = theme_boost3_gear_navigation_enabled();
        $hasexcludedtabs = $legacyeligible && theme_boost3_page_has_legacy_gear_secondary_items($page);
        $hasoverflow = theme_boost3_page_detect_navigation_overflow($page);

        // When legacy drawer hides horizontal tabs, excluded tabs must still be reachable.
        $effectivegear = $gearenabled || ($legacyeligible && ($hasexcludedtabs || $hasoverflow));

        $policy = (object) [
            'page_kind' => self::classify($page),
            'legacy_drawer_eligible' => $legacyeligible,
            'legacy_nav_toggle' => $legacynavdrawer,
            'legacy_drawer_body_class' => $legacyeligible && $legacynavdrawer,
            'show_secondary_tabs' => self::should_show_secondary_tabs($page, $legacyeligible, $effectivegear, $hasoverflow),
            'gear_enabled_setting' => $gearenabled,
            'gear_effective' => $effectivegear,
            'show_gear' => self::should_show_gear($page, $effectivegear),
            'gear_inline_header' => self::should_inline_gear_with_header($page, $legacyeligible, $effectivegear, $hasoverflow),
            'hide_tertiary_overflow' => false,
            'participants_gear' => false,
            'use_gear_secondary_nav' => false,
        ];

        if ($policy->show_gear) {
            $policy->hide_tertiary_overflow = $hasoverflow;
            $policy->participants_gear = theme_boost3_page_uses_participants_actionbar($page);
            $policy->use_gear_secondary_nav = !$policy->gear_inline_header;
        }

        if ($legacyeligible) {
            $policy->use_gear_secondary_nav = $policy->show_gear && ($hasoverflow || $hasexcludedtabs);
            $policy->gear_inline_header = false;
        }

        return $policy;
    }

    /**
     * Site admin UI where the legacy drawer must not appear.
     *
     * Course-scoped pages under /admin/ (settings, tool_lp, roles) are never site admin.
     *
     * @param \moodle_page $page
     * @return bool
     */
    public static function is_site_admin_for_drawer(\moodle_page $page): bool {
        if (theme_boost3_page_is_course_scoped_page($page)) {
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
     * @param \moodle_page $page
     * @return bool
     */
    public static function is_admin_for_gear_and_tabs(\moodle_page $page): bool {
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
        if (self::url_path_is_admin($page)) {
            return true;
        }
        if ($page->pagelayout === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * @param \moodle_page $page
     * @return bool
     */
    protected static function url_path_is_admin(\moodle_page $page): bool {
        if (!$page->url instanceof \moodle_url) {
            return false;
        }
        $path = $page->url->get_path(false);
        return ($path === '/admin' || strpos($path, '/admin/') === 0);
    }

    /**
     * @param \moodle_page $page
     * @param bool $legacyeligible
     * @param bool $effectivegear
     * @param bool $hasoverflow
     * @return bool
     */
    protected static function should_show_secondary_tabs(
        \moodle_page $page,
        bool $legacyeligible,
        bool $effectivegear,
        bool $hasoverflow
    ): bool {
        if (!$page->has_secondary_navigation()) {
            return false;
        }

        if (self::is_admin_for_gear_and_tabs($page)) {
            return true;
        }

        if ($legacyeligible) {
            return false;
        }

        if (!$effectivegear) {
            return true;
        }

        return $hasoverflow;
    }

    /**
     * @param \moodle_page $page
     * @param bool $effectivegear
     * @return bool
     */
    protected static function should_show_gear(\moodle_page $page, bool $effectivegear): bool {
        if (!$effectivegear) {
            return false;
        }
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if (in_array($page->pagelayout, ['popup', 'embedded'], true)) {
            return false;
        }
        if (self::is_admin_for_gear_and_tabs($page)) {
            return false;
        }

        if (theme_boost3_legacy_drawer_active_for_page($page)) {
            return theme_boost3_page_has_legacy_gear_secondary_items($page)
                || theme_boost3_page_detect_navigation_overflow($page);
        }

        return true;
    }

    /**
     * @param \moodle_page $page
     * @param bool $legacyeligible
     * @param bool $effectivegear
     * @param bool $hasoverflow
     * @return bool
     */
    protected static function should_inline_gear_with_header(
        \moodle_page $page,
        bool $legacyeligible,
        bool $effectivegear,
        bool $hasoverflow
    ): bool {
        if ($legacyeligible) {
            return false;
        }
        if (!$effectivegear) {
            return false;
        }
        if (!$page->has_secondary_navigation()) {
            return false;
        }
        if (self::is_admin_for_gear_and_tabs($page)) {
            return false;
        }
        if ($hasoverflow) {
            return false;
        }

        return true;
    }
}
