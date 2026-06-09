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
 * Language strings for theme_boost3.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Boost3 (Boost Union child)';
$string['gearmenu'] = 'Course menu';
$string['gearmenudesc'] = 'Course and page actions (similar to the old gear menu)';
$string['enablegearmenu'] = 'Gear-style course menu (Moodle 3.9 style)';
$string['enablegearmenu_desc'] = 'When enabled, course tabs and section links are consolidated in a gear menu. When disabled, standard Boost horizontal tabs and the tertiary url_select are used. Purge caches after changing this setting.';
$string['enablelegacydrawer'] = 'Legacy left navigation drawer (Moodle 3.9 style)';
$string['enablelegacydrawer_desc'] = 'When enabled, the left drawer shows flat course, site and my courses links instead of the activity tree. Secondary tabs move into the drawer. Purge caches after changing this setting.';
$string['legacydrawernav'] = 'Course and site navigation';
$string['legacydrawersite'] = 'Site';
$string['legacydrawertoggleopen'] = 'Open navigation panel';
$string['legacydrawertoggleclose'] = 'Close navigation panel';
$string['legacydrawersitekeys'] = 'Site links in legacy drawer';
$string['legacydrawersitekeys_desc'] = 'One global_navigation node key per line (or comma-separated). Only nodes that exist on the current page are shown. Unknown keys are skipped. Examples: home, contentbank. Dashboard, calendar and private files live in the top navbar in Moodle 4.5 and are not added here.';
$string['legacydrawersectionlinks'] = 'Course section links in legacy drawer';
$string['legacydrawersectionlinks_desc'] = 'How section names in the left drawer link to course content. Section page uses Moodle 4.5 dedicated section URLs. Anchors scroll on the course home page like Moodle 3.9 (topics/weeks formats).';
$string['legacydrawersectionlinks_sectionpage'] = 'Section page (/course/section.php)';
$string['legacydrawersectionlinks_anchor'] = 'Course home anchors (#section-N)';
$string['legacydrawercoursekeys'] = 'Course tabs in legacy drawer';
$string['legacydrawercoursekeys_desc'] = 'Secondary navigation tab keys (one per line) shown in the left drawer between the course title and section list — like Moodle 3.9. All other course tabs go to the gear menu when it is enabled. Use the node keys from the course secondary navigation (see catalog below). Unknown keys are skipped.';
$string['legacydrawercoursekeys_catalog'] = 'Common secondary navigation keys (core and frequent plugins):
editsettings — Course settings
participants — Participants
competencies — Competencies (tool_lp)
grades — Grades
questionbank — Question bank
coursereports — Reports
coursecompletion — Course completion
badges — Badges
contentbank — Content bank (course tab)
filtermanagement — Filters
coursetools — LTI / external tools
backup — Backup and restore
coursehome — Course home (usually omitted; course title is shown separately)
Plugins may add more keys — inspect $PAGE->secondarynav on your site if needed.';
