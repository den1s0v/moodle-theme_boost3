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
 * Declarative channel defaults for a page kind (navigation matrix row).
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable profile of which navigation channels are allowed for a page kind.
 */
class navigation_channel_profile {

    /** No Boost3 breadcrumb override — core / Boost Union only. */
    public const BREADCRUMBS_NONE = 'none';

    /** M3.9 chain via breadcrumb_builder + boostnavbar. */
    public const BREADCRUMBS_LEGACY_M39 = 'legacy_m39';

    /** Core / Union category breadcrumbs (e.g. course/edit.php). */
    public const BREADCRUMBS_CORE_UNION = 'core_union';

    /** Gear is not offered for this page kind. */
    public const GEAR_PLACEMENT_NONE = 'none';

    /** Gear inline in #page-header when shown. */
    public const GEAR_PLACEMENT_HEADER = 'header';

    /** Gear in secondary-navigation strip when shown. */
    public const GEAR_PLACEMENT_SECONDARY_STRIP = 'secondary_strip';

    /** @var string One of BREADCRUMBS_* */
    public $breadcrumbsmode;

    /** @var bool Legacy flat drawer when enablelegacydrawer is on. */
    public $legacydrawerwhenenabled;

    /** @var bool Horizontal secondary tabs when secondary nav exists. */
    public $secondarytabswhenavailable;

    /** @var bool Gear may appear (subject to settings and overflow). */
    public $gearallowed;

    /** @var string One of GEAR_PLACEMENT_* — preferred position when gear is shown. */
    public $gearplacementwhenshown;

    /**
     * @param string $breadcrumbsmode
     * @param bool $legacydrawerwhenenabled
     * @param bool $secondarytabswhenavailable
     * @param bool $gearallowed
     * @param string $gearplacementwhenshown
     */
    public function __construct(
        string $breadcrumbsmode,
        bool $legacydrawerwhenenabled,
        bool $secondarytabswhenavailable,
        bool $gearallowed,
        string $gearplacementwhenshown
    ) {
        $this->breadcrumbsmode = $breadcrumbsmode;
        $this->legacydrawerwhenenabled = $legacydrawerwhenenabled;
        $this->secondarytabswhenavailable = $secondarytabswhenavailable;
        $this->gearallowed = $gearallowed;
        $this->gearplacementwhenshown = $gearplacementwhenshown;
    }
}
