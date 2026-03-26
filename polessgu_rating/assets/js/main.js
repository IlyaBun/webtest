/**
 * Основной JavaScript файл системы
 */

// Переключение боковой панели на мобильных устройствах
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

// Закрытие модального окна
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Открытие модального окна
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

// Подтверждение удаления
function confirmDelete(message, url) {
    if (confirm(message || 'Вы уверены, что хотите удалить эту запись?')) {
        window.location.href = url;
    }
}

// Инициализация графиков
function initCharts() {
    // График распределения оценок
    const gradeChartCtx = document.getElementById('gradeDistributionChart');
    if (gradeChartCtx) {
        new Chart(gradeChartCtx, {
            type: 'doughnut',
            data: {
                labels: ['Отлично (9-10)', 'Хорошо (7-8)', 'Удовл. (5-6)', 'Неудовл. (3-4)'],
                datasets: [{
                    data: gradeChartCtx.dataset.data ? JSON.parse(gradeChartCtx.dataset.data) : [25, 35, 30, 10],
                    backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // График успеваемости по группам
    const groupChartCtx = document.getElementById('groupPerformanceChart');
    if (groupChartCtx) {
        new Chart(groupChartCtx, {
            type: 'bar',
            data: {
                labels: groupChartCtx.dataset.labels ? JSON.parse(groupChartCtx.dataset.labels) : ['МП-11', 'МП-21', 'МП-31', 'МП-41'],
                datasets: [{
                    label: 'Средний балл',
                    data: groupChartCtx.dataset.data ? JSON.parse(groupChartCtx.dataset.data) : [7.5, 8.2, 7.8, 8.5],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10
                    }
                }
            }
        });
    }

    // График динамики успеваемости
    const dynamicsChartCtx = document.getElementById('dynamicsChart');
    if (dynamicsChartCtx) {
        new Chart(dynamicsChartCtx, {
            type: 'line',
            data: {
                labels: ['Сен', 'Окт', 'Ноя', 'Дек', 'Янв', 'Фев'],
                datasets: [{
                    label: 'Средний балл',
                    data: dynamicsChartCtx.dataset.data ? JSON.parse(dynamicsChartCtx.dataset.data) : [7.2, 7.4, 7.6, 7.8, 7.9, 8.0],
                    borderColor: '#667eea',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(102, 126, 234, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 5,
                        max: 10
                    }
                }
            }
        });
    }
}

// Автозапуск при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    
    // Плавное появление элементов
    const cards = document.querySelectorAll('.stat-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s, transform 0.5s';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Поиск по таблице
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    
    const filter = input.value.toUpperCase();
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let showRow = false;
        const td = tr[i].getElementsByTagName('td');
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    showRow = true;
                    break;
                }
            }
        }
        tr[i].style.display = showRow ? '' : 'none';
    }
}

// Фильтрация по группе
function filterByGroup(selectId, tableId, columnIndex) {
    const select = document.getElementById(selectId);
    const table = document.getElementById(tableId);
    if (!select || !table) return;
    
    const filter = select.value.toUpperCase();
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[columnIndex];
        if (td) {
            const txtValue = td.textContent || td.innerText;
            tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }
}

// Экспорт таблицы в CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length - 1; j++) { // Исключаем колонку действий
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        
        csv.push(row.join(','));
    }
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Валидация формы
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.style.borderColor = '#e1e1e1';
        }
    });
    
    return isValid;
}

// Уведомления
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transition = 'opacity 0.5s';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// AJAX запросы
async function ajaxRequest(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

// Форматирование даты
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
}

// Получение цвета для оценки
function getGradeColor(grade) {
    if (grade >= 9) return '#28a745';
    if (grade >= 7) return '#17a2b8';
    if (grade >= 5) return '#ffc107';
    return '#dc3545';
}
