<?php
class StatController {
    private $rdvModel;
    private $medecinModel;
    private $statModel;

    public function __construct($db) {
        $this->rdvModel = new RendezVous($db);
        $this->medecinModel = new Medecin($db);
        $this->statModel = new Statistique($db);
    }

    public function resumeJournee() {
        // SÉCURITÉ : CSRF - Vérification du token pour les requêtes POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Erreur CSRF : requête invalide.");
            }

            // Gérer l'export CSV en POST
            if (isset($_POST['export']) && $_POST['export'] === 'csv') {
                $date = $_POST['date'];
                $medecin_id = isset($_POST['medecin_id']) ? (int)$_POST['medecin_id'] : null;
                $data = $this->prepareDataForExport($date, $medecin_id);
                $this->exportCSV($data);
                exit;
            }
        }

        // Récupérer les paramètres de l'URL
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $medecin_id = isset($_GET['medecin_id']) ? (int)$_GET['medecin_id'] : null; // SÉCURITÉ : Validation des entrées

        // Récupérer les statistiques
        $rdvStats = $this->rdvModel->getRendezVousParJour($date, $medecin_id);
        $avisStats = $this->statModel->getAvisParJour($date, $medecin_id);
        $tempsMoyen = $this->rdvModel->getTempsMoyenConsultation($date, $medecin_id);
        $medecinsTop = $this->medecinModel->getMedecinsLesPlusSollicites($date);

        // Calculer le taux de présence
        $tauxPresence = ($rdvStats['totalRDV'] > 0)
            ? round(($rdvStats['rdvRealises'] / $rdvStats['totalRDV']) * 100, 2)
            : 0;

        // Comparaison avec la veille
        $compare_date = date('Y-m-d', strtotime($date . ' -1 day'));
        $comparaison = $this->rdvModel->getRendezVousParJourCompare($date, $compare_date, $medecin_id);
        $comparaison = array_combine(array_column($comparaison, 'jour'), $comparaison);

        $data = [
            'date' => $date,
            'medecin_id' => $medecin_id,
            'rdvStats' => $rdvStats,
            'avisStats' => $avisStats,
            'tempsMoyen' => $tempsMoyen,
            'medecinsTop' => $medecinsTop,
            'tauxPresence' => $tauxPresence,
            'comparaison' => [
                'hier' => $comparaison[$compare_date] ?? ['totalRDV' => 0, 'rdvRealises' => 0],
                'aujourdhui' => $comparaison[$date] ?? ['totalRDV' => 0, 'rdvRealises' => 0],
            ],
        ];

        // Afficher la vue
        include '../Views/resume_journee.php';
    }

    private function prepareDataForExport($date, $medecin_id) {
        $rdvStats = $this->rdvModel->getRendezVousParJour($date, $medecin_id);
        $avisStats = $this->statModel->getAvisParJour($date, $medecin_id);
        $tempsMoyen = $this->rdvModel->getTempsMoyenConsultation($date, $medecin_id);
        $medecinsTop = $this->medecinModel->getMedecinsLesPlusSollicites($date);

        $tauxPresence = ($rdvStats['totalRDV'] > 0)
            ? round(($rdvStats['rdvRealises'] / $rdvStats['totalRDV']) * 100, 2)
            : 0;

        return [
            'date' => $date,
            'rdvStats' => $rdvStats,
            'avisStats' => $avisStats,
            'tempsMoyen' => $tempsMoyen,
            'medecinsTop' => $medecinsTop,
            'tauxPresence' => $tauxPresence,
        ];
    }

    private function exportCSV($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="statistiques_' . $data['date'] . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'Date', 'RDV Prévus', 'RDV Réalisés', 'RDV Annulés', 'RDV Absents',
            'Taux de Présence', 'Temps Moyen (min)', 'Médecin le Plus Sollicité'
        ]);

        fputcsv($output, [
            $data['date'],
            $data['rdvStats']['totalRDV'],
            $data['rdvStats']['rdvRealises'],
            $data['rdvStats']['rdvAnnules'],
            $data['rdvStats']['rdvAbsents'],
            $data['tauxPresence'] . '%',
            round($data['tempsMoyen']),
            $data['medecinsTop'][0]['nom_complet'] ?? 'Aucun',
        ]);

        fclose($output);
    }
}
?>
