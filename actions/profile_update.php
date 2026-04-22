<?php
// ============================================
// Script pour modifier le profil de l'utilisateur
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
    header("Location: ../pages/profile.php");
    exit();
}

// Récupérer les données du formulaire
$user_id = $_SESSION['user_id'];
$prenom = trim($_POST['prenom']);
$nom = trim($_POST['nom']);
$email = trim($_POST['email']);
$filiere = trim($_POST['filiere']);
$bio = trim($_POST['bio']);

// Vérifier que les champs obligatoires ne sont pas vides
if (empty($prenom) || empty($nom) || empty($email)) {
    $_SESSION['message'] = "Prénom, nom et email sont obligatoires.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/profile.php");
    exit();
}

// Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
$stmt = $cnx->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
$stmt->bindParam(':email', $email);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$existe = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existe) {
    $_SESSION['message'] = "Cet email est déjà utilisé par un autre compte.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/profile.php");
    exit();
}

// Mettre à jour le profil dans la base de données
$sql = "UPDATE users SET prenom = :prenom, nom = :nom, email = :email, filiere = :filiere, bio = :bio WHERE id = :id";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':prenom', $prenom);
$stmt->bindParam(':nom', $nom);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':filiere', $filiere);
$stmt->bindParam(':bio', $bio);
$stmt->bindParam(':id', $user_id);
$stmt->execute();

// Mettre à jour les informations dans la session
$_SESSION['prenom'] = $prenom;
$_SESSION['nom'] = $nom;
$_SESSION['email'] = $email;
$_SESSION['filiere'] = $filiere;
$_SESSION['bio'] = $bio;

$_SESSION['message'] = "Profil mis à jour avec succès.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/profile.php");
exit();
