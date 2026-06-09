# План: левая панель drawer в стиле Moodle 3.9 для theme_boost3 (Moodle 4.5)

Документ подготовлен по результатам браузерного исследования боевого Moodle 3.9 (`eos2.vstu.ru`) и тестового Moodle 4.5 (`m45.umu.vstu.ru`, тема Boost3) в сессии 2026-06-09. Предназначен для обсуждения; реализация — после утверждения.

---

## 1. Цель

Вернуть для пользователей привычную **левую боковую панель** как на Moodle 3.9+Boost:

- единый вертикальный список навигации (курс, участники, оценки, разделы, сайт, «Мои курсы»);
- переключатель в **navbar** (гамбургер «Боковая панель»);
- состояние открыта/закрыта **сохраняется в User Preferences** и пересохраняется при toggle через async API (`core_user/repository` → `setUserPreference`).

При этом сохранить уже реализованное в Boost3 **меню-шестерёнку** для горизонтальных secondary/tertiary вкладок — согласовать оба механизма, чтобы не дублировать ссылки.

---

## 2. Что есть на Moodle 3.9 (эталон)

Исследование: аккаунт преподавателя, тема Boost, курс `id=1472`, dashboard `/my/`.

### 2.1. DOM и поведение

| Аспект | Значение |
|--------|----------|
| Контейнер | `#nav-drawer`, `data-region="drawer"` |
| Класс body при открытии | `drawer-open-left` |
| Переключатель | Кнопка в **navbar**: «Боковая панель», `data-action="toggle-drawer"`, `data-side="left"` |
| User preference | `drawer-open-nav` (на кнопке: `data-preference="drawer-open-nav"`) |
| Горизонтальные вкладки | **Отсутствуют** (нет `.secondary-navigation`) |

### 2.2. Содержимое панели

На **главной курса** (плоский `list-group`, без древовидного course index):

1. **Блок курса** — заголовок курса (active), Участники, Компетенции, Оценки, разделы курса (якоря `#section-N`).
2. **Блок «Сайт»** — Личный кабинет, Домашняя страница, Календарь, Личные файлы, Банк контента (в контексте курса).
3. **«Мои курсы»** — заголовок-разделитель + плоский список всех курсов пользователя.

На **dashboard** (`/my/`): плоский список **без заголовка «Сайт»** — Личный кабинет (active), Домашняя страница, Календарь, Личные файлы; затем разделитель **«Мои курсы»** (не ссылка) + список курсов. Банк контента на dashboard **обычно нет**.

На **календаре**, **личных файлах**, **домашней** и прочих страницах **вне курса** — тот же набор «сайтовых» пунктов + «Мои курсы», активный пункт подсвечен. Блока курса нет.

На **главной курса** — сначала блок курса (название, Участники, …, разделы), затем **без заголовка «Сайт»** те же сайтовые ссылки (включая Банк контента), затем «Мои курсы».

На **сайтовой админке** (`/admin/index.php`, eos2): drawer **присутствует** и содержит **тот же** сайт + «Мои курсы» (не дерево настроек админки). Исключения — узкие «системные» экраны (обновление, установка плагинов и т.п.; у преподавателя без прав не воспроизведено).

На **Участники** (`/user/index.php`): в drawer те же пункты курса/сайта; **tertiary** (Зачисленные, Группы и т.д.) — в контенте страницы, не в drawer.

### 2.3. Поведение toggle

- По умолчанию панель **открыта** (`drawer-open-left` на body).
- Закрытие убирает класс с body, сдвигает контент; preference сохраняется (legacy Boost 3.x JS, не `theme_boost/drawers` 4.x).

### 2.4. Активный пункт и ссылки на разделы (визуал + UX)

**Активный пункт (eos2):** выбранная ссылка **заливается** primary-цветом (синий фон, светлый текст) — класс `list-group-item active`. Примеры:

- на главной курса — **подсвечено имя курса** (первый пункт блока курса);
- на «Участники» — подсвечен пункт «Участники»;
- на dashboard — «Личный кабинет» и т.д.

Это сильно облегчает ориентацию «где я сейчас».

**Разделы курса (eos2):** ссылки ведут на **якорь** общей страницы курса: `/course/view.php?id={courseid}#section-{N}` — прокрутка к секции **без** отдельной страницы `course/section.php` (если уже на курсе — без полной перезагрузки при клике по hash).

---

## 3. Что есть на Moodle 4.5 сейчас (тест, Boost3)

Исследование: аккаунт `juliet`, активная тема **Boost3**; настройка `enablegearmenu` — **выключена** (gear не отображается).

### 3.1. Левая панель 4.x (course index drawer)

| Аспект | Значение |
|--------|----------|
| Контейнер | `#theme_boost-drawers-courseindex` |
| User preference | `drawer-open-index` (default `true` в `layout/drawers.php`) |
| Классы | body: `drawer-open-index`; `#page`: `show-drawer-left` при открытии |
| JS | `theme_boost/drawer` → `setUserPreference('drawer-open-index', true/false)` |
| Переключатель | В **области контента** (не в navbar): «Открыть/Закрыть оглавление курса» |
| Содержимое | **Только древовидное оглавление курса** (разделы → элементы), `role="tree"` |

**Нет** в левом drawer: участников/оценок как отдельных пунктов, навигации по сайту, списка «Мои курсы».

