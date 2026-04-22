<?php
// ============================================
// Script pour accepter ou refuser une demande d'adhésion
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
$membre_id = intval($_POST['membre_id']);
$action = $_POST['action'];

// Vérifier les données
if ($membre_id <= 0 || ($action != 'accepter' && $action != 'refuser')) {
    $_SESSION['message'] = "Données invalides.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Vérifier que cette demande appartient à un club de cet admin
$stmt = $cnx->prepare("SELECT m.id, m.club_id FROM membres m JOIN clubs c ON m.club_id = c.id WHERE m.id = :membre_id AND c.admin_id = :admin_id AND m.statut = 'en_attente'");
$stmt->bindParam(':membre_id', $membre_id);
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();
$membership = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$membership) {
    $_SESSION['message'] = "Demande non trouvée ou non autorisée.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Mettre à jour le statut
if ($action == 'accepter') {
    $new_status = 'accepte';
} else {
    $new_status = 'refuse';
}

$stmt = $cnx->prepare("UPDATE membres SET statut = :statut WHERE id = :id");
$stmt->bindParam(':statut', $new_status);
$stmt->bindParam(':id', $membre_id);
$stmt->execute();

// Afficher un message selon l'action
if ($action == 'accepter') {
    $_SESSION['message'] = "Demande acceptée.";
} else {
    $_SESSION['message'] = "Demande refusée.";
}
$_SESSION['message_type'] = "succes";
header("Location: ../pages/admin.php");
exit();
