document.addEventListener('DOMContentLoaded', function() {
    const grid = document.querySelector('.calendar-grid');
    const now = new Date();
    let currentMonth = now.getMonth();
    let currentYear = now.getFullYear();
    let selectedDate = null;

    function formatDate(date) {
        return date.toLocaleDateString('fr-FR', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
        });
    }

    function initializeCalendar() {
        updateCalendarHeader();
        renderCalendar();
        loadReminders();
    }

    function updateCalendarHeader() {
        const monthNames = ['JANV.', 'FÉVR.', 'MARS', 'AVR.', 'MAI', 'JUIN', 'JUIL.', 'AOÛT', 'SEPT.', 'OCT.', 'NOV.', 'DÉC.'];
        document.querySelector('.month-title').textContent = `${monthNames[currentMonth]} ${currentYear}`;
    }

    function renderCalendar() {
        grid.innerHTML = '';
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        let dayOfWeek = firstDay.getDay() || 7;

        for (let i = dayOfWeek - 1; i > 0; i--) {
            addDayCell(new Date(firstDay.getFullYear(), firstDay.getMonth(), firstDay.getDate() - i), true);
        }
        for (let i = 1; i <= lastDay.getDate(); i++) {
            addDayCell(new Date(currentYear, currentMonth, i));
        }
        const remainingCells = 42 - (dayOfWeek - 1 + lastDay.getDate());
        for (let i = 1; i <= remainingCells; i++) {
            addDayCell(new Date(lastDay.getFullYear(), lastDay.getMonth(), lastDay.getDate() + i), true);
        }
    }

    function addDayCell(date, isOtherMonth = false) {
        const cell = document.createElement('div');
        cell.className = `day-cell${isOtherMonth ? ' other-month' : ''}${date.toDateString() === now.toDateString() ? ' today' : ''}`;
        cell.textContent = date.getDate();
        cell.dataset.date = date.toISOString().split('T')[0];
        cell.addEventListener('click', () => openReminderModal(date));
        grid.appendChild(cell);
    }

    function openReminderModal(date) {
        selectedDate = date;
        const modal = document.createElement('div');
        modal.className = 'reminder-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Nouveau rappel</h2>
                    <div class="modal-date">${formatDate(date)}</div>
                </div>
                <form id="reminderForm" class="reminder-form">
                    <label>Médicament</label>
                    <input type="text" id="title" name="title" required>
                    <label>Heure</label>
                    <input type="time" id="time" name="time" required>
                    <label>Rappel avant</label>
                    <select id="notifyBefore" name="notifyBefore">
                        <option value="1">1 minute</option>
                        <option value="5">5 minutes</option>
                        <option value="15">15 minutes</option>
                        <option value="30">30 minutes</option>
                        <option value="60">1 heure</option>
                        <option value="1440">1 jour</option>
                    </select>
                    <label>Répétition</label>
                    <select id="repeatType" name="repeatType">
                        <option value="none">Pas de répétition</option>
                        <option value="daily">Chaque jour</option>
                        <option value="weekly">Chaque semaine</option>
                        <option value="monthly">Chaque mois</option>
                    </select>
                    <button type="button" class="btn-cancel">Annuler</button>
                    <button type="submit" class="btn-save">Enregistrer</button>
                </form>
            </div>`;
        document.body.appendChild(modal);
        modal.querySelector('.btn-cancel').addEventListener('click', () => modal.remove());
        modal.querySelector('#reminderForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = {
                title: formData.get('title'),
                time: formData.get('time'),
                date: selectedDate.toISOString().split('T')[0],
                notifyBefore: formData.get('notifyBefore'),
                repeatType: 'none'
                
            };
            
            try {
                const response = await fetch('/reminder/new-ajax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                if (result.success) {
                    modal.remove();
                    await loadReminders();
                } else {
                    alert(result.message || 'Erreur lors de la création du rappel');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la création du rappel');
            }
        });
    }

    async function loadReminders() {
        try {
            const response = await fetch(`/reminder/api/reminders?month=${currentMonth + 1}&year=${currentYear}`);
            const reminders = await response.json();
            document.querySelectorAll('.day-cell').forEach(cell => cell.classList.remove('has-event'));
            reminders.forEach(reminder => {
                const reminderDate = new Date(reminder.start).toISOString().split('T')[0];
                const cell = document.querySelector(`.day-cell[data-date="${reminderDate}"]`);
                if (cell) cell.classList.add('has-event');
            });
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    document.querySelector('.prev-month').addEventListener('click', () => {
        currentMonth = currentMonth === 0 ? 11 : currentMonth - 1;
        currentYear = currentMonth === 11 ? currentYear - 1 : currentYear;
        initializeCalendar();
    });

    document.querySelector('.next-month').addEventListener('click', () => {
        currentMonth = currentMonth === 11 ? 0 : currentMonth + 1;
        currentYear = currentMonth === 0 ? currentYear + 1 : currentYear;
        initializeCalendar();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') document.querySelector('.prev-month').click();
        if (e.key === 'ArrowRight') document.querySelector('.next-month').click();
    });

    initializeCalendar();
    setInterval(loadReminders, 60000);
});