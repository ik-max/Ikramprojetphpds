<?php
// ============================================
// Page de détails d'un club
// ============================================

// Inclure la connexion à la base de données
require_once '../actions/connexion.php';
// Démarrer la session
require_once '../actions/auth_check.php';

// Récupérer l'id du club depuis l'URL (?id=...)
$club_id = intval($_GET['id']);
if ($club_id <= 0) {
    header("Location: clubs.php");
    exit();
}

// Vérifier si l'utilisateur est connecté
$logged_in = isset($_SESSION['user_id']);
$user_id = 0;
if ($logged_in) {
    $user_id = $_SESSION['user_id'];
}

// Récupérer les informations du club
$stmt = $cnx->prepare("SELECT c.*, u.prenom as admin_prenom, u.nom as admin_nom, (SELECT COUNT(*) FROM membres WHERE club_id = c.id AND statut = 'accepte') as nb_membres, (SELECT COUNT(*) FROM evenements WHERE club_id = c.id) as nb_events FROM clubs c JOIN users u ON c.admin_id = u.id WHERE c.id = :id");
$stmt->bindParam(':id', $club_id);
$stmt->execute();
$club = $stmt->fetch(PDO::FETCH_ASSOC);

// Si le club n'existe pas, rediriger
if (!$club) {
    header("Location: clubs.php");
    exit();
}

// Vérifier le statut de l'utilisateur dans ce club
$is_member = false;
$is_pending = false;
$is_club_admin = false;

if ($logged_in) {
    // Vérifier si l'utilisateur est l'admin du club
    if ($club['admin_id'] == $user_id) {
        $is_club_admin = true;
    }

    // Vérifier l'adhésion
    $stmt = $cnx->prepare("SELECT statut FROM membres WHERE user_id = :uid AND club_id = :cid");
    $stmt->bindParam(':uid', $user_id);
    $stmt->bindParam(':cid', $club_id);
    $stmt->execute();
    $adhesion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($adhesion) {
        if ($adhesion['statut'] == 'accepte') {
            $is_member = true;
        } elseif ($adhesion['statut'] == 'en_attente') {
            $is_pending = true;
        }
    }
}

// Récupérer les événements du club
$stmt = $cnx->prepare("SELECT * FROM evenements WHERE club_id = :cid ORDER BY date_debut ASC");
$stmt->bindParam(':cid', $club_id);
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les membres récents du club
$stmt = $cnx->prepare("SELECT u.prenom, u.nom FROM membres m JOIN users u ON m.user_id = u.id WHERE m.club_id = :cid AND m.statut = 'accepte' ORDER BY m.date_demande DESC LIMIT 5");
$stmt->bindParam(':cid', $club_id);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tableau des mois en français
$months_fr = array('Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc');

// Définir le titre et le style de la page
$page_title = 'UniClubs - ' . $club['nom'];
$page_css = 'css/styleclubdetails.css';
$active_page = 'clubs';

