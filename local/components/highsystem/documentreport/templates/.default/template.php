<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

    <!-- Подключение внешнего JS -->
    <script src="/local/components/highsystem/documentreport/templates/.default/script.js"></script>
    <!-- Подключение внешнего CSS -->
    <link rel="stylesheet" href="/local/components/highsystem/documentreport/templates/.default/style.css">

    <!-- Форма фильтра -->
    <form id="filterForm" method="GET" class="filter-form">
        <div class="filter-group filter-group-select">
            <label for="datePreset">Выбор даты:</label>
            <select id="datePreset" name="DATE_PRESET" onchange="toggleCustomDateInputs()">
                <option value="current_month" <?=($arResult['FILTER']['DATE_PRESET'] === 'current_month' ? 'selected' : '')?>>Текущий месяц</option>
                <option value="current_week" <?=($arResult['FILTER']['DATE_PRESET'] === 'current_week' ? 'selected' : '')?>>Текущая неделя</option>
                <option value="current_day" <?=($arResult['FILTER']['DATE_PRESET'] === 'current_day' ? 'selected' : '')?>>Текущий день</option>
                <option value="custom" <?=($arResult['FILTER']['DATE_PRESET'] === 'custom' ? 'selected' : '')?>>Диапазон</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="startDate">Дата начала:</label>
            <input type="date" id="startDate" name="START_DATE" value="<?=htmlspecialchars($arResult['FILTER']['START_DATE'] ?? '')?>">
        </div>
        <div class="filter-group">
            <label for="endDate">Дата конца:</label>
            <input type="date" id="endDate" name="END_DATE" value="<?=htmlspecialchars($arResult['FILTER']['END_DATE'] ?? '')?>">
        </div>

        <div class="filter-group filter-group-select">
            <label for="department">Подразделение:</label>
            <select id="department" name="DEPARTMENT">
                <option value="" <?=($arResult['FILTER']['DEPARTMENT'] === '' ? 'selected' : '')?>>Все подразделения</option>
                <?php foreach ($arResult['DEPARTMENTS'] as $department): ?>
                    <option value="<?= htmlspecialchars($department['ID']) ?>" <?=($arResult['FILTER']['DEPARTMENT'] === (string)$department['ID'] ? 'selected' : '')?>>
                        <?= htmlspecialchars($department['NAME']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-button">
            <button type="submit">Применить</button>
        </div>
    </form>

    <!-- Таблица данных -->
<?php if (!empty($arResult['DEPARTMENTS_WITH_USERS'])): ?>
    <?php foreach ($arResult['DEPARTMENTS_WITH_USERS'] as $department): ?>
        <div class="table-wrapper">
            <h3 class="department-title"><?= htmlspecialchars($department['NAME_DEPART']) ?></h3>
            <table class="report-table">
                <thead>
                <tr>
                    <th>Сотрудник</th>
                    <th>Контакт</th>
                    <th>Компания</th>
                    <th>Сделки</th>
                    <th>Проекты</th>
                    <th>Всего ошибок</th>
                    <th>Подробнее</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $total = 0;
// writeLog('report', "Данные для отчета", $department);

                foreach ($department['EMPLOYEE'] as $employee):
                    $employeeTotal = intval($employee['DOCUMENT_STATS']['CONTACT'] ?? 0) +
                        intval($employee['DOCUMENT_STATS']['COMPANY'] ?? 0) +
                        intval($employee['DOCUMENT_STATS']['DEALS'] ?? 0) +
                        intval($employee['DOCUMENT_STATS']['PROJECTS'] ?? 0);
                    $total += $employeeTotal;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($employee['NAME'] . ' ' . $employee['LAST_NAME']) ?></td>
                        <td><?= intval($employee['DOCUMENT_STATS']['CONTACT'] ?? 0) ?></td>
                        <td><?= intval($employee['DOCUMENT_STATS']['COMPANY'] ?? 0) ?></td>
                        <td><?= intval($employee['DOCUMENT_STATS']['DEALS'] ?? 0) ?></td>
                        <td><?= intval($employee['DOCUMENT_STATS']['PROJECTS'] ?? 0) ?></td>
                        <td><?= $employeeTotal ?></td>
                        <td style="text-align: center;">
                            <?php if (!empty($employee['DOCUMENTS'])): ?>
                                <button class="details-btn" data-user-id="<?= intval($employee['ID']) ?>">+</button>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="details-row" id="details-row-<?= intval($employee['ID']) ?>" style="display: none;">
                        <td colspan="7">
                            <table class="documents-table">
                                <thead>
                                <tr>
                                    <th>Дата и время</th>
                                    <th>Элемент CRM</th>
                                    <th>Зафиксировал</th>
                                    <th>Комментарий</th>
                                </tr>
                                </thead>
                                <tbody id="documents-<?= intval($employee['ID']) ?>">
                                <tr>
                                    <td colspan="4" style="text-align: center;">Загрузка данных...</td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>Итого:</td>
                    <td colspan="4"></td>
                    <td><?= $total ?></td>
                    <td></td>
                </tr>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Нет данных для отображения.</p>
<?php endif; ?>
