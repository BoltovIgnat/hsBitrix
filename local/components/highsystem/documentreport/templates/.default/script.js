function toggleCustomDateInputs() {
    const datePreset = document.getElementById('datePreset').value;
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');

    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
    const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));

    if (datePreset === 'current_month') {
        startDateInput.value = formatDate(firstDayOfMonth);
        endDateInput.value = formatDate(lastDayOfMonth);
        startDateInput.disabled = true;
        endDateInput.disabled = true;
    } else if (datePreset === 'current_week') {
        startDateInput.value = formatDate(startOfWeek);
        endDateInput.value = formatDate(endOfWeek);
        startDateInput.disabled = true;
        endDateInput.disabled = true;
    } else if (datePreset === 'current_day') {
        startDateInput.value = formatDate(new Date());
        endDateInput.value = formatDate(new Date());
        startDateInput.disabled = true;
        endDateInput.disabled = true;
    } else {
        startDateInput.disabled = false;
        endDateInput.disabled = false;
    }
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Инициализация фильтра при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', event => {
        //console.log('Открытие формы');

        // Проверяем, что клик был по кнопке "details-btn"
        if (event.target && event.target.classList.contains('details-btn')) {
			//console.log('Клик по details-btn');
            const button = event.target;
            const userId = button.getAttribute('data-user-id');
			//console.log('userID', userId);
            const row = button.closest('tr'); // Получаем текущую строку
			//console.log('Получаем текущую строку', row);

            // Проверяем, существует ли раскрытая строка для документов
            const nextRow = row.nextElementSibling;
			//console.log('Проверяем наличие раскрытой строки', nextRow);
            if (nextRow && nextRow.classList.contains('details-row')) {
                // Если строка существует, удаляем её
                nextRow.remove();
                button.textContent = '+'; // Меняем текст кнопки обратно
            } else {
                // Создаем строку для отображения документов
                const detailsRow = document.createElement('tr');
				//console.log('Создаем строку', detailsRow);

                detailsRow.className = 'details-row';
                detailsRow.innerHTML = `
                    <td colspan="7" style="padding: 10px; background-color: #f9f9f9;">
                        <table class="documents-table" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Дата и время</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Элемент CRM</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Зафиксировал</th>
                                    <th style="border: 1px solid #ddd; padding: 8px;">Комментарий</th>
                                </tr>
                            </thead>
                            <tbody id="documents-${userId}">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 10px;">Загрузка данных...</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                `;

                row.insertAdjacentElement('afterend', detailsRow); // Добавляем строку после текущей
                button.textContent = '-'; // Меняем текст кнопки

                let ajaxStartDate = document.getElementById('startDate').value;
                let ajaxEndDate = document.getElementById('endDate').value;
                // AJAX-запрос для получения данных
				//console.log('AJAX запрос');
				fetch(`/local/components/highsystem/documentreport/get_documents.php?USER_ID=${userId}&START_DATE=${ajaxStartDate}&END_DATE=${ajaxEndDate}`)
    				.then(response => response.json())
    				.then(data => {
        				//console.log('Полученные данные:', data);
        				const documentsTableBody = document.getElementById(`documents-${userId}`);
        				documentsTableBody.innerHTML = ''; // Очищаем тело таблицы

				        if (data.length > 0) {
				            data.forEach(doc => {
                				documentsTableBody.innerHTML += `
                    				<tr>
                        				<td style="border: 1px solid #ddd; padding: 8px;">${doc.DATE_CREATE}</td>
				                        <td style="border: 1px solid #ddd; padding: 8px;">
                			            	${doc.LINK ? `<a href="${doc.LINK}" target="_blank">${doc.ELEMENT}</a>` : doc.ELEMENT}
                        				</td>
                        				<td style="border: 1px solid #ddd; padding: 8px;">${doc.CHECKER}</td>
				                        <td style="border: 1px solid #ddd; padding: 8px;">${doc.COMMENT}</td>
                				    </tr>`;
            				});
        				} else {
            				documentsTableBody.innerHTML = `
                		<tr>
                    		<td colspan="6" style="text-align: center; padding: 10px;">Документы отсутствуют.</td>
                		</tr>`;
        				}
    				})
    			.catch(error => {
        		console.error('Ошибка загрузки документов:', error);
        		const documentsTableBody = document.getElementById(`documents-${userId}`);
        		documentsTableBody.innerHTML = `
            		<tr>
                		<td colspan="6" style="text-align: center; padding: 10px; color: red;">Ошибка загрузки данных.</td>
            		</tr>`;
			    });
            }
        }
    });
});