// Inclure le header
include '../includes/header.php';
?>

        <section class="detail-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-md-auto text-center">
                    <div class="club-logo mx-auto"><?= $club['emoji'] ?></div>
                </div>

                <div class="col">
                    <span class="badge hero-badge mb-3">Club</span>
                    <h1 class="page-title mb-2"><?= htmlspecialchars($club['nom']) ?></h1>
                    <p class="hero-sub mb-3"><?= htmlspecialchars($club['description']) ?></p>

                    <div class="hero-meta d-flex flex-wrap gap-3">
                        <span><i class="bi bi-person-badge-fill"></i> Responsable: <?= htmlspecialchars($club['admin_prenom'] . ' ' . $club['admin_nom']) ?></span>
                        <span><i class="bi bi-people-fill"></i> <?= $club['nb_membres'] ?> membres</span>
                        <span><i class="bi bi-calendar-event-fill"></i> <?= $club['nb_events'] ?> événements</span>
                    </div>
                </div>

                <div class="col-lg-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!$logged_in): ?>
                            <a href="login.php" class="btn btn-accent">Se connecter pour rejoindre</a>
                        <?php elseif ($is_club_admin): ?>
                            <a href="admin.php" class="btn btn-accent">Gérer le club</a>
                        <?php elseif ($is_member): ?>
                            <form action="../actions/membership_leave.php" method="POST" onsubmit="return confirm('Voulez-vous vraiment quitter ce club ?')">
                                <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                <button type="submit" class="btn btn-danger-ghost">Quitter le club</button>
                            </form>
                        <?php elseif ($is_pending): ?>
                            <button class="btn btn-secondary-ghost" disabled>Demande en attente...</button>
                        <?php else: ?>
                            <form action="../actions/membership_request.php" method="POST">
                                <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                <button type="submit" class="btn btn-accent">Rejoindre</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Description -->
                <div class="card detail-card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h2 class="section-title mb-3">Description</h2>
                        <p class="section-text mb-0"><?= nl2br(htmlspecialchars($club['description'])) ?></p>
                    </div>
                </div>

                <!-- Événements -->
                <div class="card detail-card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="section-title mb-0">Événements du club</h2>
                            <a href="events.php" class="section-link">Voir tous</a>
                        </div>

                        <?php if (empty($events)): ?>
                            <p class="text-muted">Aucun événement pour le moment.</p>
                        <?php else: ?>
                            <?php for ($i = 0; $i < count($events); $i++): ?>
                                <?php
                                    $evt = $events[$i];
                                    $d = new DateTime($evt['date_debut']);
                                    $day = $d->format('d');
                                    $month = $months_fr[intval($d->format('m')) - 1];
                                    $time_start = $d->format('H:i');
                                    $time_end = (new DateTime($evt['date_fin']))->format('H:i');
                                ?>
                                <div class="event-item <?= $i == count($events)-1 ? 'mb-0 border-0 pb-0' : '' ?>">
                                    <div class="date-box">
                                        <div class="date-day"><?= $day ?></div>
                                        <div class="date-month"><?= $month ?></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="item-title"><?= htmlspecialchars($evt['titre']) ?></div>
                                        <div class="item-sub"><?= htmlspecialchars($evt['lieu']) ?> · <?= $time_start ?> - <?= $time_end ?></div>
                                    </div>
                                    <a href="event_details.php?id=<?= $evt['id'] ?>" class="btn btn-secondary-ghost btn-sm">Détails</a>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Informations -->
                <div class="card detail-card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h3 class="section-title mb-4">Informations</h3>

                        <div class="info-row">
                            <span class="info-label">Catégorie</span>
                            <span class="info-value"><?= htmlspecialchars(ucfirst($club['categorie'])) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Créé le</span>
                            <span class="info-value"><?= date('d M Y', strtotime($club['created_at'])) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Responsable</span>
                            <span class="info-value"><?= htmlspecialchars($club['admin_prenom'] . ' ' . $club['admin_nom']) ?></span>
                        </div>

                        <div class="info-row mb-0 border-0 pb-0">
                            <span class="info-label">Statut</span>
                            <span class="status-pill active-pill">Actif</span>
                        </div>
                    </div>
                </div>

                <!-- Membres récents -->
                <div class="card detail-card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h3 class="section-title mb-4">Membres récents</h3>

                        <?php if (empty($members)): ?>
                            <p class="text-muted">Aucun membre pour le moment.</p>
                        <?php else: ?>
                            <?php for ($i = 0; $i < count($members); $i++): ?>
                                <?php $m = $members[$i]; ?>
                                <div class="member-item <?= $i == count($members)-1 ? 'mb-0 border-0 pb-0' : '' ?>">
                                    <span class="member-avatar"><?= strtoupper(substr($m['prenom'], 0, 1) . substr($m['nom'], 0, 1)) ?></span>
                                    <div>
                                        <div class="item-title"><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></div>
                                        <div class="item-sub">Membre</div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>
