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
use context_course;
use core\url as core_url;
use moodle_page;
use moodle_url;
use navigation_node;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/theme/boost3/lib.php');

/**
 * Extends Boost Union renderer with a Moodle-3-style consolidated gear menu.
 */
class core_renderer extends \theme_boost_union\output\core_renderer {
    /**
     * Renders a dropdown that aggregates secondary navigation and common course actions.
     *
     * @return string HTML fragment (empty if nothing to show).
     */
    /**
     * Renders the Moodle-3-style flat left navigation drawer.
     *
     * @return string HTML fragment (empty when legacy drawer is inactive).
     */
    public function legacy_nav_drawer(): string {
        global $PAGE;

        if (!theme_boost3_legacy_drawer_active_for_page($PAGE)) {
            return '';
        }

        $sections = [];

        if ($PAGE->context->contextlevel >= CONTEXT_COURSE && $PAGE->course->id != SITEID) {
            $coursesection = $this->boost3_legacy_build_course_section($PAGE);
            if (!empty($coursesection['items'])) {
                $sections[] = $coursesection;
            }
        }

        $sitesection = $this->boost3_legacy_build_site_section($PAGE);
        if (!empty($sitesection['items'])) {
            $sections[] = $sitesection;
        }

        $mycoursessection = $this->boost3_legacy_build_mycourses_section($PAGE);
        if (!empty($mycoursessection['items'])) {
            $sections[] = $mycoursessection;
        }

        if (count($sections) === 0) {
            return '';
        }

        return $this->render_from_template('theme_boost3/legacy_nav_drawer', [
            'arialabel' => get_string('legacydrawernav', 'theme_boost3'),
            'sections' => $sections,
        ]);
    }

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
     * Inject gear into the page header row on course pages without horizontal secondary tabs.
     *
     * @return string
     */
    public function full_header() {
        if (theme_boost3_page_should_inline_gear_with_header($this->page)) {
            $gear = $this->gear_menu();
            if ($gear !== '') {
                $this->page->add_header_action($gear);
            }
        }

        return parent::full_header();
    }

    /**
     * Hide in-content participants tertiary select when it is shown in the gear menu.
     *
     * @param object $course
     * @param string|null $renderedcontent
     * @return string
     */
    public function render_participants_tertiary_nav($course, $renderedcontent = null): string {
        global $PAGE;

        if (theme_boost3_page_should_show_gear($PAGE) && theme_boost3_page_uses_participants_actionbar($PAGE)) {
            $actionbar = new \core\output\participants_action_bar($course, $PAGE, $renderedcontent);
            $context = $actionbar->export_for_template($this);
            unset($context['navigation']);
            if (empty($context['renderedcontent'])) {
                return '';
            }
            return $this->render_from_template('core_course/participants_actionbar', $context) ?: '';
        }

        return parent::render_participants_tertiary_nav($course, $renderedcontent);
    }

