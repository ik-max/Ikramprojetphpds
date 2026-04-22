<?php
// ============================================
// Script pour se désinscrire d'un événement
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

// Supprimer l'inscription
$stmt = $cnx->prepare("DELETE FROM inscriptions_evenements WHERE user_id = :uid AND evenement_id = :eid");
$stmt->bindParam(':uid', $user_id);
$stmt->bindParam(':eid', $event_id);
$stmt->execute();

$_SESSION['message'] = "Inscription annulée.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/event_details.php?id=" . $event_id);
exit();
