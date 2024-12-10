# Медицинская система: Бэкенд на PHP

## Описание проекта

Данный проект представляет собой бэкенд для медицинской системы управления пациентами, осмотрами, консультациями и отчетами. Реализация выполнена на языке PHP с использованием REST API. Система предназначена для врачей и администраторов, обеспечивая удобное взаимодействие с базой данных.

## Основные возможности

- **Работа с пациентами**:
  - Добавление нового пациента.
  - Просмотр данных о пациентах.
  - Редактирование информации о пациентах.
- **Осмотры**:
  - Создание и управление данными об осмотрах.
  - Работа с медицинскими диагнозами.
- **Консультации**:
  - Создание, просмотр и обновление консультаций.
- **Справочники**:
  - Доступ к справочникам, таким как специальности врачей или диагнозы из МКБ-10.
- **Отчёты**:
  - Генерация отчётов по пациентам, диагнозам и активности врачей.

## Структура проекта

- **`index.php`**: Главная точка входа в приложение.
- **`api/`**: Каталог с реализацией REST API:
  - `consultation.php`: Управление консультациями.
  - `dictionary.php`: Управление справочниками диагнозов мкб-10 и специальностей.
  - `inspection.php`: Управление осмотрами.
  - `patient.php`: Управление пациентами.
  - `report.php`: Генерация отчетов.
- **`helpers/`**: Вспомогательные функции и классы для обработки данных и взаимодействия с базой данных.
- **`.htaccess`**: Настройка маршрутизации для API.

## Используемые технологии

- **PHP**: Основной язык реализации.
- **phpMyAdmin**: база данных.
- **REST API**: Для взаимодействия фронтенда и бэкенда.

## Установка и запуск

1. Клонируйте репозиторий:
   ```bash
   git clone https://github.com/VictoriasCloud/webPHP-Hits-backend-php-project-2024-1.git
   ```

2. Перенесите проект на ваш локальный сервер (например, Open Server Panel):
   - Поместите файлы проекта в папку вашего веб-сервера

3. Запустите локальный сервер, теперь можете тестировать приложение. данные для подключения к бд лежат в проекте.
