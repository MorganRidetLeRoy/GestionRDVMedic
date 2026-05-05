# Documentation de la Base de Données - Gestion Rendez-Vous

## 📌 Introduction
Cette documentation décrit la structure de la base de données utilisée par l'application **Gestion Rendez-Vous**. Toutes les tables sont conçues pour répondre aux besoins fonctionnels du projet, avec un accent particulier sur la **sécurité** (chiffrement des données sensibles) et la **maintenabilité**.

---

## 🗃️ Tables de la Base de Données

### 1. `users`
**Description** : Stocke les informations des utilisateurs (secrétaires, praticiens, admin local).
   Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `email` | VARCHAR(255) | Email de l'utilisateur (chiffré) | `contact@cabinet.fr` |
 | `password` | VARCHAR(255) | Mot de passe haché | `...` |
 | `role` | ENUM | Rôle de l'utilisateur (`secrétaire`, `praticien`, `admin_local`) | `praticien` |
 | `phone` | VARCHAR(20) | Numéro de téléphone (chiffré) | `0123456789` |
 | `is_active` | BOOLEAN | Indique si le compte est actif | `1` |
 | `last_password_reset` | TIMESTAMP | Date de la dernière réinitialisation du mot de passe | `2026-05-01 10:00:00` |
 | `created_at` | TIMESTAMP | Date de création | `2026-05-01 09:00:00` |
 | `updated_at` | TIMESTAMP | Date de la dernière mise à jour | `2026-05-01 10:30:00` |
 | `last_activity` | TIMESTAMP | Dernière activité de l'utilisateur | `2026-05-01 10:45:00` |

**Relations** :
- Un utilisateur peut avoir plusieurs **rendez-vous** (`appointments.practitioner_id`).
- Un utilisateur peut avoir plusieurs **créneaux horaires** (`practitioner_slots.practitioner_id`).

---

### 2. `patients`
**Description** : Stocke les informations des patients.
 | Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `email` | VARCHAR(255) | Email du patient (chiffré) | `patient@example.com` |
 | `password` | VARCHAR(255) | Mot de passe haché | `...` |
 | `first_name` | VARCHAR(100) | Prénom du patient (chiffré) | `Jean` |
 | `last_name` | VARCHAR(100) | Nom du patient (chiffré) | `Dupont` |
 | `phone` | VARCHAR(20) | Numéro de téléphone (chiffré) | `0612345678` |
 | `temporary_password` | BOOLEAN | Indique si le mot de passe est temporaire | `1` |
 | `has_medical_record` | BOOLEAN | Indique si le patient a un dossier médical | `1` |
 | `created_at` | TIMESTAMP | Date de création | `2026-05-01 08:00:00` |
 | `updated_at` | TIMESTAMP | Date de la dernière mise à jour | `2026-05-01 08:30:00` |

**Relations** :
- Un patient peut avoir plusieurs **rendez-vous** (`appointments.patient_id`).
- Un patient peut avoir un **dossier médical** (`medical_records.patient_id`).

---

### 3. `appointments`
**Description** : Stocke les informations des rendez-vous.
 | Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `patient_id` | INT | ID du patient | 1 |
 | `practitioner_id` | INT | ID du praticien | 1 |
 | `date` | DATETIME | Date et heure du rendez-vous | `2026-05-10 14:00:00` |
 | `status` | ENUM | Statut du rendez-vous (`scheduled`, `confirmed`, `cancelled`, `completed`) | `confirmed` |
 | `reason` | TEXT | Motif du rendez-vous (chiffré) | `Consultation de suivi` |
 | `created_at` | TIMESTAMP | Date de création | `2026-05-01 09:00:00` |
 | `updated_at` | TIMESTAMP | Date de la dernière mise à jour | `2026-05-01 09:30:00` |

**Relations** :
- `patient_id` → `patients.id`
- `practitioner_id` → `users.id`

---

### 4. `medical_records`
**Description** : Stocke les dossiers médicaux des patients.
 | Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `patient_id` | INT | ID du patient | 1 |
 | `created_by` | INT | ID du praticien qui a créé le dossier | 1 |
 | `created_at` | TIMESTAMP | Date de création | `2026-05-01 10:00:00` |
 | `updated_at` | TIMESTAMP | Date de la dernière mise à jour | `2026-05-01 10:30:00` |

