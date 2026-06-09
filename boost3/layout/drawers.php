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
 * Theme Boost3 - Drawers page layout (extends Boost Union, adds nav flags for Mustache).
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

require_once($CFG->dirroot . '/theme/boost_union/locallib.php');
require_once($CFG->dirroot . '/theme/boost3/lib.php');

$unionlayout = $CFG->dirroot . '/theme/boost_union/layout';

$activitynavigation = get_config('theme_boost_union', 'activitynavigation');
if ($activitynavigation == THEME_BOOST_UNION_SETTING_SELECT_YES) {
    $PAGE->theme->usescourseindex = false;
}

$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);

    if (isguestuser()) {
        $sitehomerighthandblockdrawerserverconfig = get_config('theme_boost_union', 'showsitehomerighthandblockdraweronguestlogin');
    } else {
        $sitehomerighthandblockdrawerserverconfig = get_config('theme_boost_union', 'showsitehomerighthandblockdraweronfirstlogin');
    }

    $isadminsettingyes = ($sitehomerighthandblockdrawerserverconfig == THEME_BOOST_UNION_SETTING_SELECT_YES);
    $blockdraweropen = (get_user_preferences('drawer-open-block', $isadminsettingyes)) == true;
} else {
    $courseindexopen = false;
    $blockdraweropen = false;

    if (get_config('theme_boost_union', 'showsitehomerighthandblockdraweronvisit') == THEME_BOOST_UNION_SETTING_SELECT_YES) {
        $blockdraweropen = true;
    }
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    try {
        if (
            get_config('theme_boost_union', 'showsitehomerighthandblockdraweronvisit') === false &&
            get_config('theme_boost_union', 'showsitehomerighthandblockdraweronguestlogin') === false &&
            get_config('theme_boost_union', 'showsitehomerighthandblockdraweronfirstlogin') === false
        ) {
            $blockdraweropen = true;
        }
    } catch (Exception $e) {
        echo $e->getMessage();

        $blockdraweropen = true;
    }
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$legacyactive = theme_boost3_legacy_drawer_active_for_page($PAGE);
$legacynavdrawer = false;
$legacynavdrawercontent = '';
$courseindex = null;

if ($legacyactive) {
    $legacynavdrawercontent = $OUTPUT->legacy_nav_drawer();
    if ($legacynavdrawercontent !== '') {
        $legacynavdrawer = true;
    } else {
        $courseindexopen = false;
    }
} else {
    $courseindex = core_course_drawer();
    if (!$courseindex) {
        $courseindexopen = false;
    }
}

$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $overflow = $overflowdata->export_for_template($OUTPUT);
    }
}

$primary = new theme_boost_union\output\navigation\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);

if (isset($primarymenu['includesmartmenu']) && $primarymenu['includesmartmenu'] == true) {
    $extraclasses[] = 'theme-boost-union-smartmenu';
}

if (!empty($primarymenu['bottombar']) && !empty($primarymenu['bottombar']['drawer']) && !empty($primarymenu['includesmartmenu'])) {
    $extraclasses[] = 'theme-boost-union-bottombar';
}

require_once($unionlayout . '/includes/courseindex.php');

$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'legacynavdrawer' => $legacynavdrawer,
    'legacynavdrawercontent' => $legacynavdrawercontent,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
];

$templatecontext = theme_boost3_append_drawer_nav_flags($templatecontext);

// Participants action bar: navigation is stripped in PHP; only hide in-content tertiary elsewhere.
if (!empty($templatecontext['boost3_hide_tertiary_overflow'])
        && !theme_boost3_page_uses_participants_actionbar($PAGE)) {
    $extraclasses[] = 'theme-boost3-hide-content-tertiary';
}
if (!empty($templatecontext['boost3_participants_gear'])) {
    $extraclasses[] = 'theme-boost3-participants-gear';
}
if (!empty($templatecontext['boost3_legacy_drawer']) && $legacynavdrawer) {
    $extraclasses[] = 'theme-boost3-legacy-drawer';
}
$templatecontext['bodyattributes'] = $OUTPUT->body_attributes($extraclasses);

require_once($unionlayout . '/includes/courserelatedhints.php');
require_once($unionlayout . '/includes/blockregions.php');
require_once($unionlayout . '/includes/backtotopbutton.php');
require_once($unionlayout . '/includes/footerbuttons.php');
require_once($unionlayout . '/includes/scrollspy.php');
require_once($unionlayout . '/includes/footnote.php');
require_once($unionlayout . '/includes/staticpages.php');
require_once($unionlayout . '/includes/accessibilitypages.php');
require_once($unionlayout . '/includes/footer.php');
require_once($unionlayout . '/includes/javascriptdisabledhint.php');
require_once($unionlayout . '/includes/infobanners.php');
require_once($unionlayout . '/includes/navbar.php');

if ($PAGE->pagelayout == 'frontpage') {
    require_once($unionlayout . '/includes/advertisementtiles.php');
}

if ($PAGE->pagelayout == 'frontpage') {
    require_once($unionlayout . '/includes/slider.php');
}

require_once($unionlayout . '/includes/smartmenus.php');

echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);
