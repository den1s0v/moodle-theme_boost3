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
 * Theme Boost3 core renderer.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3\output;

use context_course;
use moodle_page;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Extends Boost Union renderer with a Moodle-3-style consolidated gear menu.
 */
class core_renderer extends \theme_boost_union\output\core_renderer {

    /**
     * Renders a dropdown that aggregates secondary navigation and common course actions.
     *
     * @return string HTML fragment (empty if nothing to show).
     */
    public function gear_menu(): string {
        global $PAGE;

        if (!$this->boost3_should_show_gear($PAGE)) {
            return '';
        }

        $items = [];

        if (!empty($PAGE->secondarynav)) {
            $children = $PAGE->secondarynav->get_children();
            $items = array_merge($items, $this->boost3_collect_secondary_nav($children));
        }

        $this->boost3_append_course_action_items($PAGE, $items);

        if (count($items) === 0) {
            return '';
        }

        return $this->render_from_template('theme_boost3/gear_menu', [
            'title' => get_string('gearmenu', 'theme_boost3'),
            'items' => $items,
        ]);
    }

    /**
     * Whether the gear menu should be offered on this page.
     *
     * @param moodle_page $page
     * @return bool
     */
    protected function boost3_should_show_gear(moodle_page $page): bool {
        if (!isloggedin() || isguestuser()) {
            return false;
        }
        // Popup / embedded layouts: keep chrome minimal.
        if ($page->pagelayout === 'popup' || $page->pagelayout === 'embedded') {
            return false;
        }
        return true;
    }

    /**
     * Flatten secondary navigation nodes that resolve to moodle_url actions.
     *
     * @param iterable $nodes
     * @param int $depth
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_collect_secondary_nav(iterable $nodes, int $depth = 0): array {
        $items = [];
        foreach ($nodes as $node) {
            $action = $node->action();
            if ($action instanceof moodle_url) {
                $items[] = [
                    'text' => $this->boost3_plain_nav_label($node->get_text(), $depth),
                    'url' => $action->out(false),
                    'active' => (bool) $node->is_active(),
                ];
            }
            if ($node->has_children()) {
                $items = array_merge($items, $this->boost3_collect_secondary_nav($node->get_children(), $depth + 1));
            }
        }
        return $items;
    }

    /**
     * @param string $raw
     * @param int $depth
     * @return string
     */
    protected function boost3_plain_nav_label($raw, int $depth): string {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $raw)));
        if ($depth > 0) {
            $text = str_repeat('· ', $depth) . $text;
        }
        return $text;
    }

    /**
     * Add editing / course settings when relevant and not already present.
     *
     * @param moodle_page $page
     * @param array $items reference
     */
    protected function boost3_append_course_action_items(moodle_page $page, array &$items): void {
        if (empty($page->course) || empty($page->course->id)) {
            return;
        }
        if ((int) $page->course->id === SITEID) {
            return;
        }

        try {
            $coursecontext = context_course::instance($page->course->id);
        } catch (\Exception $e) {
            return;
        }

        if (has_capability('moodle/course:update', $coursecontext)) {
            $editurl = new moodle_url('/course/view.php', ['id' => $page->course->id, 'sesskey' => sesskey()]);
            if ($page->user_is_editing()) {
                $editurl->param('adminedit', 'off');
                $edittext = get_string('turneditingoff');
            } else {
                $editurl->param('edit', '1');
                $edittext = get_string('turneditingon');
            }
            if (!$this->boost3_items_contain_url($items, $editurl)) {
                array_unshift($items, [
                    'text' => $edittext,
                    'url' => $editurl->out(false),
                    'active' => false,
                ]);
            }

            $settingsurl = new moodle_url('/course/edit.php', ['id' => $page->course->id]);
            if (!$this->boost3_items_contain_url($items, $settingsurl)) {
                $items[] = [
                    'text' => get_string('editsettings'),
                    'url' => $settingsurl->out(false),
                    'active' => false,
                ];
            }
        }
    }

    /**
     * @param array $items
     * @param moodle_url $url
     * @return bool
     */
    protected function boost3_items_contain_url(array $items, moodle_url $url): bool {
        $target = $url->out(false);
        foreach ($items as $item) {
            if (($item['url'] ?? '') === $target) {
                return true;
            }
        }
        return false;
    }
}
