// assets/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // Exemple : Ajouter un effet de fade-in aux alertes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000); // Disparaît après 5 secondes
    });

    // Exemple : Confirmer la suppression
    const deleteButtons = document.querySelectorAll('a[onclick*="confirm"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm') || 'Êtes-vous sûr ?')) {
                e.preventDefault();
            }
        });
    });
});