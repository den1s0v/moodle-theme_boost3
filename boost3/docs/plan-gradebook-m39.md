# Gradebook navigation — M3.9 spatial map

Reference for [`classes/grade_navigation_builder.php`](../classes/grade_navigation_builder.php) and regression on **eos2** / **m45**.

## Layout

- **Row 1** (1.x): top-level sections — Просмотр · Настройки · Шкалы · Буквы · Импорт · Экспорт (capability-filtered).
- **Row 2** (2.x): siblings within the **active** row-1 section (hidden when ≤1 item).
- Active tab has no link (`nav-link active` only).
- No gear on gradebook pages.

## Examples (eos2 course 13800, admin)

| URL | Row 1 active | Row 2 active |
|-----|--------------|--------------|
| `/grade/report/grader/index.php` | Просмотр (1.1) | Отчёт по оценкам (2.1) |
| `/grade/edit/tree/index.php` | Настройки (1.2) | Настройка журнала (2.1) |
| `/grade/import/csv/index.php` | Импорт (1.5) | CSV (2.1) |
| `/grade/export/ods/index.php` | Экспорт (1.6) | ODS (2.1) |
| `/grade/edit/scale/index.php` | Шкалы (1.3) | — (no row 2) |

Teachers with fewer capabilities may see a shorter row 1 (e.g. Просмотр · Настройки · Экспорт only).

## m45 smoke (course id=4)

- `/grade/report/grader/index.php` — tabs visible, `#theme-boost3-gear-btn` absent
- `/grade/report/user/index.php` — row 2 switches to user report
- `/grade/edit/tree/index.php` — row 1 «Настройки» active

## Implementation notes

- Data source: core `grade_get_plugin_info()` (same as M3.9 `grade_print_tabs` logic).
- M4.5 tertiary dropdown is hidden via `boost3_hide_tertiary_overflow` + `theme-boost3-grade-tabs` SCSS.
- Page kind: `page_classifier::KIND_GRADEBOOK` (`/grade/` in course context).
