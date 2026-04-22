<?php
// ============================================
// Header commun à toutes les pages
// ============================================

// Vérifier si un titre de page a été défini, sinon mettre un titre par défaut
if (!isset($page_title)) {
    $page_title = "UniClubs";
}
if (!isset($active_page)) {
    $active_page = "";
}

// Vérifier si l'utilisateur est connecté (grâce à la session)
$logged_in = isset($_SESSION['user_id']);

// Si connecté, récupérer le rôle
if ($logged_in) {
    $is_admin = ($_SESSION['role'] == 'admin_club');
    $is_student = ($_SESSION['role'] == 'etudiant');
} else {
    $is_admin = false;
    $is_student = false;
}

// Base URL — correspond au chemin du projet sur le serveur
$base_url = '/Ikram Bali/Ikramprojetphpds/';
?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="UniClubs - Plateforme de gestion des clubs universitaires" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />

    <!-- Global Design System -->
    <link rel="stylesheet" href="<?= $base_url ?>css/global.css" />

    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="<?= $base_url . htmlspecialchars($page_css) ?>" />
    <?php endif; ?>
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand brand" href="<?php echo $base_url; ?>index.php">Uni<span>Clubs</span></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                    <?php if (!$logged_in): ?>
                        <!-- Menu pour les visiteurs (non connectés) -->
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'index' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>index.php">
                                <i class="bi bi-house-door me-1"></i>Accueil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'clubs' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/clubs.php">
                                <i class="bi bi-people me-1"></i>Clubs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'events' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/events.php">
                                <i class="bi bi-calendar-event me-1"></i>Événements
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn-primary-glow" href="<?php echo $base_url; ?>pages/login.php" style="font-size:0.83rem;padding:0.5rem 1.2rem;">
                                Connexion <i class="bi bi-arrow-right"></i>
                            </a>
                        </li>
                    <?php elseif ($is_student): ?>
                        <!-- Menu pour les étudiants -->
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'dashboard' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/dashboard.php">
                                <i class="bi bi-grid-1x2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'clubs' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/clubs.php">
                                <i class="bi bi-people me-1"></i>Clubs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'events' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/events.php">
                                <i class="bi bi-calendar-event me-1"></i>Événements
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="nav-user-pill" href="<?php echo $base_url; ?>pages/profile.php">
                                <span class="nav-avatar"><?= strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)) ?></span>
                                <?= htmlspecialchars($_SESSION['prenom']) ?>
                            </a>
                        </li>
                        <li class="nav-item ms-lg-1">
                            <a class="nav-logout" href="<?php echo $base_url; ?>auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                            </a>
                        </li>
                    <?php elseif ($is_admin): ?>
                        <!-- Menu pour les administrateurs de club -->
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'dashboard' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/dashboard.php">
                                <i class="bi bi-grid-1x2 me-1"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'clubs' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/clubs.php">
                                <i class="bi bi-people me-1"></i>Clubs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'events' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/events.php">
                                <i class="bi bi-calendar-event me-1"></i>Événements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $active_page == 'admin' ? 'active-link' : '' ?>" href="<?php echo $base_url; ?>pages/admin.php">
                                <i class="bi bi-gear me-1"></i>Admin
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="nav-user-pill" href="<?php echo $base_url; ?>pages/profile.php">
                                <span class="nav-avatar"><?= strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)) ?></span>
                                <?= htmlspecialchars($_SESSION['prenom']) ?>
                            </a>
                        </li>
                        <li class="nav-item ms-lg-1">
                            <a class="nav-logout" href="<?php echo $base_url; ?>auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-5">
        <div class="container">
            <?php
            // Afficher un message de succès ou d'erreur (stocké dans la session)
            if (isset($_SESSION['message'])) {
                if ($_SESSION['message_type'] == 'succes') {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['message']) . '</div>';
                } else {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['message']) . '</div>';
                }
                // Supprimer le message après l'avoir affiché
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            }
            ?>