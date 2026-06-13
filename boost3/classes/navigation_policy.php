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
 * Central navigation visibility policy — resolves matrix + runtime modifiers.
 *
 * Page detection: page_classifier. Defaults: navigation_matrix.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

/**
 * Navigation visibility policy (layer 3).
 */
class navigation_policy {

    /** @deprecated Use page_classifier::KIND_* — kept for backward compatibility. */
    public const KIND_SITE_ADMIN = page_classifier::KIND_SITE_ADMIN;
    public const KIND_COURSE_ADMIN = page_classifier::KIND_COURSE_ADMIN;
    public const KIND_COURSE_FORMAT = page_classifier::KIND_COURSE_FORMAT;
    public const KIND_COURSE_SECONDARY = page_classifier::KIND_COURSE_SECONDARY;
    public const KIND_PARTICIPANTS = page_classifier::KIND_PARTICIPANTS;
    public const KIND_GRADEBOOK = page_classifier::KIND_GRADEBOOK;
    public const KIND_MODULE = page_classifier::KIND_MODULE;
    public const KIND_STANDARD = page_classifier::KIND_STANDARD;

    /**
     * Classify the current page for navigation decisions.
     *
     * @param \moodle_page $page
     * @return string One of the KIND_* constants.
     */
    public static function classify(\moodle_page $page): string {
        return page_classifier::classify($page);
    }

    /**
     * Breadcrumbs mode for this page when legacy drawer is globally enabled.
     *
     * @param \moodle_page $page
     * @return string One of navigation_channel_profile::BREADCRUMBS_*
     */
    public static function breadcrumbs_mode_for_page(\moodle_page $page): string {
        if (!theme_boost3_legacy_drawer_globally_available($page)) {
            return navigation_channel_profile::BREADCRUMBS_NONE;
        }
        $kind = page_classifier::classify($page);
        $profile = navigation_matrix::for_kind($kind);
        return $profile->breadcrumbsmode;
    }

    /**
     * Resolve all navigation channel flags for template context and renderer.
     *
     * @param \moodle_page $page
     * @param bool $legacynavdrawer True when legacy drawer HTML was rendered (non-empty content).
     * @return \stdClass Policy object with boolean flags.
     */
    public static function resolve(\moodle_page $page, bool $legacynavdrawer = false): \stdClass {
        $kind = page_classifier::classify($page);
        $profile = navigation_matrix::for_kind($kind);

        $legacyglobally = theme_boost3_legacy_drawer_globally_available($page);
        $legacyeligible = $legacyglobally && $profile->legacydrawerwhenenabled;

        $gearenabled = theme_boost3_gear_navigation_enabled();
        $hasexcludedtabs = $legacyeligible && theme_boost3_page_has_legacy_gear_secondary_items($page);
        $hasoverflow = theme_boost3_page_detect_navigation_overflow($page);

        // When legacy drawer hides horizontal tabs, excluded tabs must still be reachable.
        $effectivegear = $profile->gearallowed && ($gearenabled || ($legacyeligible && ($hasexcludedtabs || $hasoverflow)));

        $showsecondarytabs = self::resolve_show_secondary_tabs(
            $page,
            $profile,
            $legacyeligible,
            $effectivegear,
            $hasoverflow
        );

        $showgear = self::resolve_show_gear($page, $profile, $legacyeligible, $effectivegear);

        $gearinlineheader = self::resolve_gear_inline_header(
            $page,
            $profile,
            $legacyeligible,
            $effectivegear,
            $hasoverflow,
            $showgear
        );

        $breadcrumbsmode = $legacyglobally ? $profile->breadcrumbsmode : navigation_channel_profile::BREADCRUMBS_NONE;

        $policy = (object) [
            'page_kind' => $kind,
            'channel_profile' => $profile,
            'breadcrumbs_mode' => $breadcrumbsmode,
            'legacy_drawer_eligible' => $legacyeligible,
            'legacy_nav_toggle' => $legacynavdrawer,
            'legacy_drawer_body_class' => $legacyeligible && $legacynavdrawer,
            'show_secondary_tabs' => $showsecondarytabs,
            'gear_enabled_setting' => $gearenabled,
            'gear_effective' => $effectivegear,
            'show_gear' => $showgear,
            'gear_inline_header' => $gearinlineheader,
            'hide_tertiary_overflow' => page_classifier::is_gradebook_page($page),
            'show_grade_navigation' => page_classifier::is_gradebook_page($page),
            'participants_gear' => false,
            'use_gear_secondary_nav' => false,
        ];

        if ($policy->show_gear) {
            $policy->hide_tertiary_overflow = $hasoverflow;
            $policy->participants_gear = page_classifier::uses_participants_actionbar($page);
            $policy->use_gear_secondary_nav = !$policy->gear_inline_header;
        }

        if ($policy->show_grade_navigation) {
            $policy->hide_tertiary_overflow = true;
        }

        return $policy;
    }