    /**
     * Build gear menu items for the current page.
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_build_gear_items(moodle_page $page): array {
        if (!$page->has_secondary_navigation()) {
            return [];
        }

        $hasoverflow = $this->boost3_has_navigation_overflow($page);

        if ($hasoverflow) {
            return $this->boost3_build_overflow_gear_items($page);
        }

        return $this->boost3_build_secondary_gear_items($page);
    }

    /**
     * Gear items from course secondary navigation tabs (default course pages).
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_build_secondary_gear_items(moodle_page $page): array {
        $items = [];
        $secondarynav = $page->secondarynav;
        if (!$secondarynav || empty($secondarynav->children)) {
            return $items;
        }

        foreach ($secondarynav->children as $child) {
            $this->boost3_collect_top_level_node($child, $items);
        }

        return $items;
    }

    /**
     * Gear items from tertiary overflow navigation (e.g. Users section sub-pages).
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_build_overflow_gear_items(moodle_page $page): array {
        $items = [];
        $secondarynav = $page->secondarynav;
        if (!$secondarynav) {
            return $items;
        }

        if (theme_boost3_page_uses_participants_actionbar($page)) {
            $participantitems = $this->boost3_build_participants_gear_items($page);
            if (count($participantitems) > 0) {
                return $participantitems;
            }
        }

        $overflowdata = $secondarynav->get_overflow_menu_data();
        if ($overflowdata !== null) {
            $overflowexport = $overflowdata->export_for_template($this);
            if (is_object($overflowexport)) {
                $overflowexport = (array) $overflowexport;
            }
            if (is_array($overflowexport) && !empty($overflowexport['options'])) {
                $this->boost3_collect_overflow_options($overflowexport['options'], $items);
                return $items;
            }
        }

        return $this->boost3_build_overflow_gear_items_from_settingsnav($page);
    }

    /**
     * Build overflow gear items from settings navigation when url_select is unavailable.
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_build_overflow_gear_items_from_settingsnav(moodle_page $page): array {
        $items = [];
        if (!$page->settingsnav || !$page->secondarynav) {
            return $items;
        }

        $activenode = $page->secondarynav->find_active_node();
        if (!$activenode) {
            foreach ($page->secondarynav->children as $child) {
                if (is_object($child) && !empty($child->isactive)) {
                    $activenode = $child;
                    break;
                }
            }
        }
        if (!$activenode) {
            return $items;
        }

        $excludedkeys = ['coursehome', 'questionbank', 'coursereports'];
        if (in_array($activenode->key, $excludedkeys, true)) {
            return $items;
        }

        $menunode = theme_boost3_resolve_settingsnav_menunode($page, $activenode);
        if (!is_object($menunode) || !method_exists($menunode, 'has_children') || !$menunode->has_children()) {
            return $items;
        }

        $this->boost3_collect_settingsnav_menu_node($menunode, $items);

        return $items;
    }

    /**
     * Gear items for the participants page (matches core participants_action_bar).
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_build_participants_gear_items(moodle_page $page): array {
        $items = [];
        $actionbar = new \core\output\participants_action_bar($page->course, $page, null);
        $dropdown = $actionbar->get_dropdown($this);
        if ($dropdown === null) {
            return $items;
        }

        $data = json_decode(json_encode($dropdown), true);
        if (!is_array($data) || empty($data['options']) || !is_array($data['options'])) {
            return $items;
        }

        $this->boost3_collect_overflow_options($data['options'], $items);

        return $items;
    }

    /**
     * Collect gear items from a settings navigation subtree (with optional group headers).
     *
     * @param object $node
     * @param array $items
     */
    protected function boost3_collect_settingsnav_menu_node($node, array &$items): void {
        if (!method_exists($node, 'has_children') || !$node->has_children() || empty($node->children)) {
            return;
        }

        foreach ($node->children as $child) {
            if (!is_object($child) || (property_exists($child, 'display') && $child->display === false)) {
                continue;
            }

            $url = method_exists($child, 'action') ? $this->boost3_nav_url_from_action($child->action()) : null;
            $label = method_exists($child, 'get_text') ? $child->get_text() :
                (property_exists($child, 'text') ? (string) $child->text : '');

            if ($url !== null && $label !== '' && !$this->boost3_items_has_url($items, $url)) {
                $items[] = [
                    'text' => $this->boost3_plain_nav_label($label, 0),
                    'url' => $url,
                    'active' => !empty($child->isactive),
                ];
                continue;
            }

            if (method_exists($child, 'has_children') && $child->has_children() && !empty($child->children)) {
                $grouplabel = $this->boost3_plain_nav_label($label, 0);
                if ($grouplabel === '') {
                    continue;
                }
                if (count($items) > 0) {
                    $items[] = ['divider' => true];
                }
                $items[] = ['header' => $grouplabel];
                foreach ($child->children as $grandchild) {
                    if (!is_object($grandchild) || (property_exists($grandchild, 'display') && $grandchild->display === false)) {
                        continue;
                    }
                    $childurl = method_exists($grandchild, 'action') ? $this->boost3_nav_url_from_action($grandchild->action()) : null;
                    $grandlabel = method_exists($grandchild, 'get_text') ? $grandchild->get_text() :
                        (property_exists($grandchild, 'text') ? (string) $grandchild->text : '');
                    if ($childurl === null || $grandlabel === '' || $this->boost3_items_has_url($items, $childurl)) {
                        continue;
                    }
                    $items[] = [
                        'text' => $this->boost3_plain_nav_label($grandlabel, 0),
                        'url' => $childurl,
                        'active' => !empty($grandchild->isactive),
                    ];
                }
            }
        }
    }

