<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<!-- Подключение стилей и скриптов -->
<link rel="stylesheet" type="text/css" href="<?= $templateFolder ?>/style.css">
<script src="<?= $templateFolder ?>/script.js"></script>

<div id="dashboard">
    <!-- Фильтр -->
    <div class="filter-container">
        <div class="filter-item">
            <label for="quick-select">Быстрый выбор:</label>
            <select id="quick-select" name="quick-select">
                <option value="today">Текущий день</option>
                <option value="week">Текущая неделя</option>
                <option value="month">Текущий месяц</option>
                <option value="range">Диапазон</option>
            </select>
        </div>
        <div class="filter-item">
            <label for="start-date">От даты:</label>
            <input type="date" id="start-date" name="start-date">
        </div>
        <div class="filter-item">
            <label for="end-date">До даты:</label>
            <input type="date" id="end-date" name="end-date">
        </div>
        <button class="apply-button">Применить</button>
    </div>

    <!-- Вкладки -->
    <div class="tabs">
        <div class="tab" data-tab="general">Общее</div>
        <div class="tab" data-tab="details">Детализация лидов</div>
        <div class="tab" data-tab="distribution">Распределение</div>
    </div>

    <!-- Содержимое вкладок -->
    <div class="tab-content" id="general">
        <h3>Общая информация</h3>
        <ul id="general-content">
            <!-- Данные для "Общее" будут добавлены через JS -->
        </ul>
    </div>

    <div class="tab-content" id="details">
        <h3>Детализация лидов</h3>
        <table id="details-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Дата создания</th>
                <th>Ответственный</th>
                <th>Источник</th>
                <th>Стадия</th>
                <th>Ручное распределение</th>
                <th>Кто распределил</th>
                <th>Дата распределения</th>
            </tr>
            </thead>
            <tbody id="details-tbody">
            <!-- Данные для "Детализация лидов" будут добавлены через JS -->
            </tbody>
        </table>
        <p id="details-leads-count">Всего элементов: 0</p>
    </div>

    <div class="tab-content" id="distribution">
        <h3>Сводная информация по распределению</h3>
        <table id="distribution-summary-table">
            <colgroup>
                <col style="width: 30%;"> <!-- Ширина первого столбца -->
                <col style="width: 15%;"> <!-- Ширина второго столбца -->
                <col style="width: 15%;"> <!-- Ширина третьего столбца -->
                <col style="width: 15%;"> <!-- Ширина четвертого столбца -->
                <col style="width: 15%;"> <!-- Ширина пятого столбца -->
            </colgroup>
            <thead>
            <tr>
                <th>Отдел / Сотрудник</th>
                <th>Всего лидов</th>
                <th>В работе</th>
                <th>Качественных</th>
                <th>Не качественных</th>
            </tr>
            </thead>
            <tbody id="distribution-summary-tbody">
            <!-- Данные будут добавлены через JS -->
            </tbody>
        </table>

        <h3>Детальная информация по распределению</h3>
        <table id="distribution-details-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Дата создания</th>
                <th>Ответственный</th>
                <th>Источник</th>
                <th>Стадия</th>
                <th>Ручное распределение</th>
                <th>Кто распределил</th>
                <th>Дата распределения</th>
            </tr>
            </thead>
            <tbody id="distribution-details-tbody">
            <!-- Данные для детальной таблицы распределения будут добавлены через JS -->
            </tbody>
        </table>

        <p id="distribution-details-count" style="text-align: right; font-weight: bold; margin-top: 10px;">
            Всего элементов: 0
        </p>
    </div>
</div>