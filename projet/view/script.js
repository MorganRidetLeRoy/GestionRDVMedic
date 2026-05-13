// view/script.js

// Attend que le DOM soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    // Récupère les variables globales définies dans index.php
    const userId = typeof userId !== 'undefined' ? userId : 1;
    const userRole = typeof userRole !== 'undefined' ? userRole : 'patient';

    // Initialise FullCalendar
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch(`controller/AppointmentController.php?action=getSlots&id_medecin=${idMedecin}&date=${date}`)
                .then(response => response.json())
                .then(events => {
                    if (events.success) {
                        successCallback(events.data);
                    } else {
                        failureCallback(new Error(events.error || 'Erreur lors du chargement des événements.'));
                    }
                })
                .catch(error => failureCallback(error));
        },
        eventDidMount: function(info) {
            if (info.event.extendedProps && info.event.extendedProps.statut === 'en_attente') {
                info.el.classList.add('fc-event-waiting');
            } else if (info.event.extendedProps && info.event.extendedProps.statut === 'confirme') {
                info.el.classList.add('fc-event-confirmed');
            } else if (info.event.extendedProps && info.event.extendedProps.statut === 'annule') {
                info.el.classList.add('fc-event-canceled');
            }
        }
    });
    calendar.render();

    // Charge les créneaux disponibles lorsque la date change
    document.getElementById('datePicker').addEventListener('change', async function() {
        const date = this.value;
        const slotsContainer = document.getElementById('slotsContainer');
        const idMedecin = document.getElementById('idMedecin').value;

        if (!date) {
            slotsContainer.innerHTML = '<p>Veuillez choisir une date pour voir les créneaux libres.</p>';
            return;
        }

        slotsContainer.innerHTML = '<p>Chargement des créneaux...</p>';

        try {
            // Chemin relatif vers le contrôleur
            const response = await fetch(`controller/AppointmentController.php?action=getSlots&id_medecin=${idMedecin}&date=${date}`);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            const slots = await response.json();

            if (slots.success) {
                if (slots.data && Array.isArray(slots.data) && slots.data.length > 0) {
                    slotsContainer.innerHTML = '';
                    slots.data.forEach(slot => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'slot-button';

                        // Calcule la durée du créneau (en minutes)
                        const start = new Date(`1970-01-01 ${slot.heure_debut}`);
                        const end = new Date(`1970-01-01 ${slot.heure_fin}`);
                        const duration = (end - start) / (1000 * 60);

                        // Affiche l'heure et la durée
                        button.textContent = `${slot.heure_debut} - ${slot.heure_fin} (${duration} min)`;
                        button.dataset.slotId = slot.id_creneau;
                        slotsContainer.appendChild(button);
                    });
                } else {
                    slotsContainer.innerHTML = '<p>Aucun créneau disponible pour cette date.</p>';
                }
            } else {
                slotsContainer.innerHTML = `<p>Erreur: ${slots.error || 'Impossible de charger les créneaux.'}</p>`;
            }
        } catch (error) {
            console.error("Erreur lors du chargement des créneaux:", error);
            slotsContainer.innerHTML = `<p>Erreur: ${error.message}</p>`;
        }
    });

    // Gère la sélection des créneaux
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('slot-button')) {
            document.querySelectorAll('.slot-button').forEach(button => {
                button.classList.remove('selected');
            });
            e.target.classList.add('selected');
            document.getElementById('submitBtn').disabled = false;
            document.getElementById('selectedSlotId').value = e.target.dataset.slotId;
        }
    });

    // Gère la soumission du formulaire de rendez-vous
    document.getElementById('createAppointmentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const notification = document.getElementById('notification');
        notification.style.display = 'none';

        // Validation du téléphone
        const telephone = document.getElementById('telephone').value;
        if (!/^[0-9]{10}$/.test(telephone)) {
            notification.style.display = 'block';
            notification.textContent = '❌ Le numéro de téléphone doit contenir 10 chiffres.';
            notification.style.backgroundColor = '#f44336';
            notification.style.color = 'white';
            return;
        }

        const formData = new FormData(this);

        try {
            // Chemin relatif vers le contrôleur
            const response = await fetch('controller/AppointmentController.php?action=create', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                notification.style.display = 'block';
                notification.textContent = '✅ Rendez-vous confirmé avec succès !';
                notification.style.backgroundColor = '#4CAF50';
                notification.style.color = 'white';

                // Réinitialise le formulaire
                this.reset();
                document.getElementById('submitBtn').disabled = true;
                document.getElementById('slotsContainer').innerHTML = '<p>Veuillez choisir une date pour voir les créneaux libres.</p>';

                // Rafraîchit le calendrier
                calendar.refetchEvents();
            } else {
                notification.style.display = 'block';
                notification.textContent = '❌ Erreur : ' + (result.error || 'Impossible de confirmer le rendez-vous.');
                notification.style.backgroundColor = '#f44336';
                notification.style.color = 'white';
            }
        } catch (error) {
            notification.style.display = 'block';
            notification.textContent = '❌ Erreur réseau. Veuillez réessayer.';
            notification.style.backgroundColor = '#f44336';
            notification.style.color = 'white';
            console.error('Erreur:', error);
        }
    });

    // Gère la génération de créneaux (pour les médecins/admins)
    if (['medecin', 'admin'].includes(userRole)) {
        const generateSlotsForm = document.getElementById('generateSlotsForm');
        if (generateSlotsForm) {
            generateSlotsForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const notification = document.getElementById('generateSlotsNotification');
                notification.style.display = 'none';

                const formData = new FormData(this);
                const params = new URLSearchParams(formData).toString();

                try {
                    // Chemin relatif vers le contrôleur
                    const response = await fetch(`controller/AppointmentController.php?${params}`);
                    const result = await response.json();

                    notification.style.display = 'block';
                    if (result.success) {
                        notification.textContent = result.message;
                        notification.className = 'success';
                        // Rafraîchit la page pour voir les nouveaux créneaux
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        notification.textContent = 'Erreur: ' + result.error;
                        notification.className = 'error';
                    }
                } catch (error) {
                    notification.style.display = 'block';
                    notification.textContent = 'Erreur réseau: ' + error.message;
                    notification.className = 'error';
                    console.error('Erreur:', error);
                }
            });
        }
    }

    // Gère la recherche de patients
    document.getElementById('searchButton').addEventListener('click', async function() {
        const searchTerm = document.getElementById('searchInput').value.trim();
        if (!searchTerm) {
            alert('Veuillez entrer un terme de recherche.');
            return;
        }

        try {
            // Chemin relatif vers le contrôleur
            const response = await fetch(`controller/AppointmentController.php?action=search&searchTerm=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();

            const appointmentsList = document.getElementById('appointmentsList');
            if (result.success && result.data && result.data.length > 0) {
                appointmentsList.innerHTML = '';
                result.data.forEach(appointment => {
                    const appointmentDiv = document.createElement('div');
                    appointmentDiv.className = 'appointment';
                    appointmentDiv.innerHTML = `
                        <h3>${appointment.nom_patient} ${appointment.prenom_patient}</h3>
                        <p>📞 ${appointment.telephone_patient}</p>
                        <p>📅 Créneau: ${appointment.id_creneau} - Statut: ${appointment.statut}</p>
                    `;
                    appointmentsList.appendChild(appointmentDiv);
                });
            } else {
                appointmentsList.innerHTML = '<p>Aucun rendez-vous trouvé.</p>';
            }
        } catch (error) {
            console.error('Erreur:', error);
            document.getElementById('appointmentsList').innerHTML = '<p>Erreur lors de la recherche.</p>';
        }
    });
});
