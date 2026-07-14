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
 * Ensures $PAGE->navbar contains nodes from core navigation trees (Moodle 3.9 parity).
 *
 * We do not invent breadcrumb labels/URLs: items are copied from $PAGE->navigation,
 * $PAGE->settingsnav and $PAGE->secondarynav that core already built for the page.
 * theme_boost3\boostnavbar then stops Boost Union from stripping them in course context.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

use moodle_page;
use moodle_url;
use navigation_node;

/**
 * Fills $PAGE->navbar from existing navigation API when Boost 4 left it empty.
 */
class breadcrumb_builder {

    /** @var string[] Settingsnav keys that are containers, not breadcrumb segments. */
    private const SKIP_SETTINGS_KEYS = ['root', 'frontpage', 'mycourses', 'course', 'courses'];

    /**
     * Populate $PAGE->navbar from core navigation when legacy drawer needs M3 breadcrumbs.
     *
     * @param moodle_page $page
     * @return void
     */
    public static function populate(moodle_page $page): void {
        if (!self::should_populate($page)) {
            return;
        }

        $existing = $page->navbar->get_items();

        // Activity pages: core usually already added course → section → module.
        if ($page->context && $page->context->contextlevel == CONTEXT_MODULE && $existing !== []) {
            if (!self::navbar_has_legacy_prefix($page)) {
                self::prepend_site_prefix($page);
            }
            return;
        }

        if ($existing !== [] && !self::navbar_is_incomplete_stub($page, $existing)) {
            return;
        }

        self::rebuild_from_navigation($page);
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    public static function should_populate(moodle_page $page): bool {
        return navigation_policy::breadcrumbs_mode_for_page($page)
            === navigation_channel_profile::BREADCRUMBS_LEGACY_M39;
    }

    /**
     * Core/Union breadcrumb chain for /course/edit.php (pagelayout admin).
     *
     * Boost Union parent::prepare_nodes_for_boost() strips «Курсы» and the course node
     * in CONTEXT_COURSE; core often leaves $PAGE->navbar empty before boostnavbar runs.
     *
     * @param moodle_page $page
     * @return void
     */
    public static function populate_course_admin_union(moodle_page $page): void {
        if (!page_classifier::is_course_edit_page($page)) {
            return;
        }

        self::clear_navbar($page);

        self::add_global_nav_node($page, 'myhome', [navigation_node::TYPE_SETTING, navigation_node::TYPE_SYSTEM]);

        $courses = self::find_global_nav_node($page, 'courses', [
            navigation_node::TYPE_CUSTOM,
            navigation_node::TYPE_CATEGORY,
            navigation_node::TYPE_SYSTEM,
        ]);
        if (!$courses instanceof navigation_node) {
            $courses = $page->navigation->find('courses', navigation_node::TYPE_ROOTNODE);
        }
        if ($courses instanceof navigation_node) {
            self::add_node_to_navbar($page->navbar, $courses, true);
        } else {
            $page->navbar->add(
                get_string('courses'),
                new moodle_url('/course/index.php'),
                navigation_node::TYPE_CUSTOM,
                null,
                'courses'
            );
        }

        self::add_category_nodes($page);
        self::add_course_node($page);

        $tail = self::collect_course_edit_tail_nodes($page);
        if ($tail !== []) {
            foreach ($tail as $node) {
                self::add_node_to_navbar($page->navbar, $node, true);
            }
        } else {
            $label = trim((string) ($page->heading ?? ''));
            if ($label === '' && !empty($page->title)) {
                $label = trim((string) $page->title);
            }
            if ($label !== '') {
                $page->navbar->add($label);
            }
        }
    }

    /**
     * @param moodle_page $page
     * @return void
     */
    protected static function add_category_nodes(moodle_page $page): void {
        if (empty($page->categories)) {
            return;
        }
        foreach (array_reverse($page->categories) as $category) {
            $context = \context_coursecat::instance($category->id);
            if (!\core_course_category::can_view_category($category)) {
                continue;
            }
            $displaycontext = \context_helper::get_navigation_filter_context($context);
            $url = new moodle_url('/course/index.php', ['categoryid' => $category->id]);
            $name = format_string($category->name, true, ['context' => $displaycontext]);
            $page->navbar->add(
                $name,
                $url,
                \breadcrumb_navigation_node::TYPE_CATEGORY,
                null,
                $category->id
            );
        }
    }

    /**
     * Last segment on course/edit.php (e.g. «Настройки» / «Редактировать настройки»).
     *
     * @param moodle_page $page
     * @return navigation_node[]
     */
    protected static function collect_course_edit_tail_nodes(moodle_page $page): array {
        if ($page->settingsnav) {
            $active = $page->settingsnav->find_active_node();
            if ($active instanceof navigation_node) {
                return [$active];
            }
            foreach (['editsettings', 'settings', 'courseadmin'] as $key) {
                $node = $page->settingsnav->find($key, null);
                if ($node instanceof navigation_node) {
                    return [$node];
                }
            }
        }

        if ($page->has_secondary_navigation() && $page->secondarynav) {
            $active = $page->secondarynav->find_active_node();
            if ($active instanceof navigation_node && ($active->key ?? '') !== 'coursehome') {
                return [$active];
            }
            $edit = $page->secondarynav->find('editsettings', null);
            if ($edit instanceof navigation_node) {
                return [$edit];
            }
        }

        return [];
    }

    /**
     * Rebuild navbar from navigation trees (sources core already maintains).
     *
     * @param moodle_page $page
     * @return void
     */
    protected static function rebuild_from_navigation(moodle_page $page): void {
        self::clear_navbar($page);

        self::add_global_nav_node($page, 'myhome', [navigation_node::TYPE_SETTING, navigation_node::TYPE_SYSTEM]);

        // M3.9: «Мои курсы» — разделитель без ссылки (как на eos2).
        $mycourses = $page->navigation->find('mycourses', navigation_node::TYPE_ROOTNODE);
        if ($mycourses instanceof navigation_node) {
            self::add_node_to_navbar($page->navbar, $mycourses, false);
        } else {
            $page->navbar->add(get_string('mycourses'));
        }

        self::add_course_node($page);

        foreach (self::collect_context_nodes($page) as $node) {
            self::add_node_to_navbar($page->navbar, $node, true);
        }
    }

    /**
     * Prepend dashboard + mycourses before an existing mod/activity chain.
     *
     * @param moodle_page $page
     * @return void
     */
    protected static function prepend_site_prefix(moodle_page $page): void {
        $prepend = [];

        $myhome = self::find_global_nav_node($page, 'myhome', [navigation_node::TYPE_SETTING, navigation_node::TYPE_SYSTEM]);
        if ($myhome instanceof navigation_node) {
            $prepend[] = $myhome;
        }

        $mycourses = $page->navigation->find('mycourses', navigation_node::TYPE_ROOTNODE);
        if ($mycourses instanceof navigation_node) {
            $prepend[] = $mycourses;
        }

        if ($prepend === []) {
            return;
        }

        $existing = [];
        foreach ($page->navbar->get_items() as $item) {
            $existing[] = $item;
        }
        self::clear_navbar($page);

        foreach ($prepend as $node) {
            $withlink = ($node->key ?? '') !== 'mycourses';
            self::add_node_to_navbar($page->navbar, $node, $withlink);
        }
        foreach ($existing as $item) {
            $text = $item->text;
            if ($text instanceof \lang_string) {
                $text = $text->out();
            }
            $key = $item->key ?? null;
            $type = $item->type ?? navigation_node::TYPE_CUSTOM;
            if ($item->has_action()) {
                $page->navbar->add($text, $item->action, $type, null, $key);
            } else {
                $page->navbar->add($text, null, $type, null, $key);
            }
        }
    }

    /**
     * @param moodle_page $page
     * @return void
     */
    protected static function add_course_node(moodle_page $page): void {
        $course = $page->course ?? null;
        $courseid = (int) ($course->id ?? 0);
        // New course form (/course/edit.php without id): no real course crumb yet.
        if ($courseid <= 0 || $courseid == SITEID) {
            return;
        }

        $label = format_string($course->shortname ?: $course->fullname, true, [
            'context' => \context_course::instance($courseid),
        ]);
        $url = new moodle_url('/course/view.php', ['id' => $courseid]);

        if ($page->secondarynav) {
            $coursenode = $page->secondarynav->find('coursehome', null);
            if ($coursenode instanceof navigation_node) {
                $action = $coursenode->action();
                if ($action instanceof moodle_url) {
                    $url = $action;
                }
            }
        }

        $page->navbar->add(
            $label,
            $url,
            \breadcrumb_navigation_node::TYPE_COURSE,
            null,
            $courseid
        );
    }

    /**
     * Active branch from settingsnav (preferred) or secondarynav.
     *
     * @param moodle_page $page
     * @return navigation_node[]
     */
    protected static function collect_context_nodes(moodle_page $page): array {
        $nodes = [];

        if ($page->settingsnav) {
            $active = $page->settingsnav->find_active_node();
            if ($active instanceof navigation_node) {
                foreach (self::path_to_root($active) as $node) {
                    $key = $node->key ?? '';
                    if ($key === '' || in_array($key, self::SKIP_SETTINGS_KEYS, true)) {
                        continue;
                    }
                    $nodes[] = $node;
                }
                if ($nodes !== []) {
                    return $nodes;
                }
            }
        }

        if ($page->has_secondary_navigation() && $page->secondarynav) {
            $active = $page->secondarynav->find_active_node();
            if (!$active instanceof navigation_node) {
                foreach ($page->secondarynav->children as $child) {
                    if (is_object($child) && !empty($child->isactive)) {
                        $active = $child;
                        break;
                    }
                }
            }
            if ($active instanceof navigation_node && ($active->key ?? '') !== 'coursehome') {
                $nodes[] = $active;
            }
        }

        if ($nodes === [] && \theme_boost3_page_uses_participants_actionbar($page)) {
            $users = $page->settingsnav ? $page->settingsnav->find('users', null) : null;
            if ($users instanceof navigation_node) {
                $nodes[] = $users;
            }
        }

        return $nodes;
    }

    /**
     * @param navigation_node $node
     * @return navigation_node[]
     */
    protected static function path_to_root(navigation_node $node): array {
        $path = [];
        $current = $node;
        while ($current instanceof navigation_node) {
            array_unshift($path, $current);
            $parent = $current->parent ?? null;
            if (!$parent instanceof navigation_node || ($parent->key ?? '') === 'root') {
                break;
            }
            $current = $parent;
        }
        return $path;
    }

    /**
     * @param moodle_page $page
     * @param string $key
     * @param int[] $types
     * @return navigation_node|null
     */
    protected static function find_global_nav_node(moodle_page $page, string $key, array $types): ?navigation_node {
        foreach ($types as $type) {
            $node = $page->navigation->find($key, $type);
            if ($node instanceof navigation_node) {
                return $node;
            }
        }
        return null;
    }

    /**
     * @param moodle_page $page
     * @param string $key
     * @param int[] $types
     * @return void
     */
    protected static function add_global_nav_node(moodle_page $page, string $key, array $types): void {
        $node = self::find_global_nav_node($page, $key, $types);
        if ($node instanceof navigation_node) {
            self::add_node_to_navbar($page->navbar, $node, true);
        }
    }

    /**
     * Copy a navigation_node into the page navbar.
     *
     * @param \navbar $navbar
     * @param navigation_node $node
     * @param bool $includelink
     * @return void
     */
    protected static function add_node_to_navbar($navbar, navigation_node $node, bool $includelink): void {
        $text = $node->text;
        if ($text instanceof \lang_string) {
            $text = $text->out();
        }
        $text = trim(strip_tags((string) $text));
        if ($text === '') {
            return;
        }

        $action = $includelink ? $node->action() : null;
        if ($action instanceof moodle_url) {
            $navbar->add($text, $action, $node->type, null, $node->key ?? null);
        } else if (is_string($action) && $action !== '' && $action !== '#' && $includelink) {
            $navbar->add($text, new moodle_url($action), $node->type, null, $node->key ?? null);
        } else {
            $navbar->add($text, null, $node->type, null, $node->key ?? null);
        }
    }

    /**
     * Boost 4 often leaves only non-linked stubs before boostnavbar strips them.
     *
     * @param moodle_page $page
     * @param array $items
     * @return bool
     */
    protected static function navbar_is_incomplete_stub(moodle_page $page, array $items): bool {
        if ($items === []) {
            return true;
        }
        if (!self::navbar_has_legacy_prefix($page)) {
            $linked = 0;
            foreach ($items as $item) {
                if ($item->has_action()) {
                    $linked++;
                }
            }
            if ($linked === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clear manually added navbar crumbs.
     *
     * Important: {@see navbar} overrides `$children` as a plain array (not
     * navigation_node_collection). Calling navigation_node::remove() fatals with
     * "Call to a member function remove() on array" because parent->children has
     * no remove() method.
     *
     * @param moodle_page $page
     * @return void
     */
    protected static function clear_navbar(moodle_page $page): void {
        $navbar = $page->navbar;
        $navbar->children = [];
        // Only use crumbs we add next — do not re-merge active nav chain in get_items().
        $navbar->ignore_active(true);

        // Invalidate get_items() cache and prepended crumbs (both protected).
        $reflection = new \ReflectionClass($navbar);
        if ($reflection->hasProperty('items')) {
            $prop = $reflection->getProperty('items');
            $prop->setAccessible(true);
            $prop->setValue($navbar, null);
        }
        if ($reflection->hasProperty('prependchildren')) {
            $prop = $reflection->getProperty('prependchildren');
            $prop->setAccessible(true);
            $prop->setValue($navbar, []);
        }
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    protected static function navbar_has_legacy_prefix(moodle_page $page): bool {
        foreach ($page->navbar->get_items() as $item) {
            $action = $item->action;
            if ($action instanceof moodle_url && $action->compare(new moodle_url('/my/'), URL_MATCH_BASE)) {
                return true;
            }
            if (($item->key ?? '') === 'myhome') {
                return true;
            }
        }
        return false;
    }
}
