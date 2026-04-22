<?php
// ============================================
// Script pour demander l'adhésion à un club
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

// Vérifier que le club existe
$stmt = $cnx->prepare("SELECT id FROM clubs WHERE id = :id");
$stmt->bindParam(':id', $club_id);
$stmt->execute();
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    $_SESSION['message'] = "Ce club n'existe pas.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/clubs.php");
    exit();
}

// Vérifier si l'utilisateur a déjà une demande ou est déjà membre
$stmt = $cnx->prepare("SELECT id, statut FROM membres WHERE user_id = :user_id AND club_id = :club_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':club_id', $club_id);
$stmt->execute();
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Si la demande est en attente
    if ($existing['statut'] == 'en_attente') {
        $_SESSION['message'] = "Vous avez déjà une demande en attente pour ce club.";
        $_SESSION['message_type'] = "erreur";
    }
    // Si déjà accepté
    elseif ($existing['statut'] == 'accepte') {
        $_SESSION['message'] = "Vous êtes déjà membre de ce club.";
        $_SESSION['message_type'] = "erreur";
    }
    // Si refusé, on peut re-soumettre la demande
    else {
        $stmt = $cnx->prepare("UPDATE membres SET statut = 'en_attente', date_demande = NOW() WHERE id = :id");
        $stmt->bindParam(':id', $existing['id']);
        $stmt->execute();
        $_SESSION['message'] = "Votre demande a été renvoyée.";
        $_SESSION['message_type'] = "succes";
    }
    header("Location: ../pages/club_details.php?id=" . $club_id);
    exit();
}

// Créer la demande d'adhésion
$sql = "INSERT INTO membres (user_id, club_id, statut) VALUES (:user_id, :club_id, 'en_attente')";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':club_id', $club_id);
$stmt->execute();

$_SESSION['message'] = "Demande d'adhésion envoyée avec succès !";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/club_details.php?id=" . $club_id);
exit();