    /**
     * Collect links from url_select export (supports optgroups).
     *
     * @param array $options
     * @param array $items
     */
    protected function boost3_collect_overflow_options(array $options, array &$items): void {
        foreach ($options as $option) {
            if (is_object($option)) {
                $option = (array) $option;
            }
            if (!is_array($option)) {
                continue;
            }

            if (!empty($option['isgroup']) && !empty($option['options']) && is_array($option['options'])) {
                if (count($items) > 0) {
                    $items[] = ['divider' => true];
                }
                $groupname = $this->boost3_plain_nav_label($option['name'] ?? '', 0);
                if ($groupname !== '') {
                    $items[] = ['header' => $groupname];
                }
                $this->boost3_collect_overflow_options($option['options'], $items);
                continue;
            }

            if (!empty($option['disabled'])) {
                continue;
            }

            $value = isset($option['value']) ? (string) $option['value'] : '';
            $name = isset($option['name']) ? (string) $option['name'] : '';
            if ($value === '' || $name === '') {
                continue;
            }

            $url = $this->boost3_overflow_option_url($value);
            if ($url === null || $this->boost3_items_has_url($items, $url)) {
                continue;
            }

            $items[] = [
                'text' => $this->boost3_plain_nav_label($name, 0),
                'url' => $url,
                'active' => !empty($option['selected']),
            ];
        }
    }