    /**
     * Site admin UI where the legacy drawer must not appear.
     *
     * @param \moodle_page $page
     * @return bool
     * @deprecated Use page_classifier::is_site_admin_for_drawer()
     */
    public static function is_site_admin_for_drawer(\moodle_page $page): bool {
        return page_classifier::is_site_admin_for_drawer($page);
    }

    /**
     * Admin UI where gear menu and legacy drawer behaviour differ from course pages.
     *
     * @param \moodle_page $page
     * @return bool
     * @deprecated Use page_classifier::is_admin_for_gear_and_tabs()
     */
    public static function is_admin_for_gear_and_tabs(\moodle_page $page): bool {
        return page_classifier::is_admin_for_gear_and_tabs($page);
    }

    /**
     * @param \moodle_page $page
     * @param navigation_channel_profile $profile
     * @param bool $legacyeligible
     * @param bool $effectivegear
     * @param bool $hasoverflow
     * @return bool
     */
    protected static function resolve_show_secondary_tabs(
        \moodle_page $page,
        navigation_channel_profile $profile,
        bool $legacyeligible,
        bool $effectivegear,
        bool $hasoverflow
    ): bool {
        if (!$page->has_secondary_navigation()) {
            return false;
        }

        if (!$profile->secondarytabswhenavailable) {
            return false;
        }

        if (page_classifier::is_admin_for_gear_and_tabs($page)) {
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
     * @param navigation_channel_profile $profile
     * @param bool $legacyeligible
     * @param bool $effectivegear
     * @return bool
     */
    protected static function resolve_show_gear(
        \moodle_page $page,
        navigation_channel_profile $profile,
        bool $legacyeligible,
        bool $effectivegear
    ): bool {
        if (!$profile->gearallowed || !$effectivegear) {
            return false;
        }
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        if (in_array($page->pagelayout, ['popup', 'embedded'], true)) {
            return false;
        }
        if (page_classifier::is_admin_for_gear_and_tabs($page)) {
            return false;
        }

        if ($legacyeligible) {
            return theme_boost3_page_has_legacy_gear_secondary_items($page)
                || theme_boost3_page_detect_navigation_overflow($page);
        }

        return true;
    }

    /**
     * @param \moodle_page $page
     * @param navigation_channel_profile $profile
     * @param bool $legacyeligible
     * @param bool $effectivegear
     * @param bool $hasoverflow
     * @param bool $showgear
     * @return bool
     */
    protected static function resolve_gear_inline_header(
        \moodle_page $page,
        navigation_channel_profile $profile,
        bool $legacyeligible,
        bool $effectivegear,
        bool $hasoverflow,
        bool $showgear
    ): bool {
        if (!$showgear || !$effectivegear) {
            return false;
        }
        if (!$page->has_secondary_navigation()) {
            return false;
        }
        if (page_classifier::is_admin_for_gear_and_tabs($page)) {
            return false;
        }

        if ($profile->gearplacementwhenshown === navigation_channel_profile::GEAR_PLACEMENT_HEADER) {
            if ($legacyeligible) {
                return true;
            }
            // Split mode: inline only when there is no overflow strip.
            return !$hasoverflow;
        }

        if ($profile->gearplacementwhenshown === navigation_channel_profile::GEAR_PLACEMENT_SECONDARY_STRIP) {
            return false;
        }

        return false;
    }
}