### 3.2. Куда переехала навигация в 4.5

- **Primary navigation** — горизонтально в navbar (В начало, Личный кабинет, Мои курсы, …).
- **Secondary navigation** — горизонтальные вкладки курса (Курс, Настройки, Участники, Оценки, …).
- **Tertiary** — dropdown / action bar на подстраницах.

### 3.3. Уже реализовано в theme_boost3 (gear)

При `enablegearmenu=1`:

- вкладки курса и tertiary уходят в шестерёнку;
- на подразделах Participants — полное tertiary из `participants_action_bar`;
- `#action_bar` скрывается при дублировании.

**Это не заменяет** левый drawer 3.9 — решает другую ось UX (горизонтальные tabs → gear).

---

## 4. Boost Union: встроенные настройки (оценка применимости)

Просмотрены настройки Boost Union на тестовом сайте и README v4.5.

### 4.1. Полезны, но недостаточны

| Настройка | Что даёт | Почему не решает задачу |
|-----------|----------|------------------------|
| `courseindexdrawerwidth` | Ширина оглавления курса | Только course index tree, не flat nav 3.9 |
| `blockdrawerwidth` | Ширина правого drawer блоков | Другая панель |
| `courseindexmodiconenabled` | Иконки модулей в course index | Косметика tree-view |
| `hidenodesprimarynavigation` | Скрыть узлы primary nav | Не переносит их в левый drawer |
| Smart menus | Кастомные пункты в primary / bottom bar / user menu | Горизонтальные/мобильные зоны, не левый flat drawer |
| `activitynavigation` | Prev/next activity + **отключает course index** (`usescourseindex=false` в `drawers.php`) | Конфликтует с левым drawer; на тесте = **Нет** |
| Site home right block drawer | Правый drawer на главной для гостей/первого входа | Не левая навигация |
| Off-canvas block region | Верхний overlay-drawer (9 точек) | Другое назначение |

### 4.2. Вывод по Boost Union

**Полностью обойтись настройками Boost Union нельзя.** Нужна доработка `theme_boost3`: кастомный контент и/или замена левого drawer, плюс toggle в navbar и согласование с gear.

---

## 5. Плагины LK: расширение левого меню (справочно, вне scope темы)

> **Scope:** `local_coursesets` и `block_lk` **не разрабатываем и не портим** в рамках `theme_boost3` (бета, не на боевом eos2). Ниже — анализ use-case для **опциональной совместимости**: если плагин когда-либо установят, drawer должен подхватить его узлы из `global_navigation` автоматически.

На Moodle 3.9 (eos2, в перспективе) левый drawer может дополнительно модифицироваться плагинами. Исходники:

| Репозиторий | Компоненты | Роль в левом меню |
|-------------|------------|-------------------|
| `moodle-block_lk` | `block_lk` | ЛК-инструменты (зачётка, поиск групп); **без** замены навигации курсов |
| `moodle-plugins-lk-coursesets` | `local_coursesets`, `block_lk` (расширенный), патч `block_myoverview` | **Замена/расширение** секции «Мои курсы» в flat navigation |

### 5.1. `local_coursesets` — основной механизм (Moodle 3.9)

Колбэк стандартного API плагинов:

```php
function local_coursesets_extend_navigation(global_navigation $nav)
```

Файл: `local/coursesets/lib.php`.

**Настройки** (`local/coursesets`):

| Параметр | Назначение |
|----------|------------|
| `replacecoursenav` | Режим: `all` / `coursesets` / `link` / `none` |
| `maxcollections` | Лимит подборок в меню (1–30, по умолчанию 10) |
| `maxcourses` | Лимит курсов внутри подборки в меню (1–30, по умолчанию 10) |

**Логика (режим `all`, типичный для eos2):**

1. `$nav->clear_cache()`.
2. Если у пользователя есть подборки и режим `all` или `coursesets` — **удаляется** стандартный узел `mycourses` (`global_navigation::TYPE_ROOTNODE`).
3. Создаётся корневой узел `course_collections` («Мои подборки») с `showinflatnavigation = true`.
4. В меню попадают первые `maxcollections` подборок (с учётом `visible_in_navigation` в записи подборки).
5. При превышении лимита — ссылка **«N других коллекций»** → `/blocks/lk/coursesets/view.php`.
6. В режиме `all` — внутри каждой подборки первые `maxcourses` курсов; при превышении — **«N других курсов»** → страница подборки.
7. У всех добавленных узлов: `showinflatnavigation = true` (ключевое для отображения в flat drawer 3.9).

Режимы `coursesets` (только подборки без вложенных курсов), `link` (одна ссылка + стандартные «Мои курсы»), `none` — документированы в lang-строках плагина.

### 5.2. `block_lk` в bundle coursesets

Дополнительный колбэк `block_lk_extend_navigation()` в `blocks/lk/db/navigation.php` — добавляет узел «Мои подборки» и ссылки на коллекции, если они есть.

**Замечание:** при активном `local_coursesets` с режимом `all`/`coursesets` возможно **частичное дублирование** с `block_lk_extend_navigation`; на eos2, вероятно, используется в основном `local_coursesets`. При портировании — проверить, какой колбэк реально нужен, и не дублировать узлы.

