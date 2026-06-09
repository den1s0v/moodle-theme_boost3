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
 * Unified active-state resolver for legacy drawer and gear menu items.
 *
 * Priority: navigation node isactive flag, then normalized URL match,
 * then context-specific rules (course home vs section vs my courses).
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost3;

defined('MOODLE_INTERNAL') || die();

/**
 * Active state resolver for navigation items.
 */
class active_state_resolver {
    /** @var \moodle_page */
    private $page;

    /**
     * @param \moodle_page $page
     */
    public function __construct(\moodle_page $page) {
        $this->page = $page;
    }

    /**
     * Standard nav node active: node flag first, then URL match.
     *
     * @param bool $nodeactive
     * @param string|null $url
     * @return bool
     */
    public function from_nav_node(bool $nodeactive, ?string $url): bool {
        if ($nodeactive) {
            return true;
        }
        if ($url === null || $url === '') {
            return false;
        }
        return $this->url_matches_current($url);
    }

    /**
     * Whether the course title row in the drawer should be active.
     *
     * Suppressed when a section is selected on the course home page.
     *
     * @return bool
     */
    public function course_home(): bool {
        if (!theme_boost3_page_is_course_format_view($this->page)) {
            return false;
        }
        return !$this->has_selected_section();
    }

    /**
     * Active state for an item in the "My courses" drawer block.
     *
     * @param bool $nodeactive
     * @param string $url
     * @return bool
     */
    public function mycourses_item(bool $nodeactive, string $url): bool {
        if (theme_boost3_page_is_course_format_view($this->page)) {
            return false;
        }
        if ($nodeactive) {
            return true;
        }
        if (!theme_boost3_page_is_course_scoped_page($this->page)) {
            return false;
        }
        return $this->course_view_url_matches_current_course($url);
    }

    /**
     * Active state for a course section link.
     *
     * @param int $sectionnum
     * @param string $sectionurl
     * @param string $linkmode sectionpage|anchor
     * @return bool
     */
    public function section(int $sectionnum, string $sectionurl, string $linkmode): bool {
        if ($linkmode === 'anchor') {
            if (!theme_boost3_page_is_course_format_view($this->page)) {
                return false;
            }
            $viewsection = optional_param('section', null, PARAM_INT);
            if ($viewsection !== null && (int) $viewsection === $sectionnum) {
                return true;
            }
            if ($this->page->url instanceof \moodle_url) {
                $anchor = $this->page->url->get_anchor();
                if ($anchor === 'section-' . $sectionnum) {
                    return true;
                }
            }
            return false;
        }

        if ($this->url_matches_current($sectionurl)) {
            return true;
        }

        if (theme_boost3_page_is_course_format_view($this->page)) {
            $viewsection = optional_param('section', null, PARAM_INT);
            return $viewsection !== null && (int) $viewsection === $sectionnum;
        }

        return false;
    }

    /**
     * Whether a section is actively selected on the course format view.
     *
     * @return bool
     */
    public function has_selected_section(): bool {
        if (!theme_boost3_page_is_course_format_view($this->page)) {
            return false;
        }

        $viewsection = optional_param('section', null, PARAM_INT);
        if ($viewsection !== null && (int) $viewsection > 0) {
            return true;
        }

        if ($this->page->url instanceof \moodle_url) {
            $anchor = $this->page->url->get_anchor();
            if ($anchor !== '' && preg_match('/^section-\d+$/', $anchor)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a URL matches the current page (base path and params, no fragment).
     *
     * @param string $url
     * @return bool
     */
    public function url_matches_current(string $url): bool {
        if (!$this->page->url instanceof \moodle_url) {
            return false;
        }

        try {
            $target = new \moodle_url($url);
            return $target->compare($this->page->url, URL_MATCH_BASE);
        } catch (\moodle_exception $e) {
            return false;
        }
    }

    /**
     * Whether a mycourses link points at the current course view page.
     *
     * @param string $url
     * @return bool
     */
    protected function course_view_url_matches_current_course(string $url): bool {
        try {
            $target = new \moodle_url($url);
            if ($target->get_path(false) !== '/course/view.php') {
                return false;
            }
            $targetid = (int) $target->get_param('id');
            $currentid = (int) ($this->page->course->id ?? 0);
            return $targetid > 0 && $targetid === $currentid;
        } catch (\moodle_exception $e) {
            return false;
        }
    }
}
