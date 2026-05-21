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

use action_link;
use core\url as core_url;
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

        $items = $this->boost3_build_gear_items($PAGE);
        if (count($items) === 0) {
            return '';
        }

        return $this->render_from_template('theme_boost3/gear_menu', [
            'title' => get_string('gearmenu', 'theme_boost3'),
            'items' => $items,
        ]);
    }

    /**
     * Build gear menu items for the current page.
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_build_gear_items(moodle_page $page): array {
        $items = [];
        if (!$page->has_secondary_navigation()) {
            return $items;
        }

        $secondarynav = $page->secondarynav;
        if (!$secondarynav || empty($secondarynav->children)) {
            return $items;
        }

        foreach ($secondarynav->children as $child) {
            $this->boost3_collect_top_level_node($child, $items);
        }

        $overflowdata = $secondarynav->get_overflow_menu_data();
        if ($overflowdata !== null) {
            $overflowexport = $overflowdata->export_for_template($this);
            if (is_object($overflowexport)) {
                $overflowexport = (array) $overflowexport;
            }
            if (is_array($overflowexport) && !empty($overflowexport['options'])) {
                foreach ($overflowexport['options'] as $option) {
                    if (is_object($option)) {
                        $option = (array) $option;
                    }
                    if (!is_array($option) || empty($option['uri']) || empty($option['name'])) {
                        continue;
                    }
                    $url = (string) $option['uri'];
                    if (!$this->boost3_items_has_url($items, $url)) {
                        $items[] = [
                            'text' => $this->boost3_plain_nav_label($option['name'], 0),
                            'url' => $url,
                            'active' => false,
                        ];
                    }
                }
            }
        }

        return $items;
    }

    /**
     * Collect a single top-level secondary navigation tab (no deep recursion).
     *
     * @param object $node
     * @param array $items
     */
    protected function boost3_collect_top_level_node($node, array &$items): void {
        if (!is_object($node)) {
            return;
        }

        if (property_exists($node, 'display') && $node->display === false) {
            return;
        }

        $url = null;
        if (method_exists($node, 'action')) {
            $url = $this->boost3_nav_url_from_action($node->action());
        }

        $label = method_exists($node, 'get_text') ? $node->get_text() :
            (property_exists($node, 'text') ? (string) $node->text : '');

        if ($url !== null && $label !== '' && !$this->boost3_items_has_url($items, $url)) {
            $items[] = [
                'text' => $this->boost3_plain_nav_label($label, 0),
                'url' => $url,
                'active' => !empty($node->isactive),
            ];
            return;
        }

        // Tab container without its own URL: expose direct children only (one level).
        if (method_exists($node, 'has_children') && $node->has_children() && !empty($node->children)) {
            foreach ($node->children as $child) {
                if (!is_object($child) || (property_exists($child, 'display') && $child->display === false)) {
                    continue;
                }
                $childurl = method_exists($child, 'action') ? $this->boost3_nav_url_from_action($child->action()) : null;
                $childlabel = method_exists($child, 'get_text') ? $child->get_text() :
                    (property_exists($child, 'text') ? (string) $child->text : '');
                if ($childurl === null || $childlabel === '' || $this->boost3_items_has_url($items, $childurl)) {
                    continue;
                }
                $items[] = [
                    'text' => $this->boost3_plain_nav_label($childlabel, 0),
                    'url' => $childurl,
                    'active' => !empty($child->isactive),
                ];
            }
        }
    }

    /**
     * @param mixed $action
     * @return string|null
     */
    protected function boost3_nav_url_from_action($action): ?string {
        if ($action instanceof moodle_url || $action instanceof core_url) {
            return $action->out(false);
        }
        if ($action instanceof action_link) {
            return $this->boost3_nav_url_from_action($action->url);
        }
        if (is_string($action) && $action !== '' && $action !== '#') {
            return $action;
        }
        return null;
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
     * Whether secondary navigation should use the gear menu (Mustache section helper).
     *
     * @return string Non-empty when the gear menu should replace horizontal tabs.
     */
    public function boost3_use_gear_secondary_nav(): string {
        global $PAGE;
        return $this->boost3_should_show_gear($PAGE) ? '1' : '';
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
        if ($page->pagelayout === 'popup' || $page->pagelayout === 'embedded') {
            return false;
        }
        if ($this->boost3_is_admin_page($page)) {
            return false;
        }
        return true;
    }

    /**
     * Site administration and related admin UI (keep default secondary navigation).
     *
     * @param moodle_page $page
     * @return bool
     */
    protected function boost3_is_admin_page(moodle_page $page): bool {
        if ($page->pagelayout === 'admin') {
            return true;
        }
        $pagetype = $page->pagetype ?? '';
        if ($pagetype !== '' && strpos($pagetype, 'admin-') === 0) {
            return true;
        }
        if ($page->url instanceof moodle_url) {
            $path = $page->url->get_path(false);
            if ($path === '/admin' || strpos($path, '/admin/') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $items
     * @param string $url
     * @return bool
     */
    protected function boost3_items_has_url(array $items, string $url): bool {
        foreach ($items as $item) {
            if (($item['url'] ?? '') === $url) {
                return true;
            }
        }
        return false;
    }
}