Блок `block_lk` на dashboard (`viewcollectionsintools`) рендерит подборки в **боковом блоке** «Личные инструменты» с лимитом 10 курсов в dropdown + «показать все» — это **отдельный UI**, не левый drawer.

### 5.3. Патч `block_myoverview`

В `blocks/myoverview/classes/output/main.php` добавлена группировка по подборкам (`get_collections_for_export()` → `local_coursesets_get_user_collections`). Влияет на **блок «Обзор курсов»** на `/my/`, **не** на левый drawer. Порт на 4.5 — отдельная задача, к legacy drawer не привязана напрямую.

### 5.4. Как это попадало в drawer на Moodle 3.9

1. Core строит `global_navigation` и вызывает все `*_extend_navigation` колбэки плагинов.
2. Boost 3.x ренерит в `#nav-drawer` узлы с `showinflatnavigation = true` (плоский `list-group`).
3. `local_coursesets` подменяет дерево «Мои курсы» на подборки + усечённые списки + ссылки «more…».

На eos2 у преподавателя в drawer виден длинный плоский список курсов/подборок — именно этот механизм.

### 5.5. Что изменилось в Moodle 4.5 (важно для совместимости)

| Аспект | Moodle 3.9 | Moodle 4.5 |
|--------|------------|------------|
| Колбэк `*_extend_navigation` | Вызывается, узлы попадают в flat drawer | **По-прежнему вызывается** при построении `global_navigation` |
| `showinflatnavigation` | Отображение в `#nav-drawer` | **Само по себе ничего не рисует** — flat drawer убран из Boost |
| Primary nav «Мои курсы» | Частично дублирует drawer | Отдельный hardcoded компонент, **не** читает `extend_navigation` |
| Левый drawer 4.5 | — | Только **course index tree**, не `global_navigation` |

Вывод: плагины **продолжают модифицировать дерево `global_navigation`**, но в 4.5 **некому это отрисовать** в левой панели — отсюда «пропажа» подборок после миграции, даже без поломки колбэков.

### 5.6. Можно ли сохранить совместимость **без переписывания core Moodle**?

**Да.** Core менять не нужно. Достаточно:

1. **theme_boost3** в режиме legacy drawer **читает уже построенное** `$PAGE->navigation` (`global_navigation`) и ренерит плоский список по тому же принципу, что flat navigation 3.9 (обход дерева / `build_flat_navigation_list()`).
2. Колбэки `local_coursesets_extend_navigation` и `block_lk_extend_navigation` **срабатывают автоматически** при инициализации navigation — **логику плагинов переписывать не обязательно**.
3. Плагины нужно **портировать на Moodle 4.5** (version.php, тесты API), но не менять архитектуру хуков.

**Переписывание core не требуется.** Доработки — только в **теме**; плагины LK в этот проект не входят.

### 5.7. Критическое требование к реализации drawer в теме

Секция **«Мои курсы»** в legacy drawer **не должна** собираться напрямую через `enrol_get_my_courses()` — иначе:

- обойдётся логика `local_coursesets` (подборки, лимиты, «more…»);
- появится **дубль** со стандартным списком вместо подборок;
- настройки `replacecoursenav`, `maxcollections`, `maxcourses` перестанут работать.

**Правильный источник:** поддерево `global_navigation` — узлы `mycourses` **или** `course_collections` (после отработки `local_coursesets`), включая дочерние узлы и ссылки «N других коллекций» / «N других курсов».

Псевдокод в renderer темы:

```php
// После полной инициализации $PAGE->navigation (extend_navigation уже отработали).
$root = $PAGE->navigation->find('course_collections', navigation_node::TYPE_ROOTNODE)
     ?? $PAGE->navigation->find('mycourses', navigation_node::TYPE_ROOTNODE);
$items = theme_boost3_flatten_navigation_branch($root);
```

### 5.8. Риски при опциональной установке плагинов (для темы)

1. **Порядок инициализации** — drawer renderer должен читать navigation **после** построения дерева (обычно к моменту layout это уже так; при проблемах — явный `$PAGE->navigation->initialise()` / аналог в layout).
2. **`$nav->clear_cache()`** в `local_coursesets` — побочный эффект при каждом extend; на 4.5 поведение нужно проверить нагрузочно.
3. **Дубли** — если тема добавит свой список курсов поверх navigation tree.
4. **Режим `link`** — в drawer должны остаться и `course_collections`, и стандартный `mycourses` (как задумано в плагине).
5. **Пользователи без подборок** — fallback на стандартный `mycourses` (плагин сам не удаляет узел, если коллекций нет).

### 5.9. Вывод для плана

Базовый сценарий — **без** `local_coursesets`: стандартный узел `mycourses` из core. Если плагин установлен позже — те же `extend_navigation`-колбэки отработают, тема подхватит дерево без изменений. Главное правило для темы: **не** собирать «Мои курсы» через `enrol_get_my_courses()`.

---

## 6. Разрыв (gap) между 3.9 и 4.5

```
Moodle 3.9                          Moodle 4.5 (сейчас)
─────────────────────────────────────────────────────────
#nav-drawer (flat list)      →     #theme_boost-drawers-courseindex (tree)
settingsnav + global nav     →     primary + secondary (horizontal)
sections в drawer            →     sections в course index tree
«Мои курсы» в drawer         →     «Мои курсы» в primary nav
toggle в navbar              →     toggle в content area
drawer-open-nav              →     drawer-open-index
drawer-open-left (body)      →     drawer-open-index + show-drawer-left (#page)
```

---

## 7. Предлагаемая концепция для theme_boost3

### Утверждённые решения

| Тема | Решение |
|------|---------|
| Preference / миграция с 3.9 | **Не нужна.** Используем `drawer-open-index`; default **открыта** (`true` в `layout/drawers.php` — уже так). |
| §7.3 Gear + drawer | **Вариант A** — приоритет UX как на 3.9: secondary в drawer; gear — tertiary на подразделах. |
| Скрытие horizontal secondary | **Одна общая функция** в `lib.php` (не дублировать условия gear и legacy по шаблонам). |
| §7.4 Course index | **Полный activity tree убрать**; только плоские ссылки на разделы/секции. |
| §7.5 Toggle / JS | **Переиспользовать** `#theme_boost-drawers-courseindex` + `theme_boost/drawer`; отдельный AMD не писать. |

### 7.1. Режим «Legacy left drawer» (новая настройка)

Добавить в `settings.php`:

- **`enablelegacydrawer`** (checkbox, **default: off** — утверждено §14.7).
- **`legacydrawersitekeys`** (textarea, default: `home`, `contentbank` — §14.8; этап 6).
- **`legacydrawersectionlinks`** (select: `sectionpage` | `anchor`, default `sectionpage` — §15.2).

При включении:

1. **Заменить** содержимое левого drawer (`#theme_boost-drawers-courseindex`) с course index tree на **плоскую навигацию 3.9**.
2. Перенести **toggle в navbar** (как на 3.9), скрыть дублирующий toggle в `drawer-toggles`.
3. Состояние open/close — существующий механизм M4.5:
   - preference **`drawer-open-index`** (default `true` = открыта при первом визите);
   - async save через `theme_boost/drawer` → `setUserPreference`;
   - миграция `drawer-open-nav` (3.9) **не делается**.

### 7.2. Источники данных для flat drawer

Собрать плоский список `<a class="list-group-item">` из core navigation API:

| Секция drawer | Источник в M4.5 | Примечание |
|---------------|-----------------|------------|
| Курс (home, participants, grades, …) | `$PAGE->secondarynav` и/или `$PAGE->settingsnav` | Аналог вкладок, которые сейчас в gear |
| Разделы курса | `$PAGE->settingsnav` / format API | **Только секции** (плоские ссылки), без activity tree (§7.4) |
| Сайт | `$PAGE->navigation` (global_navigation), узлы dashboard/home/calendar/… | Не primary navbar |
| **Мои курсы** | **`$PAGE->navigation` → `mycourses`** (или `course_collections`, если установлен `local_coursesets`) | Через navigation tree, не `enrol_get_my_courses()`; §5 |

Рендер: новый Mustache `theme_boost3/legacy_nav_drawer.mustache` + метод в `core_renderer.php` (по аналогии с `gear_menu`).

### 7.3. Взаимодействие с gear menu — **утверждено: вариант A**

Цель: максимальная похожесть на Moodle 3.9.

| Контекст | Горизонтальные secondary tabs | Левый drawer | Gear |
|----------|------------------------------|--------------|------|
| Course home, legacy drawer вкл. | **Скрыты** | Курс, Участники, Оценки, разделы, Сайт, Мои курсы | Не нужен (нет tertiary) |
| Подраздел (Participants, LTI, …), оба вкл. | **Скрыты** | Те же пункты курса/сайта | **Tertiary** (как сейчас) |
| Только gear, legacy выкл. | По текущей логике gear | Стандартный course index 4.5 | Как сейчас |
| Admin (сайтовая) | Скрыты / стандарт | На 3.9 — **сайт + «Мои курсы»** в drawer; на 4.5/MVP — **нет** (см. §14) | Скрыт |

**Унификация скрытия secondary tabs** (упрощение кода в `lib.php`):

Ввести одну функцию, например `theme_boost3_should_show_secondary_tabs($PAGE)` → `bool`, вместо разрозненных флагов в Mustache. Логика OR (достаточно любого основания скрыть горизонтальные вкладки):

```text
show_secondary_tabs =
    has_secondary_navigation
    AND NOT is_admin_page
    AND NOT legacy_drawer_hides_secondary   // enablelegacydrawer + страница с course nav в drawer
    AND NOT gear_hides_secondary            // существующая логика gear (overflow / inline gear / …)
```

`theme_boost3_append_drawer_nav_flags()` и шаблон `drawers.mustache` читают **один** итоговый флаг `boost3_show_secondary_tabs`. Условия gear и legacy остаются в PHP, не дублируются в Mustache.

Gear и legacy drawer **совместимы** одновременно; взаимоисключение в settings не вводим.

### 7.4. Course index — **утверждено: без activity tree**

При `enablelegacydrawer`:

- **Не рендерить** `core_course_drawer()` / course index tree (`$courseindex` пустой или не передаётся).
- В flat drawer — **только ссылки на разделы курса** (как на 3.9: `#section-N` или `course/section.php`), без вложенных элементов/активностей.
- Отдельной настройки «показать tree» **нет**.

### 7.5. Navbar toggle — **утверждено: reuse core drawer**

Правка `templates/theme_boost/navbar.mustache`:

