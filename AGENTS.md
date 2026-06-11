# AGENTS.md — theme_boost3

Инструкция для агентов Cursor при работе с темой **theme_boost3** (Moodle 4.5, дочерняя тема Boost Union) и проверкой в браузере.

## Репозиторий

| Путь | Назначение |
|------|------------|
| `boost3/` | Код темы (устанавливается в `moodle/theme/boost3/`) |
| `boost3/docs/REGRESSION-CHECKLIST.md` | Чеклист регрессии навигации |
| `boost3/docs/plan-left-drawer-m39.md` | Дизайн и матрица поведения |
| `hidden/cred.txt` | **Учётные данные и URL двух сайтов** (не коммитить) |

Перед браузерной проверкой прочитай `hidden/cred.txt` — там логины, пароли и базовые URL.

## Два сайта

| Сайт | Роль | Moodle | Тема |
|------|------|--------|------|
| **m45** | Тестовый (разработка boost3) | 4.5 | boost3 (кастомная) |
| **eos2** | Пример эталона UX | 3.9 | boost (обычная) |

- **m45** — правки темы, upgrade, purge caches, скриншоты/снапшоты gear и drawer.
- **eos2** — сравнение с Moodle 3.9 (как должно выглядеть/вести себя legacy drawer и шестерёнка). Аккаунт преподавателя, не админ.

Не дублируй пароли в чат, коммиты и PR. Бери их только из `hidden/cred.txt`.

## Деплой на m45

Файлы на сервер синхронизируются автоматически с локальной копии с задержкой **3–5 секунд**.

После изменений в теме:

1. Подожди синхронизацию.
2. Только в случае изменений, которые затрагивают фишируемые области вроде новых классов или стиля или языковые изменения:
2.1. (если версия плагина менялась) Открой `/admin/index.php` — при необходимости нажми **Продолжить** (upgrade плагина после bump `boost3/version.php`).
2.2. Или (альтернативно, если версия плагина не менялась) **Очисти все кэши**: Администрирование → Разработка → Очистить кэши, или `/admin/purgecaches.php` (нужен `sesskey` со страницы).
3. Жёсткое обновление страницы курса (Ctrl+F5).

Bump версии в `boost3/version.php` обязателен при изменениях PHP, mustache, AMD, SCSS.

## Браузер (Playwright MCP)

Сервер: **user-Playwright**. Используй для логина, навигации, проверки DOM и иконок.

### Типовой старт сессии

```
1. Прочитать hidden/cred.txt
2. browser_navigate → /login/index.php
3. Ввести логин/пароль с m45, войти
4. Перейти на тестовую страницу (см. ниже)
5. При необходимости — вторую вкладку на eos2 для сравнения
```

Логин на Moodle: поля «Логин» и «Пароль», кнопка «Вход». Если сессия уже есть — сразу открывай целевой URL.

### Полезные селекторы (boost3)

| Элемент | Селектор |
|---------|----------|
| Кнопка шестерёнки | `#theme-boost3-gear-btn` |
| Пункты gear-меню | `.theme-boost3-gear a.dropdown-item` |
| Иконка пункта gear | `.theme-boost3-gear-item-icon` |
| Legacy drawer toggle | `#theme-boost3-legacy-nav-toggle` (гамбургер) |

Проверка иконок в evaluate: смотри `innerHTML` иконки, для `<img>` — `naturalWidth === 0` означает битую картинку (404 pix в M4.5).

### Тестовые URL (m45)

Подставь `id` курса, с которым есть доступ у juliet (часто **4** или **7**):

| Сценарий | URL |
|----------|-----|
| Главная курса | `/course/view.php?id=4` |
| Участники + gear overflow | `/user/index.php?id=4` |
| Настройки темы | `/admin/settings.php?section=themesettingboost3` |
| Редактирование курса (legacy drawer + tabs) | `/course/edit.php?id=4` |

На eos2 используй те же пути относительно корня и курс, доступный преподавателю.

### Настройки темы для проверки navigation

Включать в админке при тесте legacy/gear (по умолчанию **выкл.**):

- **Legacy left navigation drawer** — `enablelegacydrawer`
- **Gear-style course menu** — `enablegearmenu`

Остальные ключи — `legacydrawercoursekeys`, `legacydrawergearexcludedkeys` и т.д. — см. `boost3/settings.php` и `boost3/README.md`.

## Сравнение m45 ↔ eos2

Типичный запрос пользователя: «как в 3.9» — открыть **одну и ту же логическую страницу** на обоих сайтах.

| Что сравнить | m45 | eos2 |
|--------------|-----|------|
| Левый drawer | flat: курс, вкладки, секции | activity tree / flat в 3.9 |
| Шестерёнка | overflow secondary/tertiary | gear / dropdown настроек курса |
| Active state | один активный пункт | не должно быть лишних подсветок |
| Иконки пунктов | FA + fallback pix | визуальный ориентир |

Фиксируй расхождения скриншотом/snapshot или списком пунктов меню с HTML иконок.

## Ключевые файлы темы

| Файл | Зона ответственности |
|------|---------------------|
| `classes/page_classifier.php` | Детекция типа страницы (KIND_*) |
| `classes/navigation_matrix.php` | Декларативная матрица каналов по KIND |
| `classes/navigation_channel_profile.php` | DTO профиля каналов |
| `classes/navigation_policy.php` | Resolve: матрица + runtime-модификаторы |
| `classes/output/core_renderer.php` | Сборка drawer и gear, иконки |
| `classes/active_state_resolver.php` | Подсветка active |
| `lib.php` | Обёртки, парсеры настроек, template flags |
| `templates/theme_boost3/*.mustache` | Разметка drawer, gear, toggle |
| `scss/post.scss` | Стили legacy navigation |

## Иконки в gear (Moodle 4.5)

- Старые pix (`i/award`, `i/external`, `i/roles` и др.) могут отдавать **404** — в коде есть blocklist и цепочка fallback.
- Moodle ставит **fa-gear** на многие admin-пункты через `i/settings` — в gear шестерёнка намеренно только у **Настройки** (`/course/edit.php`).
- Дефолт при отсутствии иконки: **`i/next`** (`fa-chevron-right`).

## Ограничения для агента

- Коммиты и PR — **только по явной просьбе** пользователя.
- Не коммитить `hidden/cred.txt` и секреты.
- После правок темы на m45 — не считать баг воспроизведённым, пока не сделан upgrade + purge caches.
- Отвечать пользователю **на русском**, если не попросили иначе.

## Быстрый промпт для нового чата

> Прочитай `AGENTS.md` и `hidden/cred.txt`. Включи legacy drawer и gear на m45 (если выкл.). Проверь в браузере [страница/сценарий] и сравни с eos2.
