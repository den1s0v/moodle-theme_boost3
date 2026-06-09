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
 * Sync legacy drawer hamburger aria/title and prevent drawer displace() scroll drift.
 *
 * @module     theme_boost3/legacy_drawer_toggle
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Initialise legacy drawer toggle behaviour.
 */
export const init = () => {
    const btn = document.querySelector('.theme-boost3-legacy-nav-toggle');
    const drawer = document.getElementById('theme_boost-drawers-courseindex');
    if (!btn || !drawer) {
        return;
    }

    const syncToggle = (isOpen) => {
        const title = isOpen ? btn.dataset.boost3DrawerTitleClose : btn.dataset.boost3DrawerTitleOpen;
        if (!title) {
            return;
        }
        btn.setAttribute('title', title);
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        const sr = btn.querySelector('.visually-hidden, .sr-only');
        if (sr) {
            sr.textContent = title;
        }
    };

    drawer.addEventListener('theme_boost/drawers:shown', () => syncToggle(true));
    drawer.addEventListener('theme_boost/drawers:hidden', () => syncToggle(false));

    const resetNavToggleTransform = () => {
        btn.style.transform = '';
    };
    window.addEventListener('scroll', resetNavToggleTransform, {passive: true});
    document.addEventListener('scroll', resetNavToggleTransform, {passive: true});
};
