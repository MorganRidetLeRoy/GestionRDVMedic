// view/script.js
document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialisation de FullCalendar
    const calendarEl = document.getElementById('calendar');
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek', // Vue par semaine avec les heures
        locale: 'fr', // En français
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        slotMinTime: '08:00:00', // Heure de début du planning
        slotMaxTime: '19:00:00', // Heure de fin
        allDaySlot: false,
        hiddenDays: [0], // Cacher le dimanche
        
        // 2. Récupération des événements depuis ton contrôleur
        events: '../controller/AppointmentController.php?action=getCalendarEvents&id_medecin=1',
        
        // 3. Gestion de la suppression (Annulation) au clic sur un événement
        eventClick: function(info) {
            if (confirm("Voulez-vous annuler le rendez-vous de " + info.event.title + " ?")) {
                cancelAppointment(info.event.id, info.event);
            }
        }
    });

    calendar.render();

    // 4. Gestion du formulaire de création
    document.getElementById('createAppointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../controller/AppointmentController.php?action=create', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rendez-vous créé avec succès!');
                this.reset();
                calendar.refetchEvents(); // Met à jour l'agenda dynamiquement
            } else {
                alert('Erreur lors de la création du rendez-vous.');
            }
        });
    });

    // 5. Fonction d'annulation mise à jour
    function cancelAppointment(appointmentId, eventObj) {
        fetch(`../controller/AppointmentController.php?action=cancel&appointmentId=${appointmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Rendez-vous annulé avec succès!');
                eventObj.remove(); // Retire l'événement de l'agenda visuellement
            } else {
                alert("Erreur lors de l'annulation.");
            }
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
    const datePicker = document.getElementById('datePicker');
    const slotsContainer = document.getElementById('slotsContainer');
    const selectedSlotInput = document.getElementById('selectedSlotId');
    const submitBtn = document.getElementById('submitBtn');

    // 1. Écouter le changement de date
    datePicker.addEventListener('change', function() {
        const selectedDate = this.value;
        const idMedecin = 1; // Id du médecin (dynamique si tu as plusieurs médecins)

        if (selectedDate) {
            fetch(`../controller/AppointmentController.php?action=getSlots&id_medecin=${idMedecin}&date=${selectedDate}`)
            .then(response => response.json())
            .then(slots => {
                slotsContainer.innerHTML = ''; // Vider les anciens créneaux
                
                if (slots.length === 0) {
                    slotsContainer.innerHTML = '<p style="color:red;">Aucun créneau disponible pour cette date.</p>';
                    submitBtn.disabled = true;
                } else {
                    slots.forEach(slot => {
                        const btn = document.createElement('div');
                        btn.classList.add('slot-button');
                        btn.textContent = `${slot.heure_debut.substring(0, 5)}`; // Affiche HH:mm
                        
                        btn.onclick = function() {
                            // Désélectionner les autres
                            document.querySelectorAll('.slot-button').forEach(b => b.classList.remove('active'));
                            // Sélectionner celui-ci
                            btn.classList.add('active');
                            selectedSlotInput.value = slot.id_creneau;
                            submitBtn.disabled = false;
                        };
                        
                        slotsContainer.appendChild(btn);
                    });
                }
            });
        }
    });
});
});
