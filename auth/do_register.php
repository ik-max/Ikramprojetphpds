<?php
// ============================================
// Script d'inscription (register)
// Ce script reçoit les données du formulaire d'inscription
// et crée un nouveau compte utilisateur
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
$prenom = trim($_POST['prenom']);
$nom = trim($_POST['nom']);
$email = trim($_POST['email']);
$mot_de_passe = $_POST['mot_de_passe'];
$role = $_POST['role'];

// Vérifier que les champs obligatoires ne sont pas vides
if (empty($prenom) || empty($nom) || empty($email) || empty($mot_de_passe)) {
    $_SESSION['message'] = "Veuillez remplir tous les champs obligatoires.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/login.php");
    exit();
}

// Vérifier que le mot de passe fait au moins 8 caractères
if (strlen($mot_de_passe) < 8) {
    $_SESSION['message'] = "Le mot de passe doit contenir au moins 8 caractères.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/login.php");
    exit();
}

// Vérifier si l'email existe déjà dans la base de données
$stmt = $cnx->prepare("SELECT id FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$existe = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existe) {
    $_SESSION['message'] = "Un compte avec cet email existe déjà.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/login.php");
    exit();
}

// Hasher le mot de passe avant de le stocker dans la base
// password_hash() crée un mot de passe sécurisé (illisible)
// Cela protège les utilisateurs en cas de vol de la base de données
$hashed = password_hash($mot_de_passe, PASSWORD_DEFAULT);

// Insérer le nouvel utilisateur dans la base de données
// (Requête préparée avec marqueurs nommés - comme dans le cours)
$sql = "INSERT INTO users (prenom, nom, email, mot_de_passe, role) VALUES (:prenom, :nom, :email, :mot_de_passe, :role)";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':prenom', $prenom);
$stmt->bindParam(':nom', $nom);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':mot_de_passe', $hashed);
$stmt->bindParam(':role', $role);
$stmt->execute();

$_SESSION['message'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/login.php");
exit();
