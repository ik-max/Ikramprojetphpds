<?php
// ============================================
// Connexion à la base de données avec PDO
// ============================================
$host = "localhost";
$base = "club_manager";
$user = "root";
$pass = "";

try {
    $cnx = new PDO("mysql:host=$host;dbname=$base;charset=utf8mb4", $user, $pass);
    $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $cnx->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    print "Erreur !: " . $e->getMessage() . "<br/>";
    die();
}
