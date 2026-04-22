<?php
// ============================================
// Page Dashboard (tableau de bord de l'utilisateur)
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

// Définir le titre et le style de la page
$page_title = 'UniClubs - Dashboard';
$page_css = 'css/styledash.css';
$active_page = 'dashboard';

// Récupérer l'id de l'utilisateur
$user_id = $_SESSION['user_id'];

// ---- STATISTIQUES ----

// Nombre de clubs rejoints (statut accepté)
$stmt = $cnx->prepare("SELECT COUNT(*) as total FROM membres WHERE user_id = :uid AND statut = 'accepte'");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
$nb_clubs_joined = $ligne['total'];

// Nombre d'événements à venir
$stmt = $cnx->prepare("SELECT COUNT(*) as total FROM evenements e JOIN clubs c ON e.club_id = c.id JOIN membres m ON m.club_id = c.id AND m.user_id = :uid AND m.statut = 'accepte' WHERE e.date_debut >= NOW()");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
$nb_upcoming_events = $ligne['total'];

// Nombre de demandes en attente
$stmt = $cnx->prepare("SELECT COUNT(*) as total FROM membres WHERE user_id = :uid AND statut = 'en_attente'");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
$nb_pending_requests = $ligne['total'];

// Nombre d'inscriptions aux événements
$stmt = $cnx->prepare("SELECT COUNT(*) as total FROM inscriptions_evenements WHERE user_id = :uid");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
$nb_event_registrations = $ligne['total'];

