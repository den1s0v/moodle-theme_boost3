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
 * Builds Moodle 3.9-style two-row gradebook tabs from core grade_get_plugin_info().
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

use grade_plugin_info;
use moodle_page;
use moodle_url;

/**
 * Gradebook navigation rows for theme_boost3/grade_navigation.mustache.
 */
class grade_navigation_builder {

    /**
     * @param moodle_page $page
     * @return array|null Template context with row1/row2 tab arrays, or null when empty.
     */
    public static function build(moodle_page $page): ?array {
        global $CFG;

        if (!page_classifier::is_gradebook_page($page)) {
            return null;
        }

        require_once($CFG->dirroot . '/grade/lib.php');

        $courseid = (int) $page->course->id;
        [$activetype, $activeplugin] = self::resolve_active($page, $courseid);

        if ($activetype === 'preferences') {
            $activetype = 'settings';
        }

        $plugininfo = grade_get_plugin_info($courseid, $activetype, $activeplugin ?? '');

        $row1 = [];
        $row2 = [];

        foreach ($plugininfo as $plugintype => $plugins) {
            if ($plugintype === 'strings') {
                continue;
            }

            if (!empty($plugins->id)) {
                $label = $plugins->string;
                if (!empty($plugininfo[$activetype]->parent)) {
                    $label = $plugininfo[$activetype]->parent->string;
                }
                $url = self::plugin_link_out($plugins->link);
                $row1[] = self::make_tab($label, $url, $activetype === $plugintype);
                continue;
            }

            if (!is_array($plugins) && !($plugins instanceof \Traversable)) {
                continue;
            }

            $firstplugin = null;
            foreach ($plugins as $plugin) {
                if ($plugin instanceof grade_plugin_info) {
                    $firstplugin = $plugin;
                    break;
                }
            }
            if ($firstplugin === null) {
                continue;
            }

            $topurl = self::plugin_link_out($firstplugin->link);
            if ($plugintype === 'report') {
                $topurl = (new moodle_url('/grade/report/index.php', ['id' => $courseid]))->out(false);
            }

            $row1[] = self::make_tab(
                $plugininfo['strings'][$plugintype] ?? $firstplugin->string,
                $topurl,
                $activetype === $plugintype
            );

            if ($activetype === $plugintype) {
                foreach ($plugins as $plugin) {
                    if (!$plugin instanceof grade_plugin_info) {
                        continue;
                    }
                    $row2[] = self::make_tab(
                        $plugin->string,
                        self::plugin_link_out($plugin->link),
                        ($activeplugin !== null && $activeplugin !== '' && $plugin->id === $activeplugin)
                    );
                }
            }
        }

        if (count($row1) <= 1) {
            $row1 = [];
        }
        if (count($row2) <= 1) {
            $row2 = [];
        }

        if ($row1 === [] && $row2 === []) {
            return null;
        }

        return [
            'row1' => $row1,
            'row2' => $row2,
            'hasrow2' => $row2 !== [],
        ];
    }

    /**
     * @param string $text
     * @param string|null $url
     * @param bool $active
     * @return array{text: string, url: string|null, active: bool, hasurl: bool}
     */
    protected static function make_tab(string $text, ?string $url, bool $active): array {
        return [
            'text' => $text,
            'url' => $active ? null : $url,
            'active' => $active,
            'hasurl' => !$active && $url !== null && $url !== '',
        ];
    }

    /**
     * @param mixed $link moodle_url|string|null
     * @return string|null
     */
    protected static function plugin_link_out($link): ?string {
        if ($link instanceof moodle_url) {
            return $link->out(false);
        }
        if (is_string($link) && $link !== '') {
            return $link;
        }
        return null;
    }

    /**
     * Resolve active gradebook section from the current page URL.
     *
     * @param moodle_page $page
     * @param int $courseid
     * @return array{0: string, 1: string|null}
     */
    protected static function resolve_active(moodle_page $page, int $courseid): array {
        $path = $page->url instanceof moodle_url ? $page->url->get_path(false) : '';

        if (preg_match('#^/grade/report/grader/preferences\.php#', $path)) {
            return ['settings', 'grader'];
        }
        if (preg_match('#^/grade/report/([^/]+)/#', $path, $matches)) {
            return ['report', $matches[1]];
        }
        if (preg_match('#^/grade/report/index\.php#', $path)) {
            return ['report', 'grader'];
        }
        if (preg_match('#^/grade/edit/tree/#', $path)) {
            return ['settings', 'setup'];
        }
        if (preg_match('#^/grade/edit/settings/#', $path)) {
            return ['settings', 'coursesettings'];
        }
        if (preg_match('#^/grade/edit/scale/#', $path)) {
            return ['scale', 'view'];
        }
        if (preg_match('#^/grade/edit/letter/#', $path)) {
            return ['letter', 'view'];
        }
        if (preg_match('#^/grade/edit/outcome/#', $path)) {
            return ['outcome', 'course'];
        }
        if (preg_match('#^/grade/import/([^/]+)/#', $path, $matches)) {
            return ['import', $matches[1]];
        }
        if (preg_match('#^/grade/import/index\.php#', $path)) {
            return ['import', 'csv'];
        }
        if (preg_match('#^/grade/export/([^/]+)/#', $path, $matches)) {
            return ['export', $matches[1]];
        }
        if (preg_match('#^/grade/export/index\.php#', $path)) {
            return ['export', 'ods'];
        }

        return ['report', 'grader'];
    }
}
