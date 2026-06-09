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
 * Theme Boost3 settings.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('themesettingboost3', get_string('pluginname', 'theme_boost3'));

    $name = 'theme_boost3/enablegearmenu';
    $title = get_string('enablegearmenu', 'theme_boost3');
    $description = get_string('enablegearmenu_desc', 'theme_boost3');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    $name = 'theme_boost3/enablelegacydrawer';
    $title = get_string('enablelegacydrawer', 'theme_boost3');
    $description = get_string('enablelegacydrawer_desc', 'theme_boost3');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $settings->add($setting);

    $name = 'theme_boost3/legacydrawersitekeys';
    $title = get_string('legacydrawersitekeys', 'theme_boost3');
    $description = get_string('legacydrawersitekeys_desc', 'theme_boost3');
    $default = "home\ncontentbank";
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, 5, 40);
    $settings->add($setting);

    $name = 'theme_boost3/legacydrawersectionlinks';
    $title = get_string('legacydrawersectionlinks', 'theme_boost3');
    $description = get_string('legacydrawersectionlinks_desc', 'theme_boost3');
    $choices = [
        'sectionpage' => get_string('legacydrawersectionlinks_sectionpage', 'theme_boost3'),
        'anchor' => get_string('legacydrawersectionlinks_anchor', 'theme_boost3'),
    ];
    $setting = new admin_setting_configselect($name, $title, $description, 'sectionpage', $choices);
    $settings->add($setting);

    $name = 'theme_boost3/legacydrawercoursekeys';
    $title = get_string('legacydrawercoursekeys', 'theme_boost3');
    $description = get_string('legacydrawercoursekeys_desc', 'theme_boost3') . "\n\n"
        . get_string('legacydrawercoursekeys_catalog', 'theme_boost3');
    $default = "editsettings\nparticipants\ncompetencies\ngrades";
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, 8, 50);
    $settings->add($setting);

    $name = 'theme_boost3/legacydrawercoursekeyaliases';
    $title = get_string('legacydrawercoursekeyaliases', 'theme_boost3');
    $description = get_string('legacydrawercoursekeyaliases_desc', 'theme_boost3');
    $default = "editsettings=editsettings,settings,courseedit\nparticipants=participants,users\ngrades=grades,gradeadmin,gradebooksetup\ncompetencies=competencies,competency";
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, 6, 50);
    $settings->add($setting);

    $name = 'theme_boost3/legacydrawersectionformats';
    $title = get_string('legacydrawersectionformats', 'theme_boost3');
    $description = get_string('legacydrawersectionformats_desc', 'theme_boost3');
    $default = 'topics, weeks';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, 2, 40);
    $settings->add($setting);

    $name = 'theme_boost3/legacydrawergearexcludedkeys';
    $title = get_string('legacydrawergearexcludedkeys', 'theme_boost3');
    $description = get_string('legacydrawergearexcludedkeys_desc', 'theme_boost3');
    $default = 'coursehome, questionbank, coursereports';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_RAW, 3, 40);
    $settings->add($setting);
}
