<?php
// ============================================
// Script de connexion (login)
// Ce script reçoit les données du formulaire de connexion
// et vérifie si l'email et le mot de passe sont corrects
// ============================================

// Inclure la connexion à la base de données
require_once __DIR__ . '/../actions/connexion.php';
// Démarrer la session
require_once __DIR__ . '/../actions/auth_check.php';

// Vérifier que le formulaire a été soumis avec la méthode POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../pages/login.php");
    exit();
}

// Récupérer les données du formulaire
$email = trim($_POST['email']);
$password = $_POST['password'];

// Vérifier que les champs ne sont pas vides
if (empty($email) || empty($password)) {
    $_SESSION['message'] = "Veuillez remplir tous les champs.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/login.php");
    exit();
}

// Chercher l'utilisateur dans la base de données avec une requête préparée
// (Comme dans le cours - Chapitre 4 : Requêtes Préparées avec marqueurs nommés)
$stmt = $cnx->prepare("SELECT * FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si l'utilisateur existe et si le mot de passe est correct
// password_verify() compare le mot de passe saisi avec le mot de passe hashé dans la base
if (!$user || !password_verify($password, $user['mot_de_passe'])) {
    $_SESSION['message'] = "Email ou mot de passe incorrect.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/login.php");
    exit();
}

// Stocker les informations de l'utilisateur dans la session
$_SESSION['user_id'] = $user['id'];
$_SESSION['prenom'] = $user['prenom'];
$_SESSION['nom'] = $user['nom'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['filiere'] = $user['filiere'];
$_SESSION['bio'] = $user['bio'];

// Rediriger selon le rôle de l'utilisateur
if ($user['role'] == 'admin_club') {
    header("Location: ../pages/admin.php");
} else {
    header("Location: ../pages/dashboard.php");
}
exit();