- Кнопка «Боковая панель» / `fa-bars` с `data-toggler="drawers"`, `data-target="theme_boost-drawers-courseindex"`.
- `aria-expanded`, `aria-controls="theme_boost-drawers-courseindex"`.
- **Новый drawer id и отдельный JS не создаём** — меняется только `{{$drawercontent}}` внутри существующего partial `theme_boost/drawer`.
- Скрыть кнопку toggle в `drawer-toggles` (content area), когда legacy drawer активен — чтобы не было двух переключателей.

### 7.6. SCSS

`scss/post.scss`:

- Стили `list-group-item` / **active state как на 3.9** — сплошная заливка primary (§15.1), не бледный highlight;
- Заголовки секций («Сайт», «Мои курсы») — non-clickable `list-group-item`;
- Ширина: наследовать `courseindexdrawerwidth` из Boost Union;
- body `drawer-open-index` / `#page.show-drawer-left` — уже есть в Boost.

### 7.7. User preferences и async API

Цепочка (без изменений core, если reuse drawer):

1. PHP: `get_user_preferences('drawer-open-index', true)` — уже в `boost3/layout/drawers.php`.
2. Mustache drawer partial: `{{$drawerpreferencename}}drawer-open-index{{/drawerpreferencename}}` — уже есть.
3. JS `theme_boost/drawers`: при open/close → `setUserPreference('drawer-open-index', bool)`.
4. Убедиться, что `theme_boost_union_user_preferences()` / parent chain разрешает ajax update (наследуется от boost).

**Отдельный JS не нужен**, если не меняем имя preference.

---

## 8. План реализации (этапы)

### Этап 0 — подготовка (0.5 дн.)

- [ ] На тесте: `enablegearmenu` **включена**; включить `enablelegacydrawer` после реализации MVP.
- [ ] Зафиксировать скриншоты/чеклист: dashboard, course home (formats **topics**, **weeks**), participants, activity, admin.
- [ ] Без `local_coursesets` / `block_lk` на тестовом стенде (базовый сценарий).

### Этап 1 — MVP flat drawer (2–3 дн.)

- [ ] Настройка `enablelegacydrawer` + lang strings.
- [ ] `theme_boost3_build_legacy_drawer_items()` в renderer — сбор items; «Мои курсы» из `$PAGE->navigation` → `mycourses` (§5.7).
- [ ] Форматы курса: **topics** и **weeks**; прочие — без гарантий на этапе 1.
- [ ] Шаблон `legacy_nav_drawer.mustache`.
- [ ] Условная подмена `{{$drawercontent}}` в `drawers.mustache`.
- [ ] Отключение course index tree; в drawer — только flat section links (§7.4).
- [ ] Проверка preference open/close на course + dashboard.

### Этап 2 — Navbar toggle (1 дн.)

- [ ] Кнопка в `navbar.mustache`, скрытие content-area toggle.
- [ ] A11y: focus trap, `aria-expanded`.
- [ ] Mobile: поведение как core drawer (overlay на малых экранах).

### Этап 3 — Secondary tabs + gear (1–2 дн.)

- [ ] `theme_boost3_should_show_secondary_tabs()` — единая точка (legacy OR gear), флаги в `theme_boost3_append_drawer_nav_flags`.
- [ ] Убрать дубли ссылок drawer ↔ gear.
- [ ] Participants: drawer = course-level; gear = tertiary only (вариант A).

### Этап 4 — Полировка (1–2 дн.)

- [ ] SCSS: визуальное сближение с eos2 (§15.1 — active fill).
- [ ] Ссылки на разделы: настройка якорь / `section.php` (§15.2).
- [ ] Длинный список «Мои курсы» в drawer — scroll, без лагов.
- [ ] Гость / неавторизованный — drawer закрыт (как сейчас).
- [ ] Конфликт `activitynavigation` — документировать: при включении Union отключает course index; legacy drawer должен **принудительно** включать левую панель.

### Этап 5 — Тестирование

- [ ] Регрессия gear menu (вкл/выкл) и legacy drawer (вкл/выкл).
- [ ] Регрессия правого block drawer (`drawer-open-block`).
- [ ] Роли/capabilities: teacher, student, manager — пункты drawer соответствуют правам core navigation.
- [ ] Форматы **topics** и **weeks**; RTL, editing mode.
- [ ] Сравнение drawer с eos2 (визуально/по чеклисту); primary nav **не скрываем** (§10.1).
- [ ] *(Опционально, вне этапа 1)* smoke-тест с установленным `local_coursesets`, если появится на стенде.

---

## 9. Затрагиваемые файлы (прогноз)

| Файл | Изменения |
|------|-----------|
| `settings.php` | `enablelegacydrawer` |
| `lang/en|ru/theme_boost3.php` | строки настройки |
| `lib.php` | `theme_boost3_should_show_secondary_tabs()`, флаги template, gear + legacy |
| `layout/drawers.php` | условие courseindex, default open state |
| `classes/output/core_renderer.php` | `legacy_nav_drawer()`, сбор nodes |
| `templates/theme_boost/drawers.mustache` | ветка legacy content |
| `templates/theme_boost3/legacy_nav_drawer.mustache` | **новый** |
| `templates/theme_boost/navbar.mustache` | hamburger toggle |
| `scss/post.scss` | стили flat nav |

