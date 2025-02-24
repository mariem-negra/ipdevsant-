document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM
    const notificationsButton = document.getElementById('notificationsButton');
    const notificationsModal = document.getElementById('notificationsModal');
    const closeModal = document.querySelector('.close-modal');
    const notificationsList = document.getElementById('notificationsList');
    const notificationCount = document.getElementById('notificationCount');

    // Fonction pour formater la date
    const formatDate = (dateString) => {
        const date = new Date(dateString);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const notifDate = new Date(date);
        notifDate.setHours(0, 0, 0, 0);

        if (notifDate.getTime() === today.getTime()) {
            return "Aujourd'hui";
        }

        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    // Fonction pour mettre à jour le compteur de notifications
    function updateNotificationCount(count) {
        notificationCount.textContent = count;
        notificationCount.style.display = count > 0 ? 'inline' : 'none';
    }

    // Fonction pour créer un élément de notification
    const createNotificationItem = (notification) => {
        const time = new Date(notification.dateTime).toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit'
        });

        const div = document.createElement('div');
        div.className = 'notification-item';
        div.innerHTML = `
            <div class="notification-content">
                <div class="notification-time">${time}</div>
                <div class="notification-text">${notification.title}</div>
            </div>
            <div class="notification-actions">
                <button class="edit-btn" onclick="location.href='/reminder/editReminder/${notification.id}'">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="delete-btn" data-id="${notification.id}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        // Ajout de l'écouteur d'événement pour la suppression
        const deleteBtn = div.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', () => deleteNotification(notification.id));

        return div;
    };

    // Fonction pour mettre à jour la liste des notifications
    function updateNotificationsList(notifications) {
        notificationsList.innerHTML = '';
        
        if (!notifications || notifications.length === 0) {
            notificationsList.innerHTML = '<p class="no-notifications">Aucun rappel à venir</p>';
            return;
        }

        // Grouper les notifications par date
        const groupedNotifications = {};
        notifications.forEach(notification => {
            const dateKey = formatDate(notification.dateTime);
            if (!groupedNotifications[dateKey]) {
                groupedNotifications[dateKey] = [];
            }
            groupedNotifications[dateKey].push(notification);
        });

        // Trier les dates
        const sortedDates = Object.keys(groupedNotifications).sort((a, b) => {
            if (a === "Aujourd'hui") return -1;
            if (b === "Aujourd'hui") return 1;
            return new Date(a) - new Date(b);
        });

        // Créer les sections pour chaque date
        sortedDates.forEach(date => {
            const dateSection = document.createElement('div');
            dateSection.className = 'date-section';
            
            const dateHeader = document.createElement('h3');
            dateHeader.className = 'date-header';
            dateHeader.textContent = date;
            dateSection.appendChild(dateHeader);

            // Trier les notifications par heure
            const sortedNotifications = groupedNotifications[date].sort((a, b) => 
                new Date(a.dateTime) - new Date(b.dateTime)
            );

            sortedNotifications.forEach(notification => {
                dateSection.appendChild(createNotificationItem(notification));
            });

            notificationsList.appendChild(dateSection);
        });
    }

    // Fonction pour charger les notifications
    async function loadNotifications() {
        try {
            const response = await fetch('/reminder/notifications-json');
            if (!response.ok) throw new Error('Erreur réseau');
            
            const data = await response.json();
            updateNotificationsList(data);
            updateNotificationCount(data.length);
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
            notificationsList.innerHTML = '<p class="error">Erreur lors du chargement des notifications</p>';
        }
    }

    // Fonction de suppression
    async function deleteNotification(id) {
        if (!confirm('Voulez-vous vraiment supprimer ce rappel ?')) return;

        try {
            const response = await fetch(`/reminder/delete/${id}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            });

            // Si la réponse est OK, on recharge sans afficher d'erreur
            if (response.ok) {
                await loadNotifications();
                return;
            }

            // Tente de récupérer les détails de l'erreur si disponible
            const data = await response.json().catch(() => ({}));
            if (data.success) {
                await loadNotifications();
                return;
            }

            throw new Error(data.message || 'Erreur lors de la suppression');
        } catch (error) {
            console.error('Erreur:', error);
            // Affiche l'erreur seulement si la suppression a vraiment échoué
            if (document.querySelector(`[data-id="${id}"]`)) {
                alert('Erreur lors de la suppression du rappel');
            }
        }
    }

    // Event listeners
    notificationsButton.addEventListener('click', () => {
        loadNotifications();
        notificationsModal.style.display = 'block';
    });

    closeModal.addEventListener('click', () => {
        notificationsModal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target === notificationsModal) {
            notificationsModal.style.display = 'none';
        }
    });

    // Chargement initial et rafraîchissement périodique
    loadNotifications();
    setInterval(loadNotifications, 60000); // Rafraîchit toutes les minutes
});