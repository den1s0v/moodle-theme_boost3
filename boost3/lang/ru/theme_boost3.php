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
 * Language strings for theme_boost3.
 *
 * @package    theme_boost3
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Boost3 (дочерняя тема Boost Union)';
$string['gearmenu'] = 'Меню курса';
$string['gearmenudesc'] = 'Действия курса и страницы (как старое меню-шестерёнка)';
$string['enablegearmenu'] = 'Меню-шестерёнка (как в Moodle 3.9)';
$string['enablegearmenu_desc'] = 'Если включено, вкладки курса и подразделы собираются в меню-шестерёнку. Если выключено, используется стандартная горизонтальная навигация Boost и выпадающий tertiary. После смены настройки очистите кэши.';
$string['enablelegacydrawer'] = 'Левая панель навигации (как в Moodle 3.9)';
$string['enablelegacydrawer_desc'] = 'Если включено, левая панель показывает плоский список ссылок курса, сайта и «Мои курсы» вместо дерева активностей. Горизонтальные вкладки курса переносятся в панель. После смены настройки очистите кэши.';
$string['legacydrawernav'] = 'Навигация по курсу и сайту';
$string['legacydrawersite'] = 'Сайт';
$string['legacydrawertoggleopen'] = 'Открыть панель навигации';
$string['legacydrawertoggleclose'] = 'Закрыть панель навигации';
$string['legacydrawersitekeys'] = 'Сайтовые ссылки в legacy drawer';
$string['legacydrawersitekeys_desc'] = 'Один ключ узла global_navigation на строку (или через запятую). Показываются только узлы, существующие на текущей странице; неизвестные ключи пропускаются. Примеры: home, contentbank. Личный кабинет, календарь и личные файлы в Moodle 4.5 находятся в верхней navbar и сюда не добавляются.';
$string['legacydrawersectionlinks'] = 'Ссылки на разделы курса в legacy drawer';
$string['legacydrawersectionlinks_desc'] = 'Куда ведут названия разделов в левой панели. Страница раздела — отдельный URL Moodle 4.5. Якоря — прокрутка на главной странице курса, как в Moodle 3.9 (форматы topics/weeks).';
$string['legacydrawersectionlinks_sectionpage'] = 'Страница раздела (/course/section.php)';
$string['legacydrawersectionlinks_anchor'] = 'Якоря на главной курса (#section-N)';
