<?php
// ============================================
// Script pour modifier un événement
// ============================================

// Inclure la connexion et la session
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/auth_check.php';

// Vérifier que l'utilisateur est connecté et qu'il est admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin_club') {
    header("Location: ../pages/login.php");
    exit();
}

// Vérifier que le formulaire a été soumis avec POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../pages/admin.php");
    exit();
}

// Récupérer les données du formulaire
$admin_id = $_SESSION['user_id'];
$event_id = intval($_POST['event_id']);
$titre = trim($_POST['titre']);
$description = trim($_POST['description']);
$lieu = trim($_POST['lieu']);
$date_debut = $_POST['date_debut'];
$date_fin = $_POST['date_fin'];
$max_participants = intval($_POST['max_participants']);

// Vérifier que les champs ne sont pas vides
if ($event_id <= 0 || empty($titre) || empty($description) || empty($lieu) || empty($date_debut) || empty($date_fin)) {
    $_SESSION['message'] = "Tous les champs sont obligatoires.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Vérifier que l'admin est bien le propriétaire du club de cet événement
$stmt = $cnx->prepare("SELECT e.id FROM evenements e JOIN clubs c ON e.club_id = c.id WHERE e.id = :event_id AND c.admin_id = :admin_id");
$stmt->bindParam(':event_id', $event_id);
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['message'] = "Événement non trouvé ou non autorisé.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Mettre à jour l'événement
$sql = "UPDATE evenements SET titre = :titre, description = :description, lieu = :lieu, date_debut = :date_debut, date_fin = :date_fin, max_participants = :max WHERE id = :id";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':titre', $titre);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':lieu', $lieu);
$stmt->bindParam(':date_debut', $date_debut);
$stmt->bindParam(':date_fin', $date_fin);
$stmt->bindParam(':max', $max_participants);
$stmt->bindParam(':id', $event_id);
$stmt->execute();

$_SESSION['message'] = "Événement mis à jour avec succès.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/admin.php");
exit();
