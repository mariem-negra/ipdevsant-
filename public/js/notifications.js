document.addEventListener('DOMContentLoaded', function() {
    // Éléments du DOM existants
    const notificationsButton = document.getElementById('notificationsButton');
    const notificationsModal = document.getElementById('notificationsModal');
    const closeModal = document.querySelector('.close-modal');
    const notificationsList = document.getElementById('notificationsList');
    const notificationCount = document.getElementById('notificationCount');

    // Stockage pour les notifications à afficher
    let activeNotifications = [];
    let pendingNotifications = [];
    let notificationTimers = {};

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
        if (notificationCount) {
            notificationCount.textContent = count;
            notificationCount.style.display = count > 0 ? 'inline' : 'none';
        }
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
        if (!notificationsList) return;
        
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
            
            // Mettre à jour les notifications en attente
            updatePendingNotifications(data);
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
            if (notificationsList) {
                notificationsList.innerHTML = '<p class="error">Erreur lors du chargement des notifications</p>';
            }
        }
    }

    // Fonction pour mettre à jour les notifications en attente
    function updatePendingNotifications(reminders) {
        // Effacer les timers existants
        Object.keys(notificationTimers).forEach(id => {
            clearTimeout(notificationTimers[id]);
            delete notificationTimers[id];
        });
        
        pendingNotifications = [];
        
        reminders.forEach(reminder => {
            const eventTime = new Date(reminder.dateTime).getTime();
            const now = new Date().getTime();
            const notifyBefore = parseInt(reminder.notifyBefore) * 60 * 1000; // Convertir en millisecondes
            
            // Calculer le moment où la notification doit être affichée
            const notifyTime = eventTime - notifyBefore;
            
            // Si le temps de notification est dans le futur, planifier la notification
            if (notifyTime > now) {
                const timeUntilNotification = notifyTime - now;
                
                // Ajouter à la liste des notifications en attente
                pendingNotifications.push({
                    id: reminder.id,
                    title: reminder.title,
                    dateTime: reminder.dateTime,
                    notifyAt: notifyTime
                });
                
                // Planifier la notification
                notificationTimers[reminder.id] = setTimeout(() => {
                    showBrowserNotification(reminder);
                }, timeUntilNotification);
            }
        });
    }
    
    // Fonction pour afficher une notification du navigateur
    function showBrowserNotification(reminder) {
        // Vérifier si les notifications sont supportées et autorisées
        if (!("Notification" in window)) {
            console.warn("Ce navigateur ne prend pas en charge les notifications de bureau");
            return;
        }
        
        // Demander la permission si nécessaire
        if (Notification.permission !== "granted") {
            Notification.requestPermission();
        }
        
        // Créer et afficher la notification
        if (Notification.permission === "granted") {
            const eventTime = new Date(reminder.dateTime);
            const options = {
                body: `Rappel pour: ${reminder.title} à ${eventTime.toLocaleTimeString('fr-FR', {
                    hour: '2-digit',
                    minute: '2-digit'
                })}`,
                icon: '/assets/img/siren-on.png', // Remplacer par le chemin de votre icône
                tag: `reminder-${reminder.id}`
            };
            
            const notification = new Notification("Rappel de médicament", options);
            
            // Ajouter à la liste des notifications actives
            activeNotifications.push(reminder.id);
            
            // Mettre à jour le compteur de notifications actives
            updateActiveNotificationCount();
            
            // Clic sur la notification ouvre la modal
            notification.onclick = function() {
                window.focus();
                notificationsModal.style.display = 'block';
                
                // Marquer comme vue
                removeActiveNotification(reminder.id);
            };
        }
    }
    
    // Fonctions pour gérer les notifications actives
    function updateActiveNotificationCount() {
        updateNotificationCount(activeNotifications.length);
    }
    
    function removeActiveNotification(id) {
        const index = activeNotifications.indexOf(id);
        if (index > -1) {
            activeNotifications.splice(index, 1);
            updateActiveNotificationCount();
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

            if (response.ok) {
                // Supprimer le timer si existant
                if (notificationTimers[id]) {
                    clearTimeout(notificationTimers[id]);
                    delete notificationTimers[id];
                }
                
                // Supprimer des notifications actives si présent
                removeActiveNotification(id);
                
                // Recharger les notifications
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

    // Demander l'autorisation pour les notifications dès le chargement
    function requestNotificationPermission() {
        if ("Notification" in window && Notification.permission !== "granted") {
            Notification.requestPermission();
        }
    }

    // Event listeners
    if (notificationsButton) {
        notificationsButton.addEventListener('click', () => {
            loadNotifications();
            notificationsModal.style.display = 'block';
        });
    }

    if (closeModal) {
        closeModal.addEventListener('click', () => {
            notificationsModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === notificationsModal) {
            notificationsModal.style.display = 'none';
        }
    });

    // Fonction pour vérifier les rappels imminents
    function checkUpcomingReminders() {
        const now = new Date();
        
        // Parcourir les notifications en attente
        pendingNotifications.forEach(notification => {
            const notifyTime = new Date(notification.notifyAt);
            
            // Si c'est l'heure de notifier
            if (now >= notifyTime) {
                // Trouver le rappel complet correspondant
                loadNotifications().then(data => {
                    const reminder = data.find(r => r.id === notification.id);
                    if (reminder) {
                        showBrowserNotification(reminder);
                    }
                });
            }
        });
    }

    // Initialisation
    requestNotificationPermission();
    loadNotifications();
    
    // Vérifier les rappels toutes les minutes
    setInterval(checkUpcomingReminders, 60000);
    
    // Exposer la fonction deleteNotification au scope global pour les boutons
    window.deleteNotification = deleteNotification;
});