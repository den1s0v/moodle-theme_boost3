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
 * Breadcrumb navbar with Moodle 3.9 rules when legacy drawer is active.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

/**
 * Extends Boost Union navbar filtering for legacy-drawer course UX (Moodle 3.9 parity).
 */
class boostnavbar extends \theme_boost_union\boostnavbar {

    /**
     * @return bool
     */
    protected function should_use_legacy_m39_breadcrumbs(): bool {
        return navigation_policy::breadcrumbs_mode_for_page($this->page)
            === navigation_channel_profile::BREADCRUMBS_LEGACY_M39;
    }

    /**
     * @return bool
     */
    protected function should_use_core_union_breadcrumbs(): bool {
        return navigation_policy::breadcrumbs_mode_for_page($this->page)
            === navigation_channel_profile::BREADCRUMBS_CORE_UNION;
    }

    /**
     * @return void
     */
    protected function prepare_nodes_for_boost(): void {
        if ($this->should_use_legacy_m39_breadcrumbs()) {
            breadcrumb_builder::populate($this->page);
            $this->sync_items_from_page_navbar();
            $this->prepare_nodes_for_legacy_m39();
            return;
        }

        if ($this->should_use_core_union_breadcrumbs()) {
            $this->prepare_nodes_for_core_union();
            return;
        }

        parent::prepare_nodes_for_boost();
    }

    /**
     * Core/Union chain for course/edit.php — do not call Union parent (it strips courses/course crumbs).
     *
     * @return void
     */
    protected function prepare_nodes_for_core_union(): void {
        breadcrumb_builder::populate_course_admin_union($this->page);
        $this->sync_items_from_page_navbar();
        $this->remove_duplicate_items();
        $this->remove_last_item_action();
    }

    /**
     * Re-copy navbar items built after boostnavbar construction (e.g. breadcrumb_builder).
     *
     * @return void
     */
    protected function sync_items_from_page_navbar(): void {
        $this->items = [];
        foreach ($this->page->navbar->get_items() as $item) {
            $this->items[] = $item;
        }
    }

    /**
     * Keep breadcrumb chain like Moodle 3.9 — do not strip course/secondary crumbs.
     *
     * @return void
     */
    protected function prepare_nodes_for_legacy_m39(): void {
        $this->remove_duplicate_items();

        $mycoursesnode = $this->get_item('mycourses');
        if (!is_null($mycoursesnode)) {
            $mycoursesnode->text = get_string('mycourses');
            // Moodle 3.9: «Мои курсы» — подпись без ссылки (не /my/courses.php).
            $mycoursesnode->action = null;
        }

        if ($this->page->context->contextlevel == CONTEXT_MODULE && $this->page->cm) {
            $coursenode = $this->get_item($this->page->course->id, \breadcrumb_navigation_node::TYPE_COURSE);
            if (!is_null($coursenode) && $this->page->cm->sectionnum !== null) {
                $courseformat = course_get_format($this->page->course);
                $coursenode->action = $courseformat->get_view_url($this->page->cm->sectionnum);
            }
        }

        // Intentionally skip Union/Boost removals: primarynav, secondarynav, course node, mycourses,
        // remove_no_link_items, single-item clear, remove_last_item_action.
    }
}
