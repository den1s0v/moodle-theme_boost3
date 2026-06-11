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
 * Moodle 3.9-style breadcrumb chain for course pages when legacy drawer hides secondary tabs.
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
 * Builds navbar breadcrumbs matching Moodle 3.9 UX.
 */
class breadcrumb_builder {

    /**
     * Populate $PAGE->navbar when legacy drawer is active and core left breadcrumbs empty.
     *
     * @param moodle_page $page
     * @return void
     */
    public static function populate(moodle_page $page): void {
        if (!self::should_populate($page)) {
            return;
        }

        $prefix = self::build_site_prefix_items();
        $courseitem = self::build_course_item($page);
        $existingtail = self::export_navbar_tail($page);

        // Mod/activity pages: core already built course → section → module; prepend dashboard prefix only.
        if ($existingtail !== [] && self::tail_includes_course($existingtail, $page)
                && count($existingtail) > 1) {
            self::replace_navbar($page, array_merge($prefix, $existingtail));
            return;
        }

        $suffix = self::build_context_suffix($page, []);
        $items = array_merge($prefix, [$courseitem], $suffix);
        self::replace_navbar($page, $items);
    }

    /**
     * Whether this page should get M3-style breadcrumbs.
     *
     * @param moodle_page $page
     * @return bool
     */
    public static function should_populate(moodle_page $page): bool {
        if (!\theme_boost3_legacy_drawer_active_for_page($page)) {
            return false;
        }
        if (!\theme_boost3_page_is_course_scoped_page($page)) {
            return false;
        }
        $kind = navigation_policy::classify($page);
        if ($kind === navigation_policy::KIND_COURSE_ADMIN) {
            return false;
        }
        if (self::navbar_has_legacy_prefix($page)) {
            return false;
        }
        return true;
    }

    /**
     * @return array<int, array{text: string, url: ?moodle_url}>
     */
    protected static function build_site_prefix_items(): array {
        return [
            ['text' => get_string('myhome'), 'url' => new moodle_url('/my/')],
            ['text' => get_string('mycourses'), 'url' => null],
        ];
    }

    /**
     * @param moodle_page $page
     * @return array{text: string, url: moodle_url}
     */
    protected static function build_course_item(moodle_page $page): array {
        $course = $page->course;
        $courselabel = format_string($course->shortname ?: $course->fullname, true, [
            'context' => \context_course::instance($course->id),
        ]);

        return [
            'text' => $courselabel,
            'url' => new moodle_url('/course/view.php', ['id' => $course->id]),
        ];
    }

    /**
     * @param array<int, array{text: string, url: ?moodle_url}> $tail
     * @param moodle_page $page
     * @return bool
     */
    protected static function tail_includes_course(array $tail, moodle_page $page): bool {
        $courseurl = new moodle_url('/course/view.php', ['id' => $page->course->id]);
        $courselabel = format_string($page->course->shortname ?: $page->course->fullname, true, [
            'context' => \context_course::instance($page->course->id),
        ]);
        foreach ($tail as $item) {
            if ($item['url'] instanceof moodle_url && $item['url']->compare($courseurl, URL_MATCH_BASE)) {
                return true;
            }
            if ($item['text'] === $courselabel) {
                return true;
            }
        }
        return false;
    }

    /**
     * Existing navbar nodes after core/mod setup (course → section → activity, grade crumbs, etc.).
     *
     * @param moodle_page $page
     * @return array<int, array{text: string, url: ?moodle_url}>
     */
    protected static function export_navbar_tail(moodle_page $page): array {
        $tail = [];
        $children = $page->navbar->children ?? [];
        if (!is_array($children)) {
            return $tail;
        }

        foreach ($children as $child) {
            if (!($child instanceof navigation_node)) {
                continue;
            }
            $text = trim(strip_tags($child->text instanceof \lang_string ? $child->text->out() : (string) $child->text));
            if ($text === '') {
                continue;
            }
            $url = null;
            $action = $child->action();
            if ($action instanceof moodle_url) {
                $url = $action;
            }
            $tail[] = ['text' => $text, 'url' => $url];
        }

        return $tail;
    }

