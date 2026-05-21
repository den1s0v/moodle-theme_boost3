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
