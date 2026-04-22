<?php
// ============================================
// Page de liste des événements
// ============================================

// Inclure la connexion à la base de données
require_once '../actions/connexion.php';
// Démarrer la session
require_once '../actions/auth_check.php';

// Définir le titre et le style de la page
$page_title = 'UniClubs - Événements';
$page_css = 'css/styleevent.css';
$page_js = 'js/events.js';
$active_page = 'events';

// Vérifier si l'utilisateur est connecté
$logged_in = isset($_SESSION['user_id']);
$user_id = 0;
if ($logged_in) {
    $user_id = $_SESSION['user_id'];
}

// Récupérer tous les événements avec les infos du club
$result = $cnx->query("SELECT e.*, c.nom as club_nom, c.emoji, c.admin_id, (SELECT COUNT(*) FROM inscriptions_evenements WHERE evenement_id = e.id) as nb_inscrits FROM evenements e JOIN clubs c ON e.club_id = c.id ORDER BY e.date_debut ASC");
$events = $result->fetchAll(PDO::FETCH_ASSOC);

// Si l'utilisateur est connecté, récupérer ses inscriptions
$mes_inscriptions = array();
if ($logged_in) {
    $stmt = $cnx->prepare("SELECT evenement_id FROM inscriptions_evenements WHERE user_id = :uid");
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Créer un tableau simple avec les ids des événements
    foreach ($inscriptions as $insc) {
        $mes_inscriptions[] = $insc['evenement_id'];
    }
}

// Récupérer le prochain événement pour la section héro
$result_hero = $cnx->query("SELECT e.*, c.nom as club_nom, c.emoji, (SELECT COUNT(*) FROM inscriptions_evenements WHERE evenement_id = e.id) as nb_inscrits FROM evenements e JOIN clubs c ON e.club_id = c.id WHERE e.date_debut >= NOW() ORDER BY e.date_debut ASC LIMIT 1");
$hero_event = $result_hero->fetch(PDO::FETCH_ASSOC);

// Récupérer la liste des clubs pour le filtre
$result_clubs = $cnx->query("SELECT id, nom FROM clubs ORDER BY nom");
$clubs_list = $result_clubs->fetchAll(PDO::FETCH_ASSOC);

// Tableau des mois en français
$months_fr = array('Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc');
$months_fr_upper = array('JANV','FÉVR','MARS','AVR','MAI','JUIN','JUIL','AOÛT','SEPT','OCT','NOV','DÉC');

$is_admin = $logged_in && $_SESSION['role'] == 'admin_club';

// Inclure le header
include '../includes/header.php';
?>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h1 class="page-title mb-1">Événements</h1>
                <p class="text-muted mb-0">Tous les événements organisés par les clubs</p>
            </div>
            <?php if ($is_admin): ?>
                <a href="admin.php" class="btn btn-accent px-4">+ Créer un événement</a>
            <?php endif; ?>
        </div>

        <!-- Événement à la une -->
        <?php if ($hero_event): ?>
            <?php
                $hd = new DateTime($hero_event['date_debut']);
                $hero_day = $hd->format('d');
                $hero_month_upper = $months_fr_upper[intval($hd->format('m')) - 1];
                $hero_time_start = $hd->format('H\h00');
                $hero_time_end = (new DateTime($hero_event['date_fin']))->format('H\h00');
            ?>
            <section class="hero-event p-4 mb-4">
                <div class="row align-items-center g-4">
                    <div class="col-md-2 text-center text-md-start">
                        <div class="hero-date mx-auto mx-md-0">
                            <div class="hero-day"><?= $hero_day ?></div>
                            <div class="hero-month"><?= $hero_month_upper ?></div>
                        </div>
                    </div>

                    <div class="col-md-10">
                        <span class="badge badge-accent mb-3">À ne pas manquer</span>
                        <h2 class="hero-title mb-3"><?= htmlspecialchars($hero_event['titre']) ?></h2>

                        <div class="meta-line mb-3">
                            <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($hero_event['lieu']) ?></span>
                            <span><i class="bi bi-clock-fill"></i> <?= $hero_time_start ?> – <?= $hero_time_end ?></span>
                            <span><i class="bi bi-people-fill"></i> <?= $hero_event['nb_inscrits'] ?> / <?= $hero_event['max_participants'] ?> places</span>
                        </div>

                        <p class="hero-text mb-4"><?= htmlspecialchars(substr($hero_event['description'], 0, 200)) ?></p>

                        <?php if ($logged_in): ?>
                            <form action="../actions/event_register.php" method="POST" class="d-inline">
                                <input type="hidden" name="event_id" value="<?= $hero_event['id'] ?>">
                                <button type="submit" class="btn btn-accent px-4">S'inscrire maintenant</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-accent px-4">Se connecter pour s'inscrire</a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Recherche/Filtre -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 search-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <input type="text" id="eventSearch" class="form-control rounded-3" placeholder="Rechercher un événement..." oninput="filterEvents()" />
                    </div>
                    <div class="col-md-4">
                        <select id="clubFilter" class="form-select rounded-3" onchange="filterEvents()">
                            <option value="">Tous les clubs</option>
                            <?php foreach ($clubs_list as $cl): ?>
                                <option value="<?= htmlspecialchars($cl['nom']) ?>"><?= htmlspecialchars($cl['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des événements -->
        <div class="row g-4" id="eventsGrid">
            <?php if (empty($events)): ?>
                <div class="col-12">
                    <p class="text-muted text-center py-5">Aucun événement n'a encore été créé.</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $evt): ?>
                    <?php
                        $ed = new DateTime($evt['date_debut']);
                        $evt_day = $ed->format('d');
                        $evt_month_upper = $months_fr_upper[intval($ed->format('m')) - 1];
                        $evt_time_start = $ed->format('H:i');
                        $evt_time_end = (new DateTime($evt['date_fin']))->format('H:i');
                        // Vérifier si l'utilisateur est inscrit à cet événement
                        $registered = in_array($evt['id'], $mes_inscriptions);
                    ?>
                    <div class="col-md-6 col-xl-4 event-item" data-club="<?= htmlspecialchars($evt['club_nom']) ?>">
                        <div class="card event-card h-100 border-0 shadow-sm rounded-4">
                            <div class="card-body">
                                <div class="small-date mb-3">
                                    <div class="small-day"><?= $evt_day ?></div>
                                    <div class="small-month"><?= $evt_month_upper ?></div>
                                </div>

                                <h5 class="event-title mb-1"><?= htmlspecialchars($evt['titre']) ?></h5>
                                <p class="club-text"><?= htmlspecialchars($evt['club_nom']) ?></p>

                                <p class="text-muted mb-2">
                                    <i class="bi bi-geo-alt-fill me-1"></i> <?= htmlspecialchars($evt['lieu']) ?>
                                </p>
                                <p class="text-muted mb-3">
                                    <i class="bi bi-clock-fill me-1"></i> <?= $evt_time_start ?> - <?= $evt_time_end ?>
                                </p>

                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="event_details.php?id=<?= $evt['id'] ?>" class="btn btn-dark btn-sm">Voir détails</a>
                                    <?php if ($logged_in): ?>
                                        <?php if ($registered): ?>
                                            <form action="../actions/event_unregister.php" method="POST" class="d-inline">
                                                <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Annuler</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="../actions/event_register.php" method="POST" class="d-inline">
                                                <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                                                <button type="submit" class="btn btn-accent btn-sm">S'inscrire</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

<?php include '../includes/footer.php'; ?>