    /**
     * Build a navigable URL from an overflow option value.
     *
     * @param string $value
     * @return string|null
     */
    protected function boost3_overflow_option_url(string $value): ?string {
        if ($value === '') {
            return null;
        }
        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }
        if ($value[0] === '/') {
            return (new moodle_url($value))->out(false);
        }
        return (new moodle_url('/course/jumpto.php', ['jump' => $value]))->out(false);
    }

    /**
     * Whether the page uses tertiary overflow navigation.
     *
     * @param moodle_page $page
     * @return bool
     */
    protected function boost3_has_navigation_overflow(moodle_page $page): bool {
        return theme_boost3_page_has_navigation_overflow($page);
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
     * Whether the gear menu should be offered on this page.
     *
     * @param moodle_page $page
     * @return bool
     */
    protected function boost3_should_show_gear(moodle_page $page): bool {
        return theme_boost3_page_should_show_gear($page);
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

    /**
     * Course block: home, secondary tabs, section links (topics/weeks only).
     *
     * @param moodle_page $page
     * @return array{title: string, items: array}
     */
    protected function boost3_legacy_build_course_section(moodle_page $page): array {
        $items = [];
        $course = $page->course;

        $homeurl = (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        $items[] = [
            'text' => format_string($course->fullname, true, ['context' => context_course::instance($course->id)]),
            'url' => $homeurl,
            'active' => $page->pagetype === 'course-view',
        ];

        if ($page->has_secondary_navigation() && $page->secondarynav) {
            foreach ($page->secondarynav->children as $child) {
                $this->boost3_collect_top_level_node($child, $items);
            }
        }

        foreach ($this->boost3_legacy_build_course_section_links($page) as $sectionitem) {
            if (!$this->boost3_items_has_url($items, $sectionitem['url'])) {
                $items[] = $sectionitem;
            }
        }

        return [
            'title' => '',
            'items' => $items,
        ];
    }

    /**
     * Flat section links for topics/weeks course formats.
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_legacy_build_course_section_links(moodle_page $page): array {
        global $DB;

        $course = $page->course;
        $modinfo = get_fast_modinfo($course);
        $items = [];
        $currentsection = null;
        if ($page->context->contextlevel == CONTEXT_COURSE) {
            $sectionnum = optional_param('section', null, PARAM_INT);
            if ($sectionnum !== null) {
                $currentsection = $sectionnum;
            }
        }

        $sectionrecords = $DB->get_records('course_sections', ['course' => $course->id], 'section ASC');
        foreach ($sectionrecords as $sectionrecord) {
            $sectionnum = (int) $sectionrecord->section;
            $sectioninfo = $modinfo->get_section_info($sectionnum);
            if (!empty($sectioninfo->deletioninprogress)) {
                continue;
            }
            $name = trim(format_string(get_section_name($course, $sectionnum)));
            if ($name === '') {
                continue;
            }
            $sectionurl = new moodle_url('/course/section.php', ['id' => $sectionrecord->id]);
            $items[] = [
                'text' => $name,
                'url' => $sectionurl->out(false),
                'active' => ($currentsection !== null && (int) $currentsection === (int) $sectionnum),
            ];
        }

        return $items;
    }

    /**
     * Site block from global navigation.
     *
     * @param moodle_page $page
     * @return array{title: string, items: array}
     */
    protected function boost3_legacy_build_site_section(moodle_page $page): array {
        $items = [];
        $sitekeys = ['myhome', 'home', 'calendar', 'privatefiles', 'contentbank'];

        foreach ($sitekeys as $key) {
            $node = $page->navigation->find($key, null);
            if (!is_object($node)) {
                continue;
            }
            $this->boost3_legacy_collect_nav_node($node, $items);
        }

        return [
            'title' => get_string('legacydrawersite', 'theme_boost3'),
            'items' => $items,
        ];
    }

    /**
     * My courses block from global navigation (supports optional plugin extensions).
     *
     * @param moodle_page $page
     * @return array{title: string, items: array}
     */
    protected function boost3_legacy_build_mycourses_section(moodle_page $page): array {
        $root = $page->navigation->find('course_collections', navigation_node::TYPE_ROOTNODE);
        if (!is_object($root)) {
            $root = $page->navigation->find('mycourses', navigation_node::TYPE_ROOTNODE);
        }
        if (!is_object($root)) {
            return ['title' => '', 'items' => []];
        }

        $items = [];
        $this->boost3_legacy_flatten_nav_branch($root, $items, 2);

        $title = method_exists($root, 'get_content') ? $root->get_content() : (string) $root->text;
        if ($title === '') {
            $title = get_string('mycourses');
        }

        return [
            'title' => $title,
            'items' => $items,
        ];
    }

    /**
     * Collect a single visible navigation node as a drawer item.
     *
     * @param object $node
     * @param array $items
     */
    protected function boost3_legacy_collect_nav_node($node, array &$items): void {
        if (!is_object($node) || (property_exists($node, 'display') && $node->display === false)) {
            return;
        }

        $url = method_exists($node, 'action') ? $this->boost3_nav_url_from_action($node->action()) : null;
        $label = method_exists($node, 'get_text') ? $node->get_text() :
            (property_exists($node, 'text') ? (string) $node->text : '');

        if ($url === null || $label === '') {
            return;
        }

        $plainurl = $url;
        if (!$this->boost3_items_has_url($items, $plainurl)) {
            $items[] = [
                'text' => $this->boost3_plain_nav_label($label, 0),
                'url' => $plainurl,
                'active' => !empty($node->isactive),
            ];
        }
    }

    /**
     * Flatten a navigation branch for the legacy drawer (limited depth).
     *
     * @param object $node
     * @param array $items
     * @param int $maxdepth
     * @param int $depth
     */
    protected function boost3_legacy_flatten_nav_branch($node, array &$items, int $maxdepth = 2, int $depth = 0): void {
        if (!is_object($node) || $depth >= $maxdepth || !method_exists($node, 'has_children') || !$node->has_children()) {
            return;
        }

        foreach ($node->children as $child) {
            if (!is_object($child) || (property_exists($child, 'display') && $child->display === false)) {
                continue;
            }

            $url = method_exists($child, 'action') ? $this->boost3_nav_url_from_action($child->action()) : null;
            $label = method_exists($child, 'get_text') ? $child->get_text() :
                (property_exists($child, 'text') ? (string) $child->text : '');

            if ($url !== null && $label !== '') {
                $plainurl = $url;
                if (!$this->boost3_items_has_url($items, $plainurl)) {
                    $items[] = [
                        'text' => $this->boost3_plain_nav_label($label, 0),
                        'url' => $plainurl,
                        'active' => !empty($child->isactive),
                    ];
                }
                continue;
            }

            if ($label !== '' && method_exists($child, 'has_children') && $child->has_children()) {
                $items[] = [
                    'text' => $this->boost3_plain_nav_label($label, 0),
                    'isheader' => true,
                ];
                $this->boost3_legacy_flatten_nav_branch($child, $items, $maxdepth, $depth + 1);
            }
        }
    }
}
