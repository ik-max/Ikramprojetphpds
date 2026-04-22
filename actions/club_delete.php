<?php
// ============================================
// Script pour supprimer un club
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

// Récupérer l'id du club à supprimer
$admin_id = $_SESSION['user_id'];
$club_id = intval($_POST['club_id']);

// Vérifier que l'id est valide
if ($club_id <= 0) {
    $_SESSION['message'] = "Club invalide.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Vérifier que l'admin est bien le propriétaire du club
$stmt = $cnx->prepare("SELECT id, nom FROM clubs WHERE id = :id AND admin_id = :admin_id");
$stmt->bindParam(':id', $club_id);
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    $_SESSION['message'] = "Vous n'êtes pas autorisé à supprimer ce club.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Supprimer le club (les membres et événements seront supprimés automatiquement grâce au CASCADE)
$stmt = $cnx->prepare("DELETE FROM clubs WHERE id = :id AND admin_id = :admin_id");
$stmt->bindParam(':id', $club_id);
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();

$_SESSION['message'] = "Club supprimé avec succès.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/admin.php");
exit();
