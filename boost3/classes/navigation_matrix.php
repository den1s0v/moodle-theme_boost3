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
 * Declarative navigation channel matrix — single source of truth in code.
 *
 * Human-readable mirror: docs/NAVIGATION-DECISIONS.md
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

/**
 * Default channel profile per page kind (when legacy drawer setting is on).
 */
class navigation_matrix {

    /**
     * @param string $kind One of page_classifier::KIND_*
     * @return navigation_channel_profile
     */
    public static function for_kind(string $kind): navigation_channel_profile {
        $profiles = self::profiles();
        if (isset($profiles[$kind])) {
            return $profiles[$kind];
        }
        return $profiles[page_classifier::KIND_STANDARD];
    }

    /**
     * @return navigation_channel_profile[]
     */
    protected static function profiles(): array {
        $header = navigation_channel_profile::GEAR_PLACEMENT_HEADER;
        $strip = navigation_channel_profile::GEAR_PLACEMENT_SECONDARY_STRIP;
        $none = navigation_channel_profile::GEAR_PLACEMENT_NONE;
        $m39 = navigation_channel_profile::BREADCRUMBS_LEGACY_M39;
        $core = navigation_channel_profile::BREADCRUMBS_CORE_UNION;
        $bcnone = navigation_channel_profile::BREADCRUMBS_NONE;

        return [
            page_classifier::KIND_COURSE_FORMAT => new navigation_channel_profile(
                $m39, true, false, true, $header
            ),
            page_classifier::KIND_COURSE_SECONDARY => new navigation_channel_profile(
                $m39, true, false, true, $header
            ),
            page_classifier::KIND_PARTICIPANTS => new navigation_channel_profile(
                $m39, true, false, true, $header
            ),
            page_classifier::KIND_GRADEBOOK => new navigation_channel_profile(
                $m39, true, false, false, $none
            ),
            page_classifier::KIND_COURSE_ADMIN => new navigation_channel_profile(
                $core, true, true, false, $none
            ),
            page_classifier::KIND_SITE_ADMIN => new navigation_channel_profile(
                $bcnone, false, true, false, $none
            ),
            page_classifier::KIND_MODULE => new navigation_channel_profile(
                $m39, true, false, true, $header
            ),
            page_classifier::KIND_STANDARD => new navigation_channel_profile(
                $bcnone, true, false, true, $header
            ),
        ];
    }
}
