<?php
// ============================================
// Script pour modifier un club
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
$club_id = intval($_POST['club_id']);
$nom = trim($_POST['nom']);
$description = trim($_POST['description']);
$categorie = trim($_POST['categorie']);
$emoji = trim($_POST['emoji']);

// Vérifier que les champs obligatoires ne sont pas vides
if ($club_id <= 0 || empty($nom) || empty($description)) {
    $_SESSION['message'] = "Données invalides.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Vérifier que l'admin est bien le propriétaire du club
$stmt = $cnx->prepare("SELECT id FROM clubs WHERE id = :id AND admin_id = :admin_id");
$stmt->bindParam(':id', $club_id);
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();
$club = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$club) {
    $_SESSION['message'] = "Vous n'êtes pas autorisé à modifier ce club.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Choisir le dégradé de couleur selon la catégorie
if ($categorie == 'scientifique') {
    $couleur = 'linear-gradient(135deg, #e8f0fc, #c5d8ff)';
} elseif ($categorie == 'culturel') {
    $couleur = 'linear-gradient(135deg, #fce8e3, #ffc5b0)';
} elseif ($categorie == 'sportif') {
    $couleur = 'linear-gradient(135deg, #fff5e0, #ffd88a)';
} else {
    $couleur = 'linear-gradient(135deg, #e8f5ee, #b3e6c8)';
}

// Mettre à jour le club dans la base de données
$sql = "UPDATE clubs SET nom = :nom, description = :description, categorie = :categorie, emoji = :emoji, couleur_gradient = :couleur WHERE id = :id AND admin_id = :admin_id";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':nom', $nom);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':categorie', $categorie);
$stmt->bindParam(':emoji', $emoji);
$stmt->bindParam(':couleur', $couleur);
$stmt->bindParam(':id', $club_id);
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();

$_SESSION['message'] = "Club mis à jour avec succès.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/admin.php");
exit();
