<?php
// ============================================
// Script pour créer un événement
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
$titre = trim($_POST['titre']);
$description = trim($_POST['description']);
$lieu = trim($_POST['lieu']);
$date_debut = $_POST['date_debut'];
$date_fin = $_POST['date_fin'];
$max_participants = intval($_POST['max_participants']);

// Vérifier que les champs ne sont pas vides
if ($club_id <= 0 || empty($titre) || empty($description) || empty($lieu) || empty($date_debut) || empty($date_fin)) {
    $_SESSION['message'] = "Tous les champs sont obligatoires.";
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
    $_SESSION['message'] = "Club non trouvé ou non autorisé.";
    $_SESSION['message_type'] = "erreur";
    header("Location: ../pages/admin.php");
    exit();
}

// Insérer l'événement dans la base de données
$sql = "INSERT INTO evenements (titre, description, club_id, lieu, date_debut, date_fin, max_participants) VALUES (:titre, :description, :club_id, :lieu, :date_debut, :date_fin, :max)";
$stmt = $cnx->prepare($sql);
$stmt->bindParam(':titre', $titre);
$stmt->bindParam(':description', $description);
$stmt->bindParam(':club_id', $club_id);
$stmt->bindParam(':lieu', $lieu);
$stmt->bindParam(':date_debut', $date_debut);
$stmt->bindParam(':date_fin', $date_fin);
$stmt->bindParam(':max', $max_participants);
$stmt->execute();

$_SESSION['message'] = "Événement créé avec succès !";
$_SESSION['message_type'] = "succes";
header("Location: ../pages/admin.php");
exit();