Возможно без форка Union: `activitynavigation` override в `drawers.php` при legacy mode.

---

## 10. Риски и позиции (утверждено)

| # | Тема | Решение |
|---|------|---------|
| 1 | **Дублирование** primary nav и drawer | **Оставляем.** Primary navbar («Мои курсы» и др.) **не скрываем** (`hidenodesprimarynavigation` не используем для этого). Часть пользователей держит drawer закрытым и пользуется только верхней навигацией — как на 3.9. |
| 2 | **`local_coursesets`** | **Базовый сценарий — без плагина.** Тема читает `mycourses` из `global_navigation`. Если плагин установят позже — совместимость через те же `extend_navigation`-колбэки (§5), **без доработок плагина в этом проекте**. |
| 3 | **Форматы курса** | **Этап 1:** только `format_topics` и `format_weeks`. Остальные форматы — best effort, отдельно не проектируем. |
| 4 | **Права (capabilities)** | **Обязательно:** видимость пунктов drawer — через стандартный core navigation (те же проверки, что у settingsnav / global_navigation / secondarynav). Не показывать ссылки в обход capability API. |
| 5 | **Тестовый сайт** | `enablegearmenu` **включена**; тестировать gear + legacy drawer вместе. |
| 6 | **Плагины LK** | **Вне scope** `theme_boost3`. Не портим, не правим. §5 — справочный use-case для будущей совместимости. |

| 7 | **Defaults custom-фич** | **`enablelegacydrawer` и `enablegearmenu` — off** при установке (§14.7). |

---

## 11. Критерии приёмки

- [ ] На course home преподавателя (formats **topics**, **weeks**) drawer: пункты курса, разделы (без activity tree), Сайт, стандартные «Мои курсы» — как на eos2.
- [ ] Активный пункт drawer — сплошная primary-заливка (§15.1); на course home подсвечено имя курса, на Participants — «Участники».
- [ ] Режим ссылок на разделы: по умолчанию `sectionpage`; при `anchor` — `#section-N` на `course/view.php` (§15.2).
- [ ] Primary navbar остаётся доступным параллельно с drawer (дублирование допустимо).
- [ ] Toggle в navbar; по умолчанию drawer **открыт**; закрытие сохраняется в `drawer-open-index`.
- [ ] Закрытый drawer не сдвигает контент (поведение Boost 4.5).
- [ ] При включённом gear tertiary на Participants не дублируется в drawer и action bar.
- [ ] Настройка выключает legacy mode → стандартный Boost Union drawer без регрессий.
- [ ] Admin pages (`/admin/…`) — без legacy drawer (как gear: скрыт). **Уточнить по §14:** на 3.9 drawer на `/admin/index.php` есть.

---

## 12. Что не входит в этот план

- Изменения core Moodle.
- Разработка, порт и тестирование **`local_coursesets`**, **`block_lk`**, патча **`block_myoverview`** (отдельные проекты, бета).
- Скрытие узлов primary navigation ради устранения дубля с drawer.
- Гарантированная поддержка форматов курса кроме topics/weeks на этапе 1.
- Плагины `local_navbarplus` / Smart menus (только рекомендации по primary nav).
- Правый drawer блоков.
- Off-canvas regions Boost Union.

---

## 13. Ссылки на исследованные ресурсы

