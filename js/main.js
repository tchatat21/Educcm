/**
 * js/main.js
 * Gestion des interactions dynamiques et des notifications en temps réel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Éléments pour les notifications
    const badge = document.getElementById('notifBadge');
    const toastEl = document.getElementById('liveToast');
    const toastMsg = document.getElementById('toastMessage');

    // On initialise le compteur avec la valeur actuelle du badge
    let lastUnreadCount = badge ? parseInt(badge.innerText) || 0 : 0;

    /**
     * Vérifie si de nouvelles notifications sont arrivées
     */
    function checkNotifications() {
        // Si le badge n'existe pas sur cette page, on ne fait rien
        if (!badge) return;

        // Grâce à la balise <base> dans le header, le chemin est toujours relatif à la racine /
        const apiUrl = 'api/check_notifications.php';
        
        fetch(apiUrl)
            .then(response => response.json())
            .then(data => {
                if (data.error) return;

                if (data.unread_count > 0) {
                    // Mettre à jour le chiffre sur le badge
                    badge.innerText = data.unread_count;
                    badge.style.display = 'block';

                    // Si le nombre a augmenté depuis la dernière vérification, on affiche l'alerte
                    if (data.unread_count > lastUnreadCount) {
                        if (toastEl && toastMsg) {
                            toastMsg.innerHTML = data.last_message;
                            const toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        }
                    }
                } else {
                    // Cacher le badge s'il n'y a plus de messages
                    badge.style.display = 'none';
                }
                
                // Mémoriser le nouveau compte
                lastUnreadCount = data.unread_count;
            })
            .catch(err => console.log('Erreur polling notifications:', err));
    }

    // Si l'utilisateur est un parent (badge présent), on lance la surveillance toutes les 10 secondes
    if (badge) {
        setInterval(checkNotifications, 10000);
    }
});
