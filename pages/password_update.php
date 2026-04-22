<?php
// ============================================
// Script pour changer le mot de passe
// ============================================

// Inclure la connexion à la base de données
require_once '../actions/connexion.php';
// Démarrer la session
require_once '../actions/auth_check.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Vérifier que le formulaire a été soumis avec POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: profile.php");
    exit();
}

// Récupérer les données du formulaire
$user_id = $_SESSION['user_id'];
$old_password = $_POST['old_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Vérifier que les champs ne sont pas vides
if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
    $_SESSION['message'] = "Veuillez remplir tous les champs.";
    $_SESSION['message_type'] = "erreur";
    header("Location: profile.php");
    exit();
}

// Vérifier que les deux mots de passe correspondent
if ($new_password != $confirm_password) {
    $_SESSION['message'] = "Les mots de passe ne correspondent pas.";
    $_SESSION['message_type'] = "erreur";
    header("Location: profile.php");
    exit();
}

// Vérifier que le nouveau mot de passe fait au moins 8 caractères
if (strlen($new_password) < 8) {
    $_SESSION['message'] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    $_SESSION['message_type'] = "erreur";
    header("Location: profile.php");
    exit();
}

// Vérifier l'ancien mot de passe
$stmt = $cnx->prepare("SELECT mot_de_passe FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !password_verify($old_password, $row['mot_de_passe'])) {
    $_SESSION['message'] = "Le mot de passe actuel est incorrect.";
    $_SESSION['message_type'] = "erreur";
    header("Location: profile.php");
    exit();
}

// Hasher et mettre à jour le nouveau mot de passe
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $cnx->prepare("UPDATE users SET mot_de_passe = :pwd WHERE id = :id");
$stmt->bindParam(':pwd', $hashed);
$stmt->bindParam(':id', $user_id);
$stmt->execute();

$_SESSION['message'] = "Mot de passe mis à jour avec succès.";
$_SESSION['message_type'] = "succes";
header("Location: profile.php");
exit();
