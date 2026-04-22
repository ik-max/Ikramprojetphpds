<?php
// ============================================
// Script pour s'inscrire à un événement
// ============================================

// Inclure la connexion et la session
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/auth_check.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

// Vérifier que le formulaire a été soumis avec POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../pages/events.php");
    exit();
}

// Récupérer les données
$user_id = $_SESSION['user_id'];
$event_id = intval($_POST['event_id']);

// Vérifier que l'id est valide
if ($event_id <= 0) {
    $_SESSION['message'] = "Événement invalide.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/events.php");
    exit();
}

// Vérifier que l'événement existe et qu'il reste des places
$stmt = $cnx->prepare("SELECT e.id, e.max_participants, (SELECT COUNT(*) FROM inscriptions_evenements WHERE evenement_id = e.id) as nb_inscrits FROM evenements e WHERE e.id = :id");
$stmt->bindParam(':id', $event_id);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['message'] = "Événement non trouvé.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/events.php");
    exit();
}

// Vérifier si l'utilisateur est déjà inscrit
$stmt = $cnx->prepare("SELECT id FROM inscriptions_evenements WHERE user_id = :uid AND evenement_id = :eid");
$stmt->bindParam(':uid', $user_id);
$stmt->bindParam(':eid', $event_id);
$stmt->execute();
$deja_inscrit = $stmt->fetch(PDO::FETCH_ASSOC);

if ($deja_inscrit) {
    $_SESSION['message'] = "Vous êtes déjà inscrit à cet événement.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/event_details.php?id=" . $event_id);
    exit();
}

// Vérifier qu'il reste des places
if ($event['nb_inscrits'] >= $event['max_participants']) {
    $_SESSION['message'] = "Cet événement est complet.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/event_details.php?id=" . $event_id);
    exit();
}

// Inscrire l'utilisateur à l'événement
$sql = "INSERT INTO inscriptions_evenements (user_id, evenement_id) VALUES (:uid, :eid)";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':uid', $user_id);
$stmt->bindParam(':eid', $event_id);
$stmt->execute();

$_SESSION['message'] = "Inscription réussie !";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/event_details.php?id=" . $event_id);
exit();
