# Заголовки, breadcrumbs и secondary/gear — анализ m45 ↔ eos2

Документ по браузерному сравнению **m45** (Moodle 4.5, theme_boost3, juliet) и **eos2** (Moodle 3.9, boost, преподаватель) — сессия 2026-06-12.  
Настройки на m45: `enablelegacydrawer=1`, `enablegearmenu=1`.

---

## 1. Резюме

| Зона | Moodle 3.9 (eos2) | Moodle 4.5 + boost3 (m45) | Приоритет |
|------|-------------------|---------------------------|-----------|
| **Breadcrumbs** | Цепочка со ссылками: ЛК → Мои курсы → курс → вкладка/модуль | На course secondary **пустые** (`<ol class="breadcrumb"></ol>`); на mod — урезанная цепочка без ЛК/«Мои курсы»; на grades — текст без ссылок | **Высокий** |
| **H1** | Часто полное имя курса; на grades — длинный контекстный заголовок | Только shortname курса или имя модуля; контекст вкладки — только в `<title>` | Средний |
| **Secondary tabs** | Горизонтальные вкладки на grades, activity, course admin | Скрыты при legacy drawer; заменены gear | Ожидаемо* |
| **Gear / settings** | `context-header-settings-menu` в строке заголовка (справа от H1) | `#theme-boost3-gear-btn` в полосе `.secondary-navigation` под заголовком | Средний |
| **Tertiary** | Inline tabs (grades reports, attendance modes) | В gear или скрыто (`boost3_hide_tertiary_overflow`) | Средний |

\*Скрытие horizontal secondary при legacy drawer — **осознанное** (plan-left-drawer-m39 §7.3). Несоответствие с 3.9 — в **отсутствии breadcrumb-навигации**, а не в drawer.

---

## 2. Матрица страниц (фактическое поведение)

### 2.1. m45 (курс id=4, «Тест сертификата»)

| Страница | Breadcrumbs | H1 | Secondary tabs | Gear |
|----------|-------------|-----|----------------|------|
| `/course/view.php?id=4` | **пусто** | Тест сертификата | нет (скрыты) | да (overflow: Настройки, Отчёты, …) |
| `/user/index.php?id=4` | **пусто** | Тест сертификата | нет | да |
| `/grade/report/grader/index.php?id=4` | spans: Управление оценками, Отчёт по оценкам (**без ссылок**, без ЛК/курса) | Тест сертификата | нет | да |
| `/mod/forum/view.php?id=719` | Тест сертификата → Общее → Объявления (**ссылки есть**, без ЛК) | Объявления | нет | да |
| `/course/edit.php?id=4` | пусто | Тест сертификата | **да** (Курс, Настройки, Участники, …) | нет |

### 2.2. eos2 (курсы 21491, 439)

| Страница | Breadcrumbs | H1 | Secondary tabs | Settings menu |
|----------|-------------|-----|----------------|---------------|
| course home | ЛК → Мои курсы → курс (links) | полное имя курса | нет | `context-header-settings-menu` (шестерёнка в header row) |
| participants | ЛК → Мои курсы → курс → **Участники** (links) | полное имя курса | нет | — |
| grades (user report) | ЛК → … → Оценки → … → Отчёт (links + spans) | **длинный** (курс: просмотр: отчёт) | Обзорный / По пользователю | — |
| mod/attendance | ЛК → курс → секция → модуль (links) | имя курса | Текущий курс / Все курсы / … | — |
| course/admin.php | ЛК → … → Управление курсом | имя курса | Управление / Отчёты | — |

---

## 3. Концептуальные расхождения

### 3.1. Breadcrumbs как основной канал «где я»

В M3.9 при отсутствии horizontal secondary **breadcrumbs заменяют вкладки** для навигации вверх по иерархии. Пользователь кликает «курс» или «Участники», не открывая drawer.

