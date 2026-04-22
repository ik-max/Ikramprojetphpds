<?php
// ============================================
// Page de liste des clubs
// ============================================

// Inclure la connexion à la base de données
require_once '../actions/connexion.php';
// Démarrer la session
require_once '../actions/auth_check.php';

// Définir le titre et le style de la page
$page_title = 'UniClubs - Clubs';
$page_css = 'css/styleclub.css';
$page_js = 'js/clubs.js';
$active_page = 'clubs';

// Vérifier si l'utilisateur est connecté
$logged_in = isset($_SESSION['user_id']);
$user_id = 0;
if ($logged_in) {
    $user_id = $_SESSION['user_id'];
}

// Récupérer tous les clubs avec le nombre de membres et d'événements
$result = $cnx->query("SELECT c.*, u.prenom as admin_prenom, u.nom as admin_nom, (SELECT COUNT(*) FROM membres WHERE club_id = c.id AND statut = 'accepte') as nb_membres, (SELECT COUNT(*) FROM evenements WHERE club_id = c.id) as nb_events FROM clubs c JOIN users u ON c.admin_id = u.id ORDER BY c.created_at DESC");
$clubs = $result->fetchAll(PDO::FETCH_ASSOC);

// Pour chaque club, vérifier le statut de l'utilisateur connecté
// On va parcourir les clubs et ajouter les infos de membership
if ($logged_in) {
    // Récupérer toutes les adhésions de l'utilisateur
    $stmt = $cnx->prepare("SELECT club_id, statut FROM membres WHERE user_id = :uid");
    $stmt->bindParam(':uid', $user_id);
    $stmt->execute();
    $mes_adhesions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Créer un tableau simple : club_id => statut
    $statut_par_club = array();
    foreach ($mes_adhesions as $adhesion) {
        $statut_par_club[$adhesion['club_id']] = $adhesion['statut'];
    }
}

// Inclure le header
include '../includes/header.php';
?>

        <!-- Header -->
        <section class="clubs-hero p-4 p-lg-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge hero-badge mb-3">Explorer</span>
                    <h1 class="page-title mb-2">Découvrir les clubs</h1>
                    <p class="hero-sub mb-0">
                        Recherchez un club, consultez ses informations et envoyez une demande d'adhésion.
                    </p>
                </div>

                <?php if ($logged_in && $_SESSION['role'] == 'admin_club'): ?>
                <div class="col-lg-4 text-lg-end">
                    <button class="btn btn-accent px-4" data-bs-toggle="modal" data-bs-target="#createClubModal">+ Créer un club</button>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Recherche / Filtre -->
        <div class="card search-card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-4">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 rounded-end-4" placeholder="Rechercher un club..." oninput="filterClubs()" />
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <button class="btn btn-dark filter-btn active" onclick="setFilter('tous', this)">Tous</button>
                            <?php if ($logged_in): ?>
                            <button class="btn btn-outline-dark filter-btn" onclick="setFilter('mes-clubs', this)">Mes clubs</button>
                            <?php endif; ?>
                            <button class="btn btn-outline-dark filter-btn" onclick="setFilter('culturel', this)">Culturel</button>
                            <button class="btn btn-outline-dark filter-btn" onclick="setFilter('scientifique', this)">Scientifique</button>
                            <button class="btn btn-outline-dark filter-btn" onclick="setFilter('sportif', this)">Sportif</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des clubs -->
        <div class="row g-4" id="clubsGrid">
            <?php if (empty($clubs)): ?>
                <div class="col-12">
                    <p class="text-muted text-center py-5">Aucun club n'a encore été créé.</p>
                </div>
            <?php else: ?>
                <?php foreach ($clubs as $club): ?>
                    <?php
                        // Déterminer le statut de l'utilisateur pour ce club
                        $is_member = false;
                        $is_pending = false;
                        $is_club_admin = false;
                        $data_member = 'no';

                        if ($logged_in) {
                            // Vérifier si l'utilisateur est l'admin de ce club
                            if ($club['admin_id'] == $user_id) {
                                $is_club_admin = true;
                                $data_member = 'admin';
                            }
                            // Vérifier le statut d'adhésion
                            elseif (isset($statut_par_club[$club['id']])) {
                                if ($statut_par_club[$club['id']] == 'accepte') {
                                    $is_member = true;
                                    $data_member = 'yes';
                                } elseif ($statut_par_club[$club['id']] == 'en_attente') {
                                    $is_pending = true;
                                }
                            }
                        }
                    ?>
                    <div class="col-md-6 col-xl-4 club-item" data-cat="<?= htmlspecialchars($club['categorie']) ?>" data-member="<?= $data_member ?>">
                        <div class="card club-card h-100 border-0 shadow-sm rounded-4">
                            <div class="club-cover" style="background: <?= htmlspecialchars($club['couleur_gradient']) ?>">
                                <div class="club-emoji"><?= $club['emoji'] ?></div>
                                <span class="category-label"><?= htmlspecialchars(ucfirst($club['categorie'])) ?></span>
                            </div>

                            <div class="card-body p-4">
                                <h5 class="club-title"><?= htmlspecialchars($club['nom']) ?></h5>
                                <p class="club-desc"><?= htmlspecialchars(substr($club['description'], 0, 120)) ?></p>

                                <div class="club-meta mb-3">
                                    <span><i class="bi bi-people-fill"></i> <?= $club['nb_membres'] ?> membres</span>
                                    <span><i class="bi bi-calendar-event-fill"></i> <?= $club['nb_events'] ?> événements</span>
                                </div>

                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <?php if ($is_club_admin): ?>
                                        <span class="status-pill sp-admin">Admin</span>
                                        <a href="admin.php" class="btn btn-outline-dark btn-sm">Gérer</a>
                                    <?php elseif ($is_member): ?>
                                        <span class="status-pill sp-member">Membre</span>
                                        <a href="club_details.php?id=<?= $club['id'] ?>" class="btn btn-outline-dark btn-sm">Voir détails</a>
                                    <?php elseif ($is_pending): ?>
                                        <span class="status-pill sp-open">En attente</span>
                                        <a href="club_details.php?id=<?= $club['id'] ?>" class="btn btn-outline-dark btn-sm">Voir détails</a>
                                    <?php else: ?>
                                        <span class="status-pill sp-open">Ouvert</span>
                                        <?php if ($logged_in): ?>
                                            <form action="../actions/membership_request.php" method="POST" class="d-inline">
                                                <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                                <button type="submit" class="btn btn-accent btn-sm">Rejoindre</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="login.php" class="btn btn-accent btn-sm">Rejoindre</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Modal Créer un Club -->
        <?php if ($logged_in && $_SESSION['role'] == 'admin_club'): ?>
        <div class="modal fade" id="createClubModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4">
                    <div class="modal-header border-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold">Créer un nouveau club</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../actions/club_create.php" method="POST">
                        <div class="modal-body px-4">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Nom du club</label>
                                    <input type="text" name="nom" class="form-control" placeholder="Ex: Club Informatique" required />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Emoji</label>
                                    <input type="text" name="emoji" class="form-control" placeholder="💻" value="📌" maxlength="5" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Décrivez votre club..." required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Catégorie</label>
                                    <select name="categorie" class="form-select" required>
                                        <option value="scientifique">Scientifique</option>
                                        <option value="culturel">Culturel</option>
                                        <option value="sportif">Sportif</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 px-4 pb-4">
                            <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-accent">Créer le club</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

<?php include '../includes/footer.php'; ?>