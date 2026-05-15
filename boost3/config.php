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
 * Theme Boost3 - Theme config (grandchild of Boost Union).
 *
 * Install this directory as: moodle/theme/boost3/
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// phpcs:disable moodle.Files.RequireLogin.Missing

// Inherit full Boost Union theme configuration (layouts, drawers, etc.).
require($CFG->dirroot . '/theme/boost_union/config.php');

require_once($CFG->dirroot . '/theme/boost3/locallib.php');

$THEME->name = 'boost3';
$THEME->parents = ['boost_union', 'boost'];
$THEME->scss = function ($theme) {
    return theme_boost3_get_main_scss_content($theme);
};
$THEME->extrascsscallback = 'theme_boost3_get_extra_scss';
$THEME->prescsscallback = 'theme_boost3_get_pre_scss';
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Mirror Boost Union settings that core reads from the active theme object.
$unaddableblocks = get_config('theme_boost_union', 'unaddableblocks');
if (!empty($unaddableblocks)) {
    $THEME->settings->unaddableblocks = $unaddableblocks;
}
unset($unaddableblocks);

$scss = get_config('theme_boost_union', 'scss');
if (!empty($scss)) {
    $THEME->settings->scss = $scss;
}
unset($scss);

$scsspre = get_config('theme_boost_union', 'scsspre');
if (!empty($scsspre)) {
    $THEME->settings->scsspre = $scsspre;
}
unset($scsspre);
