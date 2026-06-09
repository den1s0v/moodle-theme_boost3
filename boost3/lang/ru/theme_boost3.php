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
$string['enablegearmenu_desc'] = 'Если включено, вкладки курса и подразделы собираются в меню-шестерёнку. Если выключено, используется стандартная горизонтальная навигация Boost и выпадающий tertiary — кроме страниц с legacy drawer: вкладки, не попавшие в панель, автоматически показываются в шестерёнке, чтобы ничего не пропало. После смены настройки очистите кэши.';
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
$string['legacydrawercoursekeys'] = 'Вкладки курса в legacy drawer';
$string['legacydrawercoursekeys_desc'] = 'Ключи вкладок secondary navigation (по одному на строку) для левой панели между названием курса и списком разделов — как в Moodle 3.9. Остальные вкладки курса попадают в меню-шестерёнку (если оно включено). Используйте ключи узлов secondary navigation (каталог ниже). Неизвестные ключи пропускаются.';
$string['legacydrawercoursekeys_catalog'] = 'Частые ключи secondary navigation (ядро и плагины):
editsettings — Настройки курса
participants — Участники
competencies — Компетенции (tool_lp)
grades — Оценки
questionbank — Банк вопросов
coursereports — Отчёты
coursecompletion — Завершение курса
badges — Значки
contentbank — Банк контента (вкладка курса)
filtermanagement — Фильтры
coursetools — LTI / внешние инструменты
backup — Резервное копирование
coursehome — Главная курса (обычно не нужен: название курса уже в панели)
Плагины могут добавлять свои ключи — при необходимости смотрите $PAGE->secondarynav на вашем сайте.';
$string['legacydrawercoursekeyaliases'] = 'Алиасы ключей вкладок курса';
$string['legacydrawercoursekeyaliases_desc'] = 'Соответствие настроенных ключей drawer альтернативным ключам secondary navigation на вашем сайте. Одна строка на канонический ключ: canonical=alias1,alias2. Объединяется со встроенными значениями по умолчанию.';
$string['legacydrawersectionformats'] = 'Форматы курса со списком разделов';
$string['legacydrawersectionformats_desc'] = 'Имена форматов курса через запятую или пробел (например topics, weeks), для которых в legacy drawer показываются плоские ссылки на разделы. Для остальных форматов — только вкладки курса.';
$string['legacydrawergearexcludedkeys'] = 'Исключённые ключи для gear overflow';
$string['legacydrawergearexcludedkeys_desc'] = 'Ключи secondary navigation, для которых поддеревья settings navigation не используются как источник overflow в шестерёнке (через запятую или пробел). По умолчанию: coursehome, questionbank, coursereports.';