    /**
     * Secondary / settings context when navbar is empty (typical course secondary pages).
     *
     * @param moodle_page $page
     * @param array<int, array{text: string, url: ?moodle_url}> $existingtail
     * @return array<int, array{text: string, url: ?moodle_url}>
     */
    protected static function build_context_suffix(moodle_page $page, array $existingtail): array {
        if ($existingtail !== []) {
            return [];
        }

        if (navigation_policy::classify($page) === navigation_policy::KIND_COURSE_FORMAT) {
            return [];
        }

        $items = [];

        if ($page->has_secondary_navigation() && $page->secondarynav) {
            $path = self::collect_active_secondary_path($page);
            foreach ($path as $node) {
                $text = self::node_text($node);
                if ($text === '' || ($node->key ?? '') === 'coursehome') {
                    continue;
                }
                $url = self::node_url($node);
                $items[] = ['text' => $text, 'url' => $url];
            }
        }

        if ($items === [] && $page->settingsnav) {
            $activenode = $page->settingsnav->find_active_node();
            if ($activenode instanceof navigation_node) {
                $ancestors = self::collect_settings_ancestors($activenode);
                foreach ($ancestors as $node) {
                    $text = self::node_text($node);
                    if ($text === '') {
                        continue;
                    }
                    $key = $node->key ?? '';
                    if (in_array($key, ['root', 'frontpage', 'mycourses', 'course'], true)) {
                        continue;
                    }
                    $items[] = ['text' => $text, 'url' => self::node_url($node)];
                }
            }
        }

        if ($items === [] && \theme_boost3_page_uses_participants_actionbar($page)) {
            $items[] = [
                'text' => get_string('participants'),
                'url' => new moodle_url('/user/index.php', ['id' => $page->course->id]),
            ];
        }

        return $items;
    }

    /**
     * @param moodle_page $page
     * @param array<int, array{text: string, url: ?moodle_url}> $items
     * @return void
     */
    /**
     * Replace navbar contents with a full M3-style chain.
     *
     * @param moodle_page $page
     * @param array<int, array{text: string, url: ?moodle_url}> $items
     * @return void
     */
    protected static function replace_navbar(moodle_page $page, array $items): void {
        $navbar = $page->navbar;
        if (!empty($navbar->children)) {
            foreach (array_values($navbar->children) as $child) {
                if ($child instanceof navigation_node) {
                    $child->remove();
                }
            }
        }
        foreach ($items as $item) {
            if (!empty($item['url']) && $item['url'] instanceof moodle_url) {
                $navbar->add($item['text'], $item['url']);
            } else {
                $navbar->add($item['text']);
            }
        }
    }

    /**
     * @param moodle_page $page
     * @return array<int, navigation_node>
     */
    protected static function collect_active_secondary_path(moodle_page $page): array {
        $activenode = $page->secondarynav->find_active_node();
        if (!$activenode instanceof navigation_node) {
            foreach ($page->secondarynav->children as $child) {
                if (is_object($child) && !empty($child->isactive)) {
                    $activenode = $child;
                    break;
                }
            }
        }
        if (!$activenode instanceof navigation_node) {
            return [];
        }

        $path = [];
        $node = $activenode;
        while ($node instanceof navigation_node) {
            array_unshift($path, $node);
            $parent = $node->parent ?? null;
            if (!$parent instanceof navigation_node || ($parent->key ?? '') === 'root') {
                break;
            }
            $node = $parent;
        }

        return $path;
    }

    /**
     * @param navigation_node $activenode
     * @return array<int, navigation_node>
     */
    protected static function collect_settings_ancestors(navigation_node $activenode): array {
        $path = [];
        $node = $activenode;
        while ($node instanceof navigation_node) {
            array_unshift($path, $node);
            $parent = $node->parent ?? null;
            if (!$parent instanceof navigation_node || ($parent->key ?? '') === 'root') {
                break;
            }
            $node = $parent;
        }
        return $path;
    }

    /**
     * @param navigation_node $node
     * @return string
     */
    protected static function node_text(navigation_node $node): string {
        $raw = $node->text;
        if ($raw instanceof \lang_string) {
            return trim(strip_tags($raw->out()));
        }
        return trim(strip_tags((string) $raw));
    }

    /**
     * @param navigation_node $node
     * @return moodle_url|null
     */
    protected static function node_url(navigation_node $node): ?moodle_url {
        $action = $node->action();
        if ($action instanceof moodle_url) {
            return $action;
        }
        if (is_string($action) && $action !== '' && $action !== '#') {
            return new moodle_url($action);
        }
        return null;
    }

    /**
     * @param moodle_page $page
     * @return bool
     */
    protected static function navbar_has_legacy_prefix(moodle_page $page): bool {
        $children = $page->navbar->children ?? [];
        if (!is_array($children) || $children === []) {
            return false;
        }
        $first = reset($children);
        if (!$first instanceof navigation_node) {
            return false;
        }
        $action = $first->action();
        if (!$action instanceof moodle_url) {
            return false;
        }
        return $action->compare(new moodle_url('/my/'), URL_MATCH_BASE);
    }
}