**Relations** :
- `patient_id` → `patients.id`
- `created_by` → `users.id`

---
### 5. `medical_notes`
**Description** : Stocke les notes médicales (antécédents, traitements, etc.).
 | Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `medical_record_id` | INT | ID du dossier médical | 1 |
 | `note_type` | ENUM | Type de note (`antecedent`, `treatment`, `consultation`, `other`) | `antecedent` |
 | `title` | VARCHAR(255) | Titre de la note (chiffré) | `Allergie aux pénicillines` |
 | `content` | TEXT | Contenu de la note (chiffré) | `Le patient est allergique aux pénicillines...` |
 | `created_by` | INT | ID du praticien qui a créé la note | 1 |
 | `created_at` | TIMESTAMP | Date de création | `2026-05-01 11:00:00` |
 | `updated_at` | TIMESTAMP | Date de la dernière mise à jour | `2026-05-01 11:30:00` |

**Relations** :
- `medical_record_id` → `medical_records.id`
- `created_by` → `users.id`

---
### 6. `practitioner_slots`
**Description** : Stocke les créneaux horaires des praticiens.
 | Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `practitioner_id` | INT | ID du praticien | 1 |
 | `day_of_week` | TINYINT | Jour de la semaine (0=dimanche, 6=samedi) | `1` (lundi) |
 | `start_time` | TIME | Heure de début | `09:00:00` |
 | `end_time` | TIME | Heure de fin | `17:00:00` |
 | `is_available` | BOOLEAN | Indique si le créneau est disponible | `1` |

**Relations** :
- `practitioner_id` → `users.id`

---
### 7. `notifications`
**Description** : Stocke les notifications envoyées (emails, etc.).
 | Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `user_id` | INT | ID de l'utilisateur (optionnel) | 1 |
 | `patient_id` | INT | ID du patient (optionnel) | 1 |
 | `type` | ENUM | Type de notification (`new_account`, `appointment_confirmation`, `appointment_reminder`) | `appointment_confirmation` |
 | `content` | TEXT | Contenu de la notification | `Confirmation de rendez-vous pour Jean Dupont` |
 | `sent_at` | TIMESTAMP | Date d'envoi | `2026-05-01 12:00:00` |
 | `status` | ENUM | Statut (`sent`, `failed`) | `sent` |

**Relations** :
- `user_id` → `users.id`
- `patient_id` → `patients.id`

---
### 8. `encryption_keys`
**Description** : Stocke les clés de chiffrement pour les données sensibles (US-34).
 | Champ | Type | Description | Exemple |
 |-------|------|-------------|---------|
 | `id` | INT | Identifiant unique | 1 |
 | `key_name` | VARCHAR(50) | Nom de la clé | `default` |
 | `key_value` | TEXT | Valeur de la clé (chiffrée) | `...` |
 | `created_at` | TIMESTAMP | Date de création | `2026-05-01 08:00:00` |

---

## 🔐 Sécurité des Données (US-34)
- **Chiffrement** : Les données sensibles (noms, prénoms, emails, téléphones, motifs de rendez-vous, notes médicales) sont **automatiquement chiffrées** avant d'être stockées en base de données.
- **Clé de chiffrement** : Une clé unique est générée et stockée dans la table `encryption_keys`. Cette clé est utilisée pour chiffrer/déchiffrer les données.
- **Algorithme** : **AES-256-CBC** (standard industriel pour le chiffrement symétrique).

---

## 🔄 Incrémentation Automatique (US-35)
- Toutes les **insertions** et **mises à jour** de données passent par le service `DatabaseManager`, qui :
  1. **Chiffre automatiquement** les champs sensibles.
  2. **Déchiffre automatiquement** les données lors de leur lecture.
- Exemple :
  ```php
  // Insertion d'un patient (chiffrement automatique)
  \$patientId = DatabaseManager::autoIncrementData('patients', [
      'email' => 'patient@example.com',
      'first_name' => 'Jean',
      'last_name' => 'Dupont'
  ]);

  // Lecture d'un patient (déchiffrement automatique)
  \$patients = DatabaseManager::getDecryptedData('patients');