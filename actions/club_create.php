<?php
// ============================================
// Script pour créer un nouveau club
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
    header("Location: ../pages/clubs.php");
    exit();
}

// Récupérer les données du formulaire
$admin_id = $_SESSION['user_id'];
$nom = trim($_POST['nom']);
$description = trim($_POST['description']);
$categorie = trim($_POST['categorie']);
$emoji = trim($_POST['emoji']);

// Vérifier que les champs obligatoires ne sont pas vides
if (empty($nom) || empty($description)) {
    $_SESSION['message'] = "Le nom et la description du club sont obligatoires.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/clubs.php");
    exit();
}

// Choisir un dégradé de couleur selon la catégorie
if ($categorie == 'scientifique') {
    $couleur = 'linear-gradient(135deg, #e8f0fc, #c5d8ff)';
} elseif ($categorie == 'culturel') {
    $couleur = 'linear-gradient(135deg, #fce8e3, #ffc5b0)';
} elseif ($categorie == 'sportif') {
    $couleur = 'linear-gradient(135deg, #fff5e0, #ffd88a)';
} else {
    $couleur = 'linear-gradient(135deg, #e8f5ee, #b3e6c8)';
}

// Insérer le club dans la base de données (requête préparée avec marqueurs nommés)
$sql = "INSERT INTO clubs (nom, description, categorie, emoji, couleur_gradient, admin_id) VALUES (:nom, :description, :categorie, :emoji, :couleur, :admin_id)";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':nom', $nom);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':categorie', $categorie);
$stmt->bindParam(':emoji', $emoji);
$stmt->bindParam(':couleur', $couleur);
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();

$_SESSION['message'] = "Club créé avec succès !";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/admin.php");
exit();
