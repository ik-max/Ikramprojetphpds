<?php
// ============================================
// Script pour quitter un club
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
    header("Location: ../pages/clubs.php");
    exit();
}

// Récupérer les données
$user_id = $_SESSION['user_id'];
$club_id = intval($_POST['club_id']);

// Vérifier que l'id est valide
if ($club_id <= 0) {
    $_SESSION['message'] = "Club invalide.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/clubs.php");
    exit();
}

// Supprimer l'adhésion
$stmt = $cnx->prepare("DELETE FROM membres WHERE user_id = :user_id AND club_id = :club_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':club_id', $club_id);
$stmt->execute();

$_SESSION['message'] = "Vous avez quitté le club.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/club_details.php?id=" . $club_id);
exit();
