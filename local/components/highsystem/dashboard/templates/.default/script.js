document.addEventListener('DOMContentLoaded', () => {
    const quickSelect = document.getElementById('quick-select');
    const startDateField = document.getElementById('start-date');
    const endDateField = document.getElementById('end-date');
    const applyButton = document.querySelector('.apply-button');
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    let currentLeads = [];
    let currentType = 'details';

    // Переключение вкладок
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));

            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).classList.add('active');
            currentType = tab.dataset.tab;
            fetchLeads(currentType);
        });
    });

    // Обработчик изменения быстрого фильтра
    quickSelect.addEventListener('change', () => {
        const today = new Date();
        let startDate, endDate;

        if (quickSelect.value === 'today') {
            startDate = endDate = formatDate(today);
        } else if (quickSelect.value === 'week') {
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - today.getDay() + 1);
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            startDate = formatDate(startOfWeek);
            endDate = formatDate(endOfWeek);
        } else if (quickSelect.value === 'month') {
            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            startDate = formatDate(startOfMonth);
            endDate = formatDate(endOfMonth);
        } else {
            startDate = '';
            endDate = '';
        }

        startDateField.value = startDate || '';
        endDateField.value = endDate || '';
        startDateField.disabled = quickSelect.value !== 'range';
        endDateField.disabled = quickSelect.value !== 'range';
    });

    // Обработчик кнопки "Применить"
    applyButton.addEventListener('click', () => {
        console.log('Нажали на применить');
        fetchLeads(currentType);
    });

    // AJAX-запрос для получения данных
    function fetchLeads(type) {
        const startDate = startDateField.value;
        const endDate = endDateField.value;

        console.log('startDate ', startDate);
        console.log('endDate ', endDate);

        fetch('/local/tools/dashboard.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                'type': type,
                'start-date': startDate,
                'end-date': endDate,
            }),
        })
            .then(response => {
                console.log('Ответ получен, статус:', response.status); // Проверка HTTP-статуса
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    currentLeads = data.data;
                    if (type === 'details') {
                        renderDetailsTable(currentLeads);
                    } else if (type === 'distribution') {
                        renderDistributionSummary(currentLeads);
                        renderDistributionTable(currentLeads);
                    }
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => console.error('Ошибка сети:', error));
    }

    // Форматирование даты в строку YYYY-MM-DD
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Отрисовка таблицы для вкладки "Детализация лидов"
    function renderDetailsTable(leads) {
        const tbody = document.querySelector('#details-tbody');
        const countContainer = document.getElementById('details-leads-count');
        tbody.innerHTML = '';

        if (leads.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">Нет данных для отображения.</td></tr>`;
            countContainer.textContent = `Всего элементов: 0`;
            return;
        }

        leads.forEach(lead => {
            const leadUrl = lead.ID
                ? `https://dev.highsystem.ru/crm/lead/details/${lead.ID}/`
                : "#";
            const assignedByUrl = lead.ASSIGNED_BY_ID
                ? `https://dev.highsystem.ru/company/personal/user/${lead.ASSIGNED_BY_ID}/`
                : "#";
            const whoDistributedUrl = lead.WHO_DISTRIBUTED_ID
                ? `https://dev.highsystem.ru/company/personal/user/${lead.WHO_DISTRIBUTED_ID}/`
                : "#";

            const row = `
            <tr>
                <td><a href="${leadUrl}" target="_blank" rel="noopener noreferrer">${lead.ID || "—"}</a></td>
                <td>${lead.TITLE || "—"}</td>
                <td>${lead.DATE_CREATE || "—"}</td>
                <td><a href="${assignedByUrl}" target="_blank" rel="noopener noreferrer">${lead.ASSIGNED_BY_NAME || "—"}</a></td>
                <td>${lead.SOURCE_NAME || "—"}</td>
                <td>${lead.STATUS_NAME || "—"}</td>
                <td>${lead.MANUAL_DISTRIBUTION || "—"}</td>
                <td><a href="${whoDistributedUrl}" target="_blank" rel="noopener noreferrer">${lead.WHO_DISTRIBUTED_NAME || "—"}</a></td>
                <td>${lead.DISTRIBUTION_DATE || "—"}</td>
            </tr>
        `;
            tbody.innerHTML += row;
        });

        countContainer.textContent = `Всего элементов: ${leads.length}`;
    }

    // Отрисовка сводной таблицы с учётом стилизации
    function renderDistributionSummary(leads) {
        const summaryContainer = document.getElementById('distribution-summary-tbody');
        summaryContainer.innerHTML = '';

        if (leads.length === 0) {
            summaryContainer.innerHTML = `<tr><td colspan="5">Нет данных для отображения.</td></tr>`;
            return;
        }

        const departmentMap = {};
        const totals = { total: 0, inWork: 0, quality: 0, notQuality: 0 };

        leads.forEach(lead => {
            const departmentName = lead.DEPARTMENT_NAME || 'Неизвестный отдел';
            const assignedByName = lead.ASSIGNED_BY_NAME || 'Неизвестный';
            const semantics = lead.STATUS_SEMANTICS || '';

            if (!departmentMap[departmentName]) {
                departmentMap[departmentName] = {
                    total: 0,
                    inWork: 0,
                    quality: 0,
                    notQuality: 0,
                    employees: {},
                };
            }

            if (!departmentMap[departmentName].employees[assignedByName]) {
                departmentMap[departmentName].employees[assignedByName] = {
                    total: 0,
                    inWork: 0,
                    quality: 0,
                    notQuality: 0,
                };
            }

            departmentMap[departmentName].total++;
            departmentMap[departmentName].employees[assignedByName].total++;
            totals.total++;

            if (semantics === '') {
                departmentMap[departmentName].inWork++;
                departmentMap[departmentName].employees[assignedByName].inWork++;
                totals.inWork++;
            } else if (semantics === 'S') {
                departmentMap[departmentName].quality++;
                departmentMap[departmentName].employees[assignedByName].quality++;
                totals.quality++;
            } else if (semantics === 'F') {
                departmentMap[departmentName].notQuality++;
                departmentMap[departmentName].employees[assignedByName].notQuality++;
                totals.notQuality++;
            }
        });

        for (const departmentName in departmentMap) {
            const departmentData = departmentMap[departmentName];

            const departmentRow = document.createElement('tr');
            departmentRow.classList.add('department-row');
            departmentRow.innerHTML = `
            <td><strong>${departmentName}</strong></td>
            <td>${departmentData.total}</td>
            <td>${departmentData.inWork}</td>
            <td>${departmentData.quality}</td>
            <td>${departmentData.notQuality}</td>
        `;
            summaryContainer.appendChild(departmentRow);

            for (const employeeName in departmentData.employees) {
                const employeeData = departmentData.employees[employeeName];
                const employeeRow = document.createElement('tr');
                employeeRow.innerHTML = `
                <td>${employeeName}</td>
                <td>${employeeData.total}</td>
                <td>${employeeData.inWork}</td>
                <td>${employeeData.quality}</td>
                <td>${employeeData.notQuality}</td>
            `;
                summaryContainer.appendChild(employeeRow);
            }
        }

        const totalsRow = document.createElement('tr');
        totalsRow.classList.add('total-row');
        totalsRow.innerHTML = `
        <td><strong>Всего</strong></td>
        <td>${totals.total}</td>
        <td>${totals.inWork}</td>
        <td>${totals.quality}</td>
        <td>${totals.notQuality}</td>
    `;
        summaryContainer.appendChild(totalsRow);
    }

    function renderDistributionTable(leads) {
        const tbody = document.querySelector('#distribution-details-tbody');
        const countContainer = document.getElementById('distribution-details-count');
        tbody.innerHTML = ''; // Очищаем таблицу

        if (leads.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">Нет данных для отображения.</td></tr>`;
            countContainer.textContent = 'Всего элементов: 0';
            return;
        }

        leads.forEach(lead => {
            const row = `
            <tr>
                <td>${lead.ID || "—"}</td>
                <td>${lead.TITLE || "—"}</td>
                <td>${lead.DATE_CREATE || "—"}</td>
                <td>${lead.ASSIGNED_BY_NAME || "—"}</td>
                <td>${lead.SOURCE_NAME || "—"}</td>
                <td>${lead.STATUS_NAME || "—"}</td>
                <td>${lead.MANUAL_DISTRIBUTION || "—"}</td>
                <td>${lead.WHO_DISTRIBUTED_NAME || "—"}</td>
                <td>${lead.DISTRIBUTION_DATE || "—"}</td>
            </tr>
        `;
            tbody.innerHTML += row;
        });

        // Обновляем общее количество элементов
        countContainer.textContent = `Всего элементов: ${leads.length}`;
    }

    // Инициализация
    quickSelect.dispatchEvent(new Event('change'));
    fetchLeads(currentType);
});