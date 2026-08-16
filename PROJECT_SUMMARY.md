# Проект: Личный трекер пивоварения — Сводка

## 1. Стек

| Слой | Технология | Примечание |
|------|-----------|------------|
| Бэкенд | Laravel 12 | Eloquent напрямую в контроллерах |
| База | SQLite | Файловая БД (`database/database.sqlite`) |
| Фронтенд | Blade + Vue 3.5 | Blade = каркас, Vue = островки |
| Сборка | Vite 8 | `npm run dev` / `npm run build` |
| API | REST API (`/api/v1/...`) | Только для Vue-компонентов |
| CSS | Tailwind 4 + Vite plugin | |

---

## 2. Структура проекта

```
/workspace
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/           # API-контроллеры (возвращают JSON)
│   │   └── Web/           # Web-контроллеры (возвращают Blade)
│   └── Models/            # Eloquent-модели
├── database/
│   ├── migrations/        # Миграции БД
│   └── seeders/           # Сидеры (категории, единицы, настройки)
├── resources/
│   ├── js/components/     # Vue-компоненты (6 шт)
│   └── views/             # Blade-шаблоны
│       ├── layouts/       # layout с боковым меню
│       └── {home,equipment,stock,recipes,brews,settings}/
├── routes/
│   ├── web.php            # Web-роуты (Blade)
│   └── api.php            # API-роуты (JSON)
└── storage/app/exports/   # Экспорты БД (backup_*.sqlite)
```

---

## 3. Основные сущности (Модели)

| Модель | Таблица | Описание |
|--------|---------|----------|
| `Category` | `categories` | Категории ингредиентов (Солод, Хмель, Дрожжи...) |
| `Unit` | `units` | Единицы измерения (кг, г, л, мл, шт, упаковка) |
| `Ingredient` | `ingredients` | Ингредиенты (связь с category, unit) |
| `Equipment` | `equipment` | Оборудование (name, price, purchase_date) |
| `StockBatch` | `stock_batches` | Партии закупок (FIFO-учёт) |
| `Recipe` | `recipes` | Рецепты (name, description) |
| `RecipeIngredient` | `recipe_ingredients` | Состав рецепта (ingredient, quantity, add_time_minutes) |
| `Brew` | `brews` | Записи о варках (volume_actual, cost_per_liter, is_modified, modified_diff) |
| `BrewIngredient` | `brew_ingredients` | Списанные ингредиенты (привязка к batch_id) |
| `BrewComment` | `brew_comments` | Комментарии к варкам |
| `Setting` | `settings` | Key-value настройки (тарифы, параметры) |
| `User` | `users` | Пользователи (стандартная Laravel) |

---

## 4. API-роуты (`routes/api.php`)

### Справочники (CRUD)
```
GET|POST /api/v1/categories
GET|PUT|DELETE /api/v1/categories/{id}

GET|POST /api/v1/units
GET|PUT|DELETE /api/v1/units/{id}

GET|POST /api/v1/ingredients
GET|PUT|DELETE /api/v1/ingredients/{id}

GET|POST /api/v1/equipment
GET|PUT|DELETE /api/v1/equipment/{id}
```

### Склад
```
GET  /api/v1/stock              → остатки с возрастом партии
POST /api/v1/stock/receipts     → добавить партию
GET  /api/v1/stock/forecast     → прогноз варок
```

### Рецепты
```
GET|POST   /api/v1/recipes
GET|PUT|DELETE /api/v1/recipes/{id}
```

### Варки
```
GET|POST /api/v1/brews
GET  /api/v1/brews/{brew}
PUT  /api/v1/brews/{brew}/complete    → завершить варку (объём + расчёт стоимости)
GET  /api/v1/brews/{brew}/comments
POST /api/v1/brews/{brew}/comments
PUT  /api/v1/comments/{comment}
DELETE /api/v1/comments/{comment}
```

### Настройки
```
GET /api/v1/settings
PUT /api/v1/settings
POST /api/v1/settings/export-db
POST /api/v1/settings/import-db
```

---

## 5. Веб-роуты (`routes/web.php`)

| URL | Контроллер | View |
|-----|-----------|------|
| `/` | `HomeController@index` | `home/index.blade.php` |
| `/equipment` | `EquipmentController@index` | `equipment/index.blade.php` |
| `/stock` | `StockController@index` | `stock/index.blade.php` |
| `/recipes` | `RecipeController@index` | `recipes/index.blade.php` |
| `/recipes/create` | `RecipeController@create` | — |
| `/recipes/{recipe}` | `RecipeController@show` | — |
| `/recipes/{recipe}/edit` | `RecipeController@edit` | — |
| `/brews` | `BrewController@index` | `brews/index.blade.php` |
| `/brews/{brew}` | `BrewController@show` | — |
| `/settings` | `SettingsController@index` | `settings/index.blade.php` |
| `POST /settings/import` | `SettingsController@import` | — |

---

## 6. Контроллеры

### Web (возвращают Blade)
| Контроллер | Назначение |
|-----------|------------|
| `HomeController` | Главная страница со статистикой |
| `EquipmentController` | Список оборудования |
| `StockController` | Страница склада (Vue-компонент) |
| `RecipeController` | Список/просмотр/редактирование рецептов |
| `BrewController` | Список/детали варок |
| `SettingsController` | Настройки + импорт/экспорт БД |

### API (возвращают JSON)
| Контроллер | Назначение |
|-----------|------------|
| `CategoryController` | CRUD категорий |
| `UnitController` | CRUD единиц измерения |
| `IngredientController` | CRUD ингредиентов |
| `EquipmentController` | CRUD оборудования |
| `StockController` | Остатки, приёмка, прогноз |
| `RecipeController` | CRUD рецептов + расчёт себестоимости |
| `BrewController` | Создание варок, FIFO-списание, комментарии |
| `SettingController` | Настройки, экспорт/импорт БД |