- Боевой 3.9: `https://eos2.vstu.ru` (курс 1472, dashboard `/my/`).
- Тест 4.5: `https://m45.umu.vstu.ru` (курс 2, тема Boost3).
- Boost Union README (drawer width, navigation, activitynavigation): [moodle-theme_boost_union](https://github.com/moodle-an-hochschulen/moodle-theme_boost_union).
- Core drawer JS: `theme/boost/amd/src/drawers.js` — `setUserPreference` при open/close.
- Текущая реализация boost3: `layout/drawers.php`, `lib.php`, `templates/theme_boost/drawers.mustache`.
- Плагины LK: `moodle-block_lk`, `moodle-plugins-lk-coursesets` (`local/coursesets/lib.php` — `local_coursesets_extend_navigation`).
- Moodle Navigation API (flat nav до 4.0): [Navigation API 4.4](https://moodledev.io/docs/4.4/apis/core/navigation).

---

## 14. Глобальная доступность drawer вне курса (анализ после MVP, 2026-06-09)

### 14.1. Вопрос

На 3.9 левая панель доступна **почти везде** для обычного пользователя (ЛК, главная, календарь, файлы, админ-главная). Нужно ли и как включить то же «глобально» в Boost3?

### 14.2. Текущее состояние Boost3 (MVP)

| Аспект | Поведение |
|--------|-----------|
| Условие включения | `enablelegacydrawer` + `theme_boost3_legacy_drawer_active_for_page()`: залогинен, не guest, не popup/embedded/maintenance/redirect, **не** `theme_boost3_page_is_site_admin_page()` |
| Layout | Только страницы на **`drawers.php`** (наследуется от Boost Union / Boost 4.5). Страницы с `pagelayout=admin` и др. без drawers — **инфраструктуры левого drawer нет** |
| Содержимое вне курса | Секции **«Сайт»** (заголовок) + **«Мои курсы»**; блок курса не строится (`context < CONTEXT_COURSE` или front page) |
| Пустой drawer | Если `legacy_nav_drawer()` вернул пустую строку → `legacynavdrawer=false`, **нет** `#theme_boost-drawers-courseindex`, **нет** гамбургера (`drawers.php`) |

**Проверено на m45 (juliet):** `/my/`, `/calendar/view.php`, `/user/files.php` — legacy drawer **есть**, toggle есть, drawer открыт по умолчанию.

### 14.3. Эталон 3.9 вне курса (eos2, преподаватель)

| Страница | Drawer | Содержимое (порядок) |
|----------|--------|----------------------|
| `/my/` | Да | ЛК · Домашняя · Календарь · Личные файлы · **«Мои курсы»** · курсы… |
| `/calendar/view.php` | Да | То же; **Календарь** active |
| `/user/files.php` | Да | То же; **Личные файлы** active |
| `/course/view.php` | Да | Курс + разделы · сайтовые ссылки · «Мои курсы» · курсы… |
| `/admin/index.php` | **Да** | **Те же** сайтовые + «Мои курсы» (не admin settings tree) |

Отдельного текстового заголовка **«Сайт»** в 3.9 **нет** — только **«Мои курсы»** как non-clickable разделитель.

### 14.4. Расхождения MVP с 3.9 (принципиальные)

| # | Тема | 3.9 | Boost3 MVP | Критичность |
|---|------|-----|------------|-------------|
| 1 | **Источник «сайтовых» пунктов** | `global_navigation` + flat list | Жёсткий список ключей в коде | **Закрыто §14.7:** ЛК/календарь/файлы не воссоздаём; остальное — **config textarea** ключей (§14.8) |
| 2 | **Заголовок «Сайт»** | Нет | Есть (`legacydrawersite`) | Низкая (косметика) |
| 3 | **Сайтовая админка** | Drawer с сайт + курсы на `/admin/index.php` | Исключена в `theme_boost3_page_is_site_admin_page()`; на m45 admin layout **без** left drawer | **Средняя** — нужно решить: повторять 3.9 или оставить как gear |
| 4 | **Admin layout M4.5** | Admin на columns2 + `#nav-drawer` | `pagelayout=admin` → layout **без** course-index drawer | **Высокая** для полного паритета — может потребовать **отдельный** layout/partial, не только смена PHP-флага |
| 5 | **Пустой контент** | Drawer shell + toggle, если есть flat nav | Нет контента → drawer полностью скрыт | Средняя |
| 6 | **Банк контента** | На курсе — да; на dashboard — обычно нет | Показывается на вне-курсовых страницах, если узел найден | Низкая |
| 7 | **Active state пункта** | Сплошная заливка primary (`list-group-item.active`) | Бледный фон `#e7f0fa` + синий текст | **Средняя** — §15.1 |
| 8 | **Ссылки на разделы** | `#section-N` на `course/view.php` | `/course/section.php?id=…` (отдельная страница M4.5) | **Средняя** — §15.2, настройка |

### 14.5. Почему «глобально» уже почти включено, но выглядит иначе

1. **Логика `active_for_page` уже широкая** — не ограничена курсом; исключены только site admin URL / `admin-*` pagetype (кроме course-scoped, §coursecompetencies fix).
2. **Ограничение — layout и наполнение**, не флаг настройки: вне `drawers.php` панель физически не рендерится; внутри — неполный блок «Сайт».
3. В M4.5 core **намеренно** перенёс ЛК/календарь/файлы в navbar ([Navigation improvements 4.0](https://docs.moodle.org/dev/Moodle_4.0_navigation_improvements)); старый приём `showinflatnavigation` [deprecated с 4.0](https://moodledev.io/docs/5.0/apis/core/navigation).

### 14.6. Этап 6 — доработка вне курса (утверждённый scope)

**Цель:** стабильный drawer на drawers-layout страницах для рядовых пользователей; без гонки за полным паритетом с 3.9.

| Задача | Решение |
|--------|---------|
| A. ЛК / Календарь / Личные файлы | **Не воссоздавать** — в M4.5 они в primary navbar; в drawer не дублировать искусственно |
| B. Сайтовые пункты в drawer | Только узлы, реально присутствующие в `global_navigation` по **настраиваемому** списку ключей (§14.8) |
| C. Дублирование с navbar | **Допустимо и желательно** там, где узел ещё есть в navigation tree (напр. «Домашняя», «Банк контента») |
| D. Заголовок «Сайт» | Косметика; можно убрать вне курса при полировке (низкий приоритет) |
| E. Сайтовая админка | **Исключение сохраняем** — drawer на `/admin/…` не делаем; admin layout не трогаем |
| F. Admin settings tree в drawer | **Вне scope** — фича классической темы, для Boost3 неактуально |
| G. Пустой drawer | При `legacyactive` + непустые «Мои курсы» — drawer и toggle; без искусственного наполнения |
| H. Active fill | Сплошная primary-заливка активного пункта (§15.1) |
| I. Section links | Настраиваемый режим якорь / `section.php` (§15.2) |

### 14.7. Утверждённые решения (2026-06-09)

| # | Тема | Решение |
|---|------|---------|
| 1 | ЛК / календарь / файлы в drawer | **Не добавлять** (primary nav достаточно) |
| 2 | Сайтовая админка | **Drawer отключён** (`theme_boost3_page_is_site_admin_page`) |
| 3 | Admin layout M4.5 | **Не расширяем** |
| 4 | Дублирование с primary nav | **Оставляем** — лучше похожий перечень в drawer, чем уникальность |
| 5 | Defaults всех custom-фич | **`enablelegacydrawer` и `enablegearmenu` — default OFF** |
| 6 | Admin settings tree | **Не делаем** |

### 14.8. Настраиваемый список ключей сайтовой секции (утверждено к реализации)

**Вопрос:** вынести жёсткий список ключей в конфиг темы?

**Ответ: да, имеет смысл и технически просто.**

| Аспект | Детали |
|--------|--------|
| Настройка | `legacydrawersitekeys` — `admin_setting_configtextarea` в `settings.php` |
| Формат | Один ключ узла `global_navigation` на строку (или через запятую); пустые строки игнорируются |
| Default | `home` и `contentbank` (без `myhome`, `calendar`, `privatefiles` — см. §14.7.1) |
| Видимость | Поле в настройках темы Boost3; описание со ссылкой на ключи core и поведением «узел не найден → пропуск» |
| Реализация | `theme_boost3_legacy_drawer_site_keys()` в `lib.php` → парсинг + `PARAM_ALPHANUMEXT`; `boost3_legacy_build_site_section()` читает список |
| Прозрачность | Админ видит и правит список без правки PHP; для плагинов — добавить ключ узла из `extend_navigation`, если узел остаётся в `global_navigation` |
| Ограничение | Ключ должен существовать в дереве navigation на данной странице; primary-only узлы **не** подтянуть этим способом (и не нужно) |

**Не делаем:** отдельный UI для подписей/иконок, drag-and-drop порядка — избыточно; порядок = порядок строк в textarea.

---

## 15. Визуальные и поведенческие доработки drawer (к реализации)

Зафиксировано по запросу (2026-06-09). Входит в этап полировки / этап 6.

### 15.1. Заливка активного пункта primary-цветом

**Эталон 3.9 (§2.4):** текущая страница в drawer — **сплошной** primary-фон и контрастный текст (как стандартный Bootstrap `list-group-item active` в Boost 3.x). На курсе «горит» имя курса; на «Участники» — пункт «Участники».

**Сейчас в Boost3:** в `post.scss` для `.active` — бледный фон `#e7f0fa`, синий текст, `font-weight: 600` — визуально слабее 3.9.

**Задачи:**

| # | Что сделать |
|---|-------------|
| 1 | SCSS: `.theme-boost3-legacy-nav .list-group-item-action.active` — `background-color` = theme primary (`$primary` / CSS var Boost), `color` = `$white` или `$gray-100`, границы согласованы с Boost 3.9 |
| 2 | Иконка в active-пункте — наследует светлый цвет текста |
| 3 | PHP: проверить корректность флага `active` — имя курса на `course-view`, secondary tabs (`isactive`), раздел при просмотре секции, site/mycourses из `navigation` (`isactive`) |
| 4 | Fallback: сравнение текущего URL с `href` пункта, если `isactive` у узла не выставлен core |

**Критерий:** на eos2 и m45 при переходе Участники / главная курса активный пункт визуально «кнопка-primary», не просто подсветка.

### 15.2. Ссылки на разделы курса: якорь vs отдельная страница

**Эталон 3.9 (§2.4):** разделы → `/course/view.php?id={courseid}#section-{sectionnum}` — прокрутка на странице курса.

**Сейчас в Boost3:** `boost3_legacy_build_course_section_links()` → `/course/section.php?id={sectionid}` (страница секции M4.5, полная навигация/перезагрузка). Выбрано ранее из‑за дедупликации URL в MVP; поведение **отличается** от 3.9.

**Настройка темы** (утверждено):

| Параметр | Значение |
|----------|----------|
| Имя | `legacydrawersectionlinks` |
| Тип | `admin_setting_configselect` |
| Варианты | **`sectionpage`** (по умолчанию) — текущее поведение `/course/section.php?id=…`; **`anchor`** — как 3.9: `/course/view.php?id=…#section-{N}` |
| Default | **`sectionpage`** — не менять поведение без явного выбора админа |
| Где | `settings.php`, видно при настройке legacy drawer |

**Реализация:**

- `theme_boost3_legacy_drawer_section_link_mode()` в `lib.php`;
- в `boost3_legacy_build_course_section_links()` — ветка URL по режиму;
- для `anchor`: active-состояние на `course-view` при совпадении `#section` / `section` query (если есть);
- **ограничение:** на форматах без якорей на `course/view.php` режим `anchor` может работать хуже — в lang string предупредить; formats topics/weeks — основной кейс.

**Не делаем:** отдельный JS smooth-scroll AMD — достаточно нативного hash; при необходимости позже.

### 15.3. Затрагиваемые файлы

| Файл | Изменения |
|------|-----------|
| `settings.php` | `legacydrawersectionlinks` |
| `lang/en\|ru/theme_boost3.php` | строки настройки + подсказки |
| `lib.php` | `theme_boost3_legacy_drawer_section_link_mode()` |
| `classes/output/core_renderer.php` | URL разделов, уточнение `active` |
| `scss/post.scss` | active fill §15.1 |

---

*Статус: §7.1–7.5, §10, **§14.6–14.8**, **§15** — в плане (2026-06-09). MVP этапов 1–4 реализован. Этап 6 + §15 — к реализации после явного старта.*
