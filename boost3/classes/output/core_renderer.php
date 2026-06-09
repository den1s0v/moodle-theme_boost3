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
use pix_icon;
use theme_boost3\active_state_resolver;

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

        if (theme_boost3_legacy_drawer_active_for_page($page)) {
            if ($this->boost3_has_navigation_overflow($page)) {
                return $this->boost3_build_overflow_gear_items($page);
            }
            return $this->boost3_build_secondary_gear_excluded_items($page);
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
            $this->boost3_collect_gear_secondary_node($child, $items);
        }

        return $items;
    }

    /**
     * Gear items for secondary tabs not shown in the legacy drawer.
     *
     * @param moodle_page $page
     * @return array<int, array<string, mixed>>
     */
    protected function boost3_build_secondary_gear_excluded_items(moodle_page $page): array {
        $items = [];
        $secondarynav = $page->secondarynav;
        if (!$secondarynav || empty($secondarynav->children)) {
            return $items;
        }

        foreach ($secondarynav->children as $child) {
            $this->boost3_collect_gear_secondary_node($child, $items, true);
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

        $excludedkeys = theme_boost3_gear_overflow_excluded_keys();
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
                $items[] = $this->boost3_gear_make_item(
                    $this->boost3_plain_nav_label($label, 0),
                    $url,
                    $this->boost3_active_resolver()->from_nav_node(!empty($child->isactive), $url),
                    $child
                );
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
                    $items[] = $this->boost3_gear_make_item(
                        $this->boost3_plain_nav_label($grandlabel, 0),
                        $childurl,
                        $this->boost3_active_resolver()->from_nav_node(!empty($grandchild->isactive), $childurl),
                        $grandchild
                    );
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

            $items[] = $this->boost3_gear_make_item(
                $this->boost3_plain_nav_label($name, 0),
                $url,
                !empty($option['selected']) || $this->boost3_active_resolver()->from_nav_node(false, $url)
            );
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
     * Collect a single top-level secondary navigation tab for the gear menu.
     *
     * @param object $node
     * @param array $items
     * @param bool $excludedonly When true, skip tabs that belong in the legacy drawer.
     */
    protected function boost3_collect_gear_secondary_node($node, array &$items, bool $excludedonly = false): void {
        if (!is_object($node)) {
            return;
        }

        if (property_exists($node, 'display') && $node->display === false) {
            return;
        }

        $key = $node->key ?? '';
        if ($key === 'coursehome') {
            return;
        }
        if ($excludedonly && theme_boost3_legacy_drawer_course_key_allowed($key)) {
            return;
        }

        $url = method_exists($node, 'action') ? $this->boost3_nav_url_from_action($node->action()) : null;
        $label = method_exists($node, 'get_text') ? $node->get_text() :
            (property_exists($node, 'text') ? (string) $node->text : '');

        if ($url !== null && $label !== '' && !$this->boost3_items_has_url($items, $url)) {
            $items[] = $this->boost3_gear_make_item(
                $this->boost3_plain_nav_label($label, 0),
                $url,
                $this->boost3_active_resolver()->from_nav_node(!empty($node->isactive), $url),
                $node
            );
        }
    }

    /**
     * Collect one whitelisted secondary tab for the legacy drawer (direct links only).
     *
     * @param object $node
     * @param array $items
     */
    protected function boost3_collect_legacy_drawer_secondary_node($node, array &$items): void {
        if (!is_object($node) || !theme_boost3_secondary_nav_node_has_link($node)) {
            return;
        }
        if (property_exists($node, 'display') && $node->display === false) {
            return;
        }

        $url = $this->boost3_nav_url_from_action($node->action());
        $label = method_exists($node, 'get_text') ? $node->get_text() :
            (property_exists($node, 'text') ? (string) $node->text : '');

        if ($url === null || $label === '' || $this->boost3_items_has_url($items, $url)) {
            return;
        }

        $items[] = $this->boost3_legacy_make_item(
            $this->boost3_plain_nav_label($label, 0),
            $url,
            $this->boost3_active_resolver()->from_nav_node(!empty($node->isactive), $url),
            null,
            $node
        );
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
     * Shared active-state resolver for drawer and gear items.
     *
     * @return active_state_resolver
     */
    protected function boost3_active_resolver(): active_state_resolver {
        return new active_state_resolver($this->page);
    }

    /**
     * Export a pix_icon for legacy_nav_drawer mustache (same shape as Boost flat_navigation).
     *
     * @param pix_icon|null $icon
     * @return array{pix: string, component: string, alt: string}|null
     */
    protected function boost3_legacy_export_icon(?pix_icon $icon): ?array {
        if (!$icon instanceof pix_icon || $icon->pix === '') {
            return null;
        }

        $data = $icon->export_for_pix();
        $component = $data['component'] ?? 'core';
        if ($component === 'moodle') {
            $component = 'core';
        }

        return [
            'pix' => $data['key'],
            'component' => $component,
            'alt' => $data['title'] ?? '',
        ];
    }

    /**
     * Resolve icon metadata from a navigation node (with settingsnav/navigation fallback by key).
     *
     * @param moodle_page $page
     * @param object|null $node
     * @return array{pix: string, component: string, alt: string}|null
     */
    protected function boost3_legacy_item_icon_from_node(moodle_page $page, $node): ?array {
        if (!is_object($node) || !empty($node->hideicon)) {
            return null;
        }

        $nodekey = $node->key ?? '';

        if ($node->icon instanceof pix_icon) {
            $exported = $this->boost3_legacy_export_icon($node->icon);
            if ($exported !== null && $this->boost3_legacy_exported_icon_is_usable($exported)) {
                return $exported;
            }
        }

        if ($nodekey === '') {
            return null;
        }

        foreach ([$page->settingsnav, $page->navigation] as $tree) {
            if (!$tree) {
                continue;
            }
            $match = $tree->find($nodekey, null);
            if (is_object($match) && empty($match->hideicon) && $match->icon instanceof pix_icon) {
                $exported = $this->boost3_legacy_export_icon($match->icon);
                if ($exported !== null && $this->boost3_legacy_exported_icon_is_usable($exported)) {
                    return $exported;
                }
            }
        }

        if ($page->settingsnav) {
            foreach (theme_boost3_secondary_settingsnav_keys($nodekey) as $settingskey) {
                $match = $page->settingsnav->find($settingskey, null);
                if (is_object($match) && empty($match->hideicon) && $match->icon instanceof pix_icon) {
                    $exported = $this->boost3_legacy_export_icon($match->icon);
                    if ($exported !== null && $this->boost3_legacy_exported_icon_is_usable($exported)) {
                        return $exported;
                    }
                }
            }
        }

        return $this->boost3_legacy_fallback_icon_for_key($nodekey);
    }

    /**
     * Known-good pix icons for secondary navigation keys (overrides broken FA mappings).
     *
     * @param string $key
     * @return array{pix: string, component: string, alt: string}|null
     */
    protected function boost3_legacy_fallback_icon_for_key(string $key): ?array {
        $fallbackicons = [
            'questionbank' => 'i/questions',
            'participants' => 'i/users',
            'users' => 'i/users',
            'enrol' => 'i/users',
            'enrolments' => 'i/users',
            'instances' => 'i/users',
            'otherusers' => 'i/users',
            'enrolotherusers' => 'i/users',
            'renameroles' => 'i/permissions',
            'roles' => 'i/permissions',
            'permissions' => 'i/permissions',
            'override' => 'i/permissions',
            'check' => 'i/permissions',
            'assign' => 'i/permissions',
            'groups' => 'i/group',
            'groupings' => 'i/group',
            'overview' => 'i/group',
            'grades' => 'i/grades',
            'gradeadmin' => 'i/grades',
            'competencies' => 'i/competencies',
            'competency' => 'i/competencies',
            'editsettings' => 'i/settings',
            'settings' => 'i/settings',
            'courseedit' => 'i/settings',
            'coursereports' => 'i/report',
            'reports' => 'i/report',
            'coursecompletion' => 'i/course',
            'completion' => 'i/course',
            'badges' => 'i/badge',
            'contentbank' => 'i/contentbank',
            'filtermanagement' => 'i/filter',
            'filters' => 'i/filter',
            'coursetools' => 'i/externallink',
            'lti' => 'i/externallink',
            'backup' => 'i/backup',
            'restore' => 'i/restore',
            'reuse' => 'i/restore',
            'unenrolself' => 'i/user',
        ];

        if ($key === '' || !isset($fallbackicons[$key])) {
            return null;
        }

        return $this->boost3_legacy_export_icon(new pix_icon($fallbackicons[$key], '', 'core'));
    }

    /**
     * Known-good pix icons for common settings navigation URLs.
     *
     * @param string $url
     * @return array{pix: string, component: string, alt: string}|null
     */
    protected function boost3_legacy_fallback_icon_for_url(string $url): ?array {
        if ($url === '') {
            return null;
        }
        try {
            $target = new moodle_url($url);
            $path = $target->get_path(false);
        } catch (\moodle_exception $e) {
            return null;
        }

        $pathicons = [
            '/user/index.php' => 'i/users',
            '/enrol/instances.php' => 'i/users',
            '/enrol/otherusers.php' => 'i/users',
            '/enrol/renameroles.php' => 'i/permissions',
            '/group/index.php' => 'i/group',
            '/group/groupings.php' => 'i/group',
            '/group/overview.php' => 'i/group',
            '/admin/roles/permissions.php' => 'i/permissions',
            '/admin/roles/check.php' => 'i/permissions',
            '/admin/roles/override.php' => 'i/permissions',
            '/admin/roles/assign.php' => 'i/permissions',
            '/course/completion.php' => 'i/course',
            '/filter/manage.php' => 'i/filter',
            '/backup/backup.php' => 'i/backup',
            '/backup/restore.php' => 'i/restore',
            '/badges/index.php' => 'i/badge',
            '/mod/lti/coursetools.php' => 'i/externallink',
            '/report/view.php' => 'i/report',
            '/question/edit.php' => 'i/questions',
        ];

        if (!isset($pathicons[$path])) {
            return null;
        }

        return $this->boost3_legacy_export_icon(new pix_icon($pathicons[$path], '', 'core'));
    }

    /**
     * Whether exported icon data is likely to render a visible glyph.
     *
     * @param array{pix: string, component: string, alt: string} $icon
     * @return bool
     */
    protected function boost3_legacy_exported_icon_is_usable(array $icon): bool {
        $pix = trim($icon['pix'] ?? '');
        if ($pix === '' || $pix === 'spacer' || $pix === 'i/none') {
            return false;
        }
        return true;
    }

    /**
     * Build one legacy drawer item array.
     *
     * @param string $text
     * @param string $url
     * @param bool $active
     * @param array{pix: string, component: string, alt: string}|null $icon
     * @return array<string, mixed>
     */
    protected function boost3_legacy_make_item(
        string $text,
        string $url,
        bool $active = false,
        ?array $icon = null,
        $node = null
    ): array {
        $item = [
            'text' => $text,
            'url' => $url,
            'active' => $active,
        ];
        $iconhtml = $this->boost3_resolve_item_icon_html($this->page, $url, $node, $text, $icon);
        if ($iconhtml !== '') {
            $item['iconhtml'] = $iconhtml;
        }
        return $item;
    }

    /**
     * Build one gear menu item (Moodle 3.9 style with optional icon).
     *
     * @param string $text
     * @param string $url
     * @param bool $active
     * @param object|null $node Navigation node for icon resolution.
     * @return array<string, mixed>
     */
    protected function boost3_gear_make_item(string $text, string $url, bool $active, $node = null): array {
        $item = [
            'text' => $text,
            'url' => $url,
            'active' => $active,
        ];
        $iconhtml = $this->boost3_resolve_item_icon_html($this->page, $url, $node, $text);
        if ($iconhtml !== '') {
            $item['iconhtml'] = $iconhtml;
        }
        return $item;
    }

    /**
     * Resolve visible icon HTML for drawer/gear items (pix fallbacks over broken FA).
     *
     * @param moodle_page $page
     * @param string $url
     * @param object|null $node
     * @param string $alttext
     * @param array{pix: string, component: string, alt: string}|null $preficon
     * @return string
     */
    protected function boost3_resolve_item_icon_html(
        moodle_page $page,
        string $url,
        $node,
        string $alttext,
        ?array $preficon = null
    ): string {
        $candidates = [];
        if ($preficon !== null) {
            $candidates[] = $preficon;
        }
        if (is_object($node)) {
            $candidates[] = $this->boost3_legacy_item_icon_from_node($page, $node);
        }
        if ($page->secondarynav && $url !== '') {
            $secondarymatch = $this->boost3_find_settingsnav_node_by_url($page->secondarynav, $url);
            if ($secondarymatch !== null) {
                $candidates[] = $this->boost3_legacy_item_icon_from_node($page, $secondarymatch);
            }
        }
        $candidates[] = $this->boost3_legacy_item_icon_from_settingsnav_url($page, $url);
        if (is_object($node) && !empty($node->key)) {
            $candidates[] = $this->boost3_legacy_fallback_icon_for_key($node->key);
        }
        $candidates[] = $this->boost3_legacy_fallback_icon_for_url($url);
        $candidates[] = $this->boost3_legacy_export_icon(new pix_icon('i/navigationitem', '', 'core'));

        foreach ($candidates as $icon) {
            $html = $this->boost3_render_item_icon_html($icon, $alttext);
            if ($html !== '') {
                return $html;
            }
        }
        return '';
    }

    /**
     * @param array{pix: string, component: string, alt: string}|null $icon
     * @param string $alttext
     * @return string
     */
    protected function boost3_render_item_icon_html(?array $icon, string $alttext): string {
        if ($icon === null || empty($icon['pix'])) {
            return '';
        }
        $html = $this->pix_icon(
            $icon['pix'],
            $icon['alt'] ?? $alttext,
            $icon['component'] ?? 'core'
        );
        return $this->boost3_icon_html_is_visible($html) ? $html : '';
    }

    /**
     * Whether rendered pix_icon HTML shows a real glyph (not empty/broken FA).
     *
     * @param string $html
     * @return bool
     */
    protected function boost3_icon_html_is_visible(string $html): bool {
        if (trim($html) === '') {
            return false;
        }
        // Broken FA mapping from some navigation nodes (no glyph name).
        if (strpos($html, 'fa-fw fa-fw') !== false) {
            return false;
        }
        if (strpos($html, '<img ') !== false) {
            static $brokenimgpix = [
                'i/award', 'i/external', 'i/roles', 'i/completion',
                'i/check', 'i/flag', 'i/plug', 'i/lti',
            ];
            if (preg_match('/<img[^>]+src="[^"]*\/([^"\/]+)"/', $html, $imgmatches)) {
                if (in_array('i/' . $imgmatches[1], $brokenimgpix, true)) {
                    return false;
                }
            }
            return true;
        }
        if (strpos($html, '<svg ') !== false) {
            return true;
        }
        // Named FA glyph (e.g. fa-user-group); trailing fa-fw alone is normal in Moodle.
        if (preg_match('/\bfa-(?!fw\b)[a-z0-9-]+/', $html)) {
            return true;
        }
        return false;
    }

    /**
     * Resolve icon from settings navigation by matching item URL.
     *
     * @param moodle_page $page
     * @param string $url
     * @return array{pix: string, component: string, alt: string}|null
     */
    protected function boost3_legacy_item_icon_from_settingsnav_url(moodle_page $page, string $url): ?array {
        if (!$page->settingsnav || $url === '') {
            return null;
        }
        $match = $this->boost3_find_settingsnav_node_by_url($page->settingsnav, $url);
        if ($match !== null) {
            return $this->boost3_legacy_item_icon_from_node($page, $match);
        }
        return null;
    }

    /**
     * @param object $node
     * @param string $url
     * @return object|null
     */
    protected function boost3_find_settingsnav_node_by_url($node, string $url) {
        if (!is_object($node)) {
            return null;
        }
        if (method_exists($node, 'action')) {
            $nodeurl = $this->boost3_nav_url_from_action($node->action());
            if ($nodeurl !== null && $nodeurl === $url) {
                return $node;
            }
        }
        if (method_exists($node, 'has_children') && $node->has_children() && !empty($node->children)) {
            foreach ($node->children as $child) {
                $found = $this->boost3_find_settingsnav_node_by_url($child, $url);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
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
        $items[] = $this->boost3_legacy_make_item(
            format_string($course->fullname, true, ['context' => context_course::instance($course->id)]),
            $homeurl,
            $this->boost3_active_resolver()->course_home(),
            $this->boost3_legacy_export_icon(new pix_icon('i/course', '', 'core'))
        );

        if ($page->has_secondary_navigation() && $page->secondarynav) {
            $keyednodes = [];
            foreach ($page->secondarynav->children as $child) {
                if (is_object($child) && !empty($child->key)) {
                    $keyednodes[$child->key] = $child;
                }
            }
            foreach (theme_boost3_legacy_drawer_course_keys() as $drawerkey) {
                foreach (theme_boost3_legacy_drawer_course_key_variants($drawerkey) as $variant) {
                    if (!isset($keyednodes[$variant])) {
                        continue;
                    }
                    $this->boost3_collect_legacy_drawer_secondary_node($keyednodes[$variant], $items);
                    break;
                }
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

        if (!theme_boost3_legacy_drawer_supports_section_links($page)) {
            return [];
        }

        $course = $page->course;
        $resolver = $this->boost3_active_resolver();
        $modinfo = get_fast_modinfo($course);
        $items = [];
        $linkmode = theme_boost3_legacy_drawer_section_link_mode();

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

            if ($linkmode === 'anchor') {
                $sectionurl = new moodle_url('/course/view.php', ['id' => $course->id], 'section-' . $sectionnum);
            } else {
                $sectionurl = new moodle_url('/course/section.php', ['id' => $sectionrecord->id]);
            }
            $isactive = $resolver->section($sectionnum, $sectionurl->out(false), $linkmode);

            $items[] = $this->boost3_legacy_make_item(
                $name,
                $sectionurl->out(false),
                $isactive,
                $this->boost3_legacy_export_icon(new pix_icon('i/section', ''))
            );
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

        foreach (theme_boost3_legacy_drawer_site_keys() as $key) {
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
            $items[] = $this->boost3_legacy_make_item(
                $this->boost3_plain_nav_label($label, 0),
                $plainurl,
                $this->boost3_active_resolver()->from_nav_node(!empty($node->isactive), $plainurl),
                null,
                $node
            );
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
                    $items[] = $this->boost3_legacy_make_item(
                        $this->boost3_plain_nav_label($label, 0),
                        $plainurl,
                        $this->boost3_active_resolver()->mycourses_item(!empty($child->isactive), $plainurl),
                        null,
                        $child
                    );
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
