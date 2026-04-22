<?php
// ============================================
// Script de déconnexion (logout)
// Détruit la session et redirige vers la page de connexion
// ============================================
session_start();
session_unset();
session_destroy();
header("Location: ../pages/login.php");
exit();