В M4.5 core при `has_secondary_navigation()` **намеренно не заполняет** navbar на многих course secondary страницах — контекст уходит в secondary tabs. boost3 **скрывает** эти tabs → **пустой breadcrumb** → регрессия UX.

**Целевое поведение boost3 (legacy drawer on):**

```
Личный кабинет (link) → Мои курсы (text) → {shortname курса} (link) → … активная ветка secondary/settings …
```

- Префикс ЛК + «Мои курсы» добавлять на **все** course-scoped страницы, включая mod (дополнять, не заменять core-хвост).
- Узлы secondarynav / settingsnav с URL — **ссылки**; текущая страница — `aria-current="page"`.
- Не трогать `course-edit` и site admin — там свои horizontal tabs.

### 3.2. Заголовок H1

| Контекст | eos2 | Рекомендация boost3 |
|----------|------|---------------------|
| Course home / participants | H1 = fullname курса | Оставить shortname/fullname курса; контекст вкладки — в breadcrumb |
| Grades / nested reports | H1 включает тип отчёта | Опционально: subtitle под H1 или расширенный H1 из active settings node |
| Activity | H1 = имя модуля | Оставить; путь — breadcrumb |

Минимальный MVP: **не менять H1**, восстановить breadcrumbs. Расширенный H1 — этап 2.

### 3.3. Secondary navigation vs gear (единая матрица)

Источник истины: `navigation_policy::resolve()`.

| `page_kind` | Legacy drawer | `show_secondary_tabs` | `show_gear` | `use_gear_secondary_nav` | `gear_inline_header` | Примечание |
|-------------|---------------|----------------------|-------------|--------------------------|----------------------|------------|
| `course_format` | да | **нет** | да (overflow) | да | нет | gear в полосе под header |
| `course_secondary` | да | **нет** | да | да | нет | participants, grades, badges… |
| `participants` | да | **нет** | да | да | нет | tertiary в gear; action bar без select |
| `course_admin` | **нет** | **да** | **нет** | — | — | `/course/edit.php` — как core M4 |
| `site_admin` | **нет** | да | нет | — | — | |
| mod (activity) | да | нет | да* | да* | нет | *если overflow; mod tabs часто в content |

**Несогласованности для выравнивания:**

1. **Позиция gear** — eos2: в `header-actions-container` рядом с H1; m45: отдельная полоса `.theme-boost3-secondary-gear-wrap`. Для паритета — перенос gear в `full_header()` / `add_header_action()` на course-format и secondary (уже есть `gear_inline_header` для gear-only без secondary nav container — расширить).
2. **Tertiary на grades/activity** — eos2 показывает inline tabs; m45 прячет в gear (`hide_tertiary_overflow`). Варианты: (A) показывать tertiary inline как на 3.9; (B) оставить в gear, но **обязательно** дать breadcrumb-цепочку до отчёта.
3. **course/edit vs course/admin** — M4 `course/edit.php` с horizontal tabs; M3 `course/admin.php` — другой URL. Breadcrumb на edit не критичен; tabs достаточно.

### 3.4. Визуальная иерархия header (M4 card vs M3 flat)

m45 (Boost 4): `#page-header` — flex, иногда card-стиль на eos2. eos2: card с breadcrumb **под** H1 в одном блоке. Порядок на 3.9: **H1 сверху, breadcrumb снизу** в `#page-navbar` — на m45 порядок тот же, но breadcrumb пуст.

SCSS: не ломать Union; при необходимости усилить контраст breadcrumb-ссылок под legacy mode.

---

## 4. План реализации (приоритеты)

### Этап A — Breadcrumbs (MVP, начат в этой сессии)

- [ ] `classes/breadcrumb_builder.php` — сбор цепочки M3.9
- [ ] Вызов из `core_renderer::full_header()` до `parent::full_header()`
- [ ] Условие: `legacy_drawer_active` + course-scoped + не `course_admin`
- [ ] Дополнение существующих mod-breadcrumbs префиксом ЛК / Мои курсы
- [ ] Тест: course home, participants, grades, forum