// ---- MES CLUBS ----
$stmt = $cnx->prepare("SELECT c.*, m.statut as membre_statut, (SELECT COUNT(*) FROM membres WHERE club_id = c.id AND statut = 'accepte') as nb_membres, CASE WHEN c.admin_id = :uid THEN 1 ELSE 0 END as is_admin FROM clubs c LEFT JOIN membres m ON m.club_id = c.id AND m.user_id = :uid2 WHERE m.statut = 'accepte' OR c.admin_id = :uid3 ORDER BY c.created_at DESC LIMIT 4");
$stmt->bindParam(':uid', $user_id);
$stmt->bindParam(':uid2', $user_id);
$stmt->bindParam(':uid3', $user_id);
$stmt->execute();
$my_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- PROCHAINS ÉVÉNEMENTS ----
$stmt = $cnx->prepare("SELECT e.*, c.nom as club_nom, c.emoji, (SELECT COUNT(*) FROM inscriptions_evenements WHERE evenement_id = e.id) as nb_inscrits, (SELECT COUNT(*) FROM inscriptions_evenements WHERE evenement_id = e.id AND user_id = :uid) as is_registered FROM evenements e JOIN clubs c ON e.club_id = c.id WHERE e.date_debut >= NOW() ORDER BY e.date_debut ASC LIMIT 4");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- DEMANDES EN ATTENTE (pour les admins de clubs) ----
$pending_requests = array();
if ($_SESSION['role'] == 'admin_club') {
    $stmt = $cnx->prepare("SELECT m.*, u.prenom, u.nom, u.email, c.nom as club_nom FROM membres m JOIN users u ON m.user_id = u.id JOIN clubs c ON m.club_id = c.id WHERE c.admin_id = :admin_id AND m.statut = 'en_attente' ORDER BY m.date_demande DESC");
    $stmt->bindParam(':admin_id', $user_id);
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tableau des mois en français
$months_fr = array('Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc');

// Inclure le header
include '../includes/header.php';
?>

        <!-- HERO -->
        <section class="dash-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge hero-badge mb-3">Tableau de bord</span>
                    <h1 class="page-title mb-2">Bienvenue <?= htmlspecialchars($_SESSION['prenom']) ?> 👋</h1>
                    <p class="hero-sub mb-0">
                        Suivez vos clubs, vos événements et les demandes en attente depuis un seul endroit.
                    </p>
                </div>

                <div class="col-lg-4">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Rechercher clubs, événements..." id="dashSearch" />
                    </div>
                </div>
            </div>
        </section>

        <!-- STATISTIQUES -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="stat-icon icon-1"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-value"><?= $nb_clubs_joined ?></div>
                        <div class="stat-label">Clubs rejoints</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="stat-icon icon-2"><i class="bi bi-calendar-event-fill"></i></div>
                        <div class="stat-value"><?= $nb_upcoming_events ?></div>
                        <div class="stat-label">Événements à venir</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="stat-icon icon-3"><i class="bi bi-clock-history"></i></div>
                        <div class="stat-value"><?= $nb_pending_requests ?></div>
                        <div class="stat-label">Demandes en attente</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="card stat-card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="stat-icon icon-4"><i class="bi bi-check2-circle"></i></div>
                        <div class="stat-value"><?= $nb_event_registrations ?></div>
                        <div class="stat-label">Inscriptions événements</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MES CLUBS + ÉVÉNEMENTS -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card dash-card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="section-title mb-0">Mes clubs</h2>
                            <a href="clubs.php" class="section-link">Voir tous</a>
                        </div>

                        <?php if (empty($my_clubs)): ?>
                            <p class="text-muted">Vous n'avez rejoint aucun club. <a href="clubs.php">Explorer les clubs</a></p>
                        <?php else: ?>
                            <?php for ($i = 0; $i < count($my_clubs); $i++): ?>
                                <?php $club = $my_clubs[$i]; ?>
                                <div class="list-item <?= $i == count($my_clubs)-1 ? 'mb-0 border-0 pb-0' : '' ?>">
                                    <div class="item-icon" style="background: <?= htmlspecialchars($club['couleur_gradient']) ?>"><?= $club['emoji'] ?></div>
                                    <div class="flex-grow-1">
                                        <div class="item-title"><?= htmlspecialchars($club['nom']) ?></div>
                                        <div class="item-sub"><?= $club['nb_membres'] ?> membres</div>
                                    </div>
                                    <?php if ($club['is_admin']): ?>
                                        <span class="badge-pill badge-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="badge-pill badge-member">Membre</span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card dash-card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="section-title mb-0">Prochains événements</h2>
                            <a href="events.php" class="section-link">Voir tous</a>
                        </div>

                        <?php if (empty($upcoming_events)): ?>
                            <p class="text-muted">Aucun événement à venir. <a href="events.php">Explorer les événements</a></p>
                        <?php else: ?>
                            <?php for ($i = 0; $i < count($upcoming_events); $i++): ?>
                                <?php
                                    $evt = $upcoming_events[$i];
                                    $d = new DateTime($evt['date_debut']);
                                    $day = $d->format('d');
                                    $month = $months_fr[intval($d->format('m')) - 1];
                                ?>
                                <div class="event-row <?= $i == count($upcoming_events)-1 ? 'mb-0 border-0 pb-0' : '' ?>">
                                    <div class="date-box">
                                        <div class="date-day"><?= $day ?></div>
                                        <div class="date-month"><?= $month ?></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="item-title"><?= htmlspecialchars($evt['titre']) ?></div>
                                        <div class="item-sub"><?= htmlspecialchars($evt['club_nom']) ?> · <?= htmlspecialchars($evt['lieu']) ?></div>
                                    </div>
                                    <?php if ($evt['is_registered'] > 0): ?>
                                        <span class="badge-pill badge-joined">Inscrit</span>
                                    <?php else: ?>
                                        <span class="badge-pill badge-upcoming">À venir</span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- DEMANDES EN ATTENTE (admin uniquement) -->
        <?php if ($_SESSION['role'] == 'admin_club' && !empty($pending_requests)): ?>
        <div class="card dash-card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title mb-0">Demandes d'adhésion en attente</h2>
                    <a href="admin.php" class="section-link">Gérer tout</a>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle requests-table mb-0">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Club</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_requests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar-sm"><?= strtoupper(substr($req['prenom'], 0, 1) . substr($req['nom'], 0, 1)) ?></span>
                                            <span><?= htmlspecialchars($req['prenom'] . ' ' . $req['nom']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($req['club_nom']) ?></td>
                                    <td><?= date('d M Y', strtotime($req['date_demande'])) ?></td>
                                    <td>
                                        <form action="../actions/membership_respond.php" method="POST" class="d-inline">
                                            <input type="hidden" name="membre_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="accepter">
                                            <button type="submit" class="btn btn-success btn-sm me-2">Accepter</button>
                                        </form>
                                        <form action="../actions/membership_respond.php" method="POST" class="d-inline">
                                            <input type="hidden" name="membre_id" value="<?= $req['id'] ?>">
                                            <input type="hidden" name="action" value="refuser">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Refuser</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

<?php include '../includes/footer.php'; ?>