<?php
// ============================================
// Script pour supprimer un événement
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

// Récupérer les données
$admin_id = $_SESSION['user_id'];
$event_id = intval($_POST['event_id']);

// Vérifier que l'id est valide
if ($event_id <= 0) {
    $_SESSION['message'] = "Événement invalide.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Vérifier que l'admin est le propriétaire du club de cet événement
$stmt = $cnx->prepare("SELECT e.id, e.titre FROM evenements e JOIN clubs c ON e.club_id = c.id WHERE e.id = :event_id AND c.admin_id = :admin_id");
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

// Supprimer l'événement (les inscriptions seront supprimées automatiquement grâce au CASCADE)
$stmt = $cnx->prepare("DELETE FROM evenements WHERE id = :id");
$stmt->bindParam(':id', $event_id);
$stmt->execute();

$_SESSION['message'] = "Événement supprimé.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/admin.php");
exit();