### Этап B — Gear placement

- [ ] На course home / secondary: gear в `header-actions-container` (как `context-header-settings-menu`)
- [ ] Полоса `.theme-boost3-secondary-gear-wrap` — только при split mode (tabs + gear)

### Этап C — Tertiary inline (опционально)

- [ ] Grades / activity: `show_secondary_tabs` или отдельный флаг для tertiary-only strip
- [ ] Не дублировать с drawer и gear

### Этап D — H1 / subtitle

- [ ] Контекстная строка под H1 для grades и глубоких settings

---

## 5. Критерии приёмки (breadcrumb MVP)

- [ ] Participants: breadcrumb `Личный кабинет > Мои курсы > {курс} > Участники` со ссылками (кроме «Мои курсы»)
- [ ] Course home: `… > {курс}` (текущий)
- [ ] Grades: полная цепочка со ссылками до активного отчёта
- [ ] Forum: префикс ЛК / Мои курсы перед существующими крошками
- [ ] course/edit: без изменений (horizontal tabs)
- [ ] Сравнение с eos2 на тех же типах страниц

---

## 6. Файлы

| Файл | Роль |
|------|------|
| `classes/breadcrumb_builder.php` | **новый** — сбор navbar |
| `classes/output/core_renderer.php` | вызов builder в `full_header()` |
| `lib.php` | хелпер `theme_boost3_should_populate_legacy_breadcrumbs()` |
| `classes/navigation_policy.php` | без смены логики gear/tabs на этапе A |
| `docs/REGRESSION-CHECKLIST.md` | дополнить сценарии header |

---

### Статус реализации (2026-06-12)

**Корневая причина:** не пустой `$PAGE->navbar`, а `theme_boost_union\boostnavbar::prepare_nodes_for_boost()` — в `CONTEXT_COURSE` удаляет myhome, mycourses, course и всё, что дублирует `secondarynav`.

**Решение (этап A — сделано):**

| Файл | Роль |
|------|------|
| `classes/boostnavbar.php` | При legacy drawer — режим M3.9: не вызывать фильтры Union |
| `classes/breadcrumb_builder.php` | Если navbar пуст — **копировать узлы** из `$PAGE->navigation`, `$PAGE->settingsnav`, `$PAGE->secondarynav` |
| `classes/output/core_renderer.php` | `navbar()` → `theme_boost3\boostnavbar` |

**Проверено на m45 (курс 4):**

- Участники: `ЛК → Мои курсы → Тест сертификата → Участники` (как eos2)
- Forum: префикс + core-хвост (секция, модуль)
- Grades: полная цепочка из settingsnav

---

### Откуда берутся элементы breadcrumb (не переизобретаем)

1. **Core заполняет `$PAGE->navbar`** при инициализации страницы (скрипты, settingsnav, modinfo) — те же деревья, что кормят gear и drawer.
2. **`breadcrumb_builder`** только **дочитывает** navbar, если Boost 4 оставил пусто:
   - **Личный кабинет** — узел `myhome` из `$PAGE->navigation` (global_navigation)
   - **Мои курсы** — узел `mycourses` из `$PAGE->navigation` (без ссылки, как 3.9)
   - **Курс** — shortname + URL из `secondarynav`/`course/view.php`
   - **Вкладка/отчёт** — активная ветка `$PAGE->settingsnav` (participants, grades, …) или `$PAGE->secondarynav`
3. **Модули** — core уже строит `курс → секция → модуль`; builder лишь **префиксует** ЛК + Мои курсы.
4. **`boostnavbar`** перестаёт **удалять** эту цепочку в course/module context.

---

*Статус: этап A выполнен; **этап B реализован** (2026-06-13, `navigation_policy`); этапы C–D (tertiary inline, H1) — в плане.*