---

## 7. Что реализовано

### Готовые страницы
- ✅ `/` — Главная (статистика: рецепты, варки, ингредиенты)
- ✅ `/equipment` — Оборудование (список + формы Добавить/Редактировать)
- ✅ `/stock` — Склад (остатки, приёмка, прогноз варок)
- ✅ `/recipes` — Рецепты (карточки)
- ✅ `/brews` — Варки (список)
- ✅ `/settings` — Настройки (тарифы, импорт/экспорт)

### Vue-компоненты (6 шт)
- `HomePage.vue` — статистика
- `EquipmentPage.vue` — управление оборудованием
- `StockPage.vue` — остатки, приёмка, прогноз
- `RecipesPage.vue` — список рецептов
- `BrewsPage.vue` — список варок
- `SettingsPage.vue` — настройки

### Реализованная логика
- ✅ FIFO-списание ингредиентов при варке
- ✅ Расчёт себестоимости рецепта по цене старой партии
- ✅ Расчёт коммунальных затрат (электроэнергия, вода питьевая, вода техническая)
- ✅ Прогноз количества варок на основе последних 10 варок
- ✅ Импорт из старой БД (SQLite → SQLite)
- ✅ Экспорт текущей БД
- ✅ Модифицированные варки с сохранением диффа (JSON)
- ✅ Комментарии к варкам (CRUD)

---

## 8. Схема БД

```
categories (id, name, timestamps)
  ↓
ingredients (id, category_id→categories, unit_id→units, name, timestamps)
  ↑              ↓
units (id, name, timestamps)   stock_batches (id, ingredient_id, quantity, price_per_unit, purchase_date, timestamps)
                                 ↓
                              brew_ingredients (id, brew_id→brews, ingredient_id, batch_id→stock_batches, quantity_used, price_per_unit, timestamps)

recipes (id, name, description, timestamps)
  ↓
recipe_ingredients (id, recipe_id→recipes, ingredient_id→ingredients, quantity, add_time_minutes, timestamps)
  ↓
brews (id, recipe_id→recipes nullable, volume_actual, cost_per_liter, is_modified, modified_diff json, timestamps)
  ↓
brew_comments (id, brew_id→brews, content, timestamps)

equipment (id, name, price, purchase_date, timestamps)

settings (id, key unique, value, timestamps)
```

**Правила:**
- `stock_batches` не удаляются — история цен хранится бесконечно
- `brew_ingredients.batch_id` — привязка списания к конкретной партии
- `brews.modified_diff` — JSON-дифф для модифицированных варок

---

## 9. Архитектурные принципы

1. **Eloquent напрямую в контроллерах** — без репозиториев
2. **Service Layer — только для сложной логики**: FIFO, расчёт себестоимости, прогноз
3. **Blade — каркас**: меню, навигация, лейаут, обёртка страниц
4. **Vue — островки**: интерактивные таблицы, формы внутри Blade-страниц
5. **Без Pinia** — состояние в компонентах (`ref`, `reactive`)
6. **Без Vue Router** — навигация через Blade-ссылки и `window.location`
7. **API только для Vue** — Blade-страницы рендерятся сервером, Vue забирает данные через API
8. **Настройки в БД** — таблица `settings` (key-value)

---

## 10. Известные проблемы / Отклонения от ТЗ

### Отклонения
- ❌ Страницы `/recipes/{id}`, `/recipes/create`, `/recipes/{id}/edit` — есть роуты, но нет view-файлов (только index)
- ❌ Страница `/brews/{id}` — есть роут, но нет view-файла
- ❌ Температурные паузы в рецептах — не реализованы (нет миграции, нет полей в БД)
- ❌ Ручное списание ингредиентов — нет API эндпоинта
- ❌ Массовая приёмка товара — не реализована
- ❌ Аналитика/графики — не реализованы (как и указано в ТЗ v1)

### Потенциальные проблемы
- ⚠️ `HomeController` ожидает API `/api/v1/home/stats`, которого нет в `api.php`
- ⚠️ `SettingsController` (Web) использует таблицу `user_settings`, которой нет в миграциях (есть только `settings`)
- ⚠️ Нет сидера для начальных данных (Equipment, Ingredients)
- ⚠️ Не реализована валидация наличия всех ингредиентов перед созданием варки в некоторых случаях

---

## 11. Команды для разработки

```bash
# Запуск
npm run dev          # Vite dev server
php artisan serve    # Laravel server

# Миграции
php artisan migrate
php artisan db:seed

# Тесты
php artisan test
npm run test         # Vitest (если настроен)

# Линтеры
# PHP-CS-Fixer (PHP)
# ESLint + Prettier (JS/Vue)
```

---

## 12. Ключевые файлы

| Файл | Назначение |
|------|------------|
| `routes/api.php` | Все API-эндпоинты |
| `routes/web.php` | Все веб-страницы |
| `resources/js/app.js` | Точка входа Vue, авто-монтирование компонентов |
| `resources/views/layouts/app.blade.php` | Основной layout с боковым меню |
| `database/seeders/DatabaseSeeder.php` | Сидеры (категории, единицы, настройки по умолчанию) |
| `app/Http/Controllers/Api/BrewController.php` | Ключевая логика: FIFO, расчёт стоимости |
| `app/Http/Controllers/Api/SettingController.php` | Импорт/экспорт БД |

---

*Версия сводки: актуальная на дату сканирования*
*Для использования: скопируй этот файл в новый чат для быстрого понимания контекста проекта*
