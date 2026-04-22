<?php
// ============================================
// Page de détails d'un événement
// ============================================

// Inclure la connexion à la base de données
require_once '../actions/connexion.php';
// Démarrer la session
require_once '../actions/auth_check.php';

// Récupérer l'id de l'événement depuis l'URL
$event_id = intval($_GET['id']);
if ($event_id <= 0) {
    header("Location: events.php");
    exit();
}

// Vérifier si l'utilisateur est connecté
$logged_in = isset($_SESSION['user_id']);
$user_id = 0;
if ($logged_in) {
    $user_id = $_SESSION['user_id'];
}

// Récupérer les informations de l'événement
$stmt = $cnx->prepare("SELECT e.*, c.nom as club_nom, c.emoji, c.admin_id, (SELECT COUNT(*) FROM inscriptions_evenements WHERE evenement_id = e.id) as nb_inscrits FROM evenements e JOIN clubs c ON e.club_id = c.id WHERE e.id = :id");
$stmt->bindParam(':id', $event_id);
$stmt->execute();
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: events.php");
    exit();
}

// Vérifier si l'utilisateur est inscrit
$is_registered = false;
if ($logged_in) {
    $stmt = $cnx->prepare("SELECT id FROM inscriptions_evenements WHERE user_id = :uid AND evenement_id = :eid");
    $stmt->bindParam(':uid', $user_id);
    $stmt->bindParam(':eid', $event_id);
    $stmt->execute();
    $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($inscription) {
        $is_registered = true;
    }
}

// Récupérer la liste des participants
$stmt = $cnx->prepare("SELECT u.prenom, u.nom, u.filiere FROM inscriptions_evenements ie JOIN users u ON ie.user_id = u.id WHERE ie.evenement_id = :eid ORDER BY ie.date_inscription DESC");
$stmt->bindParam(':eid', $event_id);
$stmt->execute();
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer les dates
$months_fr_upper = array('JANV','FÉVR','MARS','AVR','MAI','JUIN','JUIL','AOÛT','SEPT','OCT','NOV','DÉC');
$d_start = new DateTime($event['date_debut']);
$d_end = new DateTime($event['date_fin']);
$hero_day = $d_start->format('d');
$hero_month = $months_fr_upper[intval($d_start->format('m')) - 1];

// Définir le titre et le style de la page
$page_title = 'UniClubs - ' . $event['titre'];
$page_css = 'css/styleeventdetails.css';
$active_page = 'events';

// Inclure le header
include '../includes/header.php';
?>

        <section class="detail-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-md-auto text-center">
                    <div class="date-big mx-auto">
                        <div class="date-day"><?= $hero_day ?></div>
                        <div class="date-month"><?= $hero_month ?></div>
                    </div>
                </div>

                <div class="col">
                    <span class="badge hero-badge mb-3">Événement</span>
                    <h1 class="page-title mb-2"><?= htmlspecialchars($event['titre']) ?></h1>
                    <p class="hero-sub mb-3"><?= htmlspecialchars($event['description']) ?></p>

                    <div class="hero-meta d-flex flex-wrap gap-3">
                        <span><i class="bi bi-building"></i> <?= htmlspecialchars($event['club_nom']) ?></span>
                        <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($event['lieu']) ?></span>
                        <span><i class="bi bi-clock-fill"></i> <?= $d_start->format('H:i') ?> - <?= $d_end->format('H:i') ?></span>
                    </div>
                </div>

                <div class="col-lg-auto">
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($logged_in): ?>
                            <?php if ($is_registered): ?>
                                <form action="../actions/event_unregister.php" method="POST">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <button type="submit" class="btn btn-danger-ghost">Annuler l'inscription</button>
                                </form>
                            <?php else: ?>
                                <form action="../actions/event_register.php" method="POST">
                                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                    <button type="submit" class="btn btn-accent">S'inscrire</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-accent">Se connecter</a>
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
                        <p class="section-text mb-0"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    </div>
                </div>

                <!-- Participants -->
                <div class="card detail-card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h2 class="section-title mb-4">Participants (<?= count($participants) ?>)</h2>

                        <?php if (empty($participants)): ?>
                            <p class="text-muted">Aucun participant inscrit pour le moment.</p>
                        <?php else: ?>
                            <?php for ($i = 0; $i < count($participants); $i++): ?>
                                <?php $p = $participants[$i]; ?>
                                <div class="participant-item <?= $i == count($participants)-1 ? 'mb-0 border-0 pb-0' : '' ?>">
                                    <span class="participant-avatar"><?= strtoupper(substr($p['prenom'], 0, 1) . substr($p['nom'], 0, 1)) ?></span>
                                    <div>
                                        <div class="item-title"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></div>
                                        <div class="item-sub"><?php if (!empty($p['filiere'])) { echo htmlspecialchars($p['filiere']); } else { echo 'Étudiant'; } ?></div>
                                    </div>
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
                            <span class="info-label">Club organisateur</span>
                            <span class="info-value"><?= htmlspecialchars($event['club_nom']) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Lieu</span>
                            <span class="info-value"><?= htmlspecialchars($event['lieu']) ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Début</span>
                            <span class="info-value"><?= $d_start->format('d M Y - H:i') ?></span>
                        </div>

                        <div class="info-row">
                            <span class="info-label">Fin</span>
                            <span class="info-value"><?= $d_end->format('d M Y - H:i') ?></span>
                        </div>

                        <div class="info-row mb-0 border-0 pb-0">
                            <span class="info-label">Participants</span>
                            <span class="status-pill active-pill"><?= $event['nb_inscrits'] ?> / <?= $event['max_participants'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="card detail-card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h3 class="section-title mb-4">Actions rapides</h3>

                        <div class="d-grid gap-2">
                            <?php if ($logged_in): ?>
                                <?php if ($is_registered): ?>
                                    <form action="../actions/event_unregister.php" method="POST">
                                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                        <button type="submit" class="btn btn-danger-ghost w-100">Annuler l'inscription</button>
                                    </form>
                                <?php else: ?>
                                    <form action="../actions/event_register.php" method="POST">
                                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                        <button type="submit" class="btn btn-accent w-100">S'inscrire</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="events.php" class="btn btn-secondary-ghost">Retour aux événements</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>
