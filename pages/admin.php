<?php
// ============================================
// Page Administration pour les responsables de clubs
// ============================================

// Inclure la connexion à la base de données
require_once '../actions/connexion.php';
// Démarrer la session
require_once '../actions/auth_check.php';

// Vérifier que l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin_club') {
    header("Location: dashboard.php");
    exit();
}

// Définir le titre et le style de la page
$page_title = 'UniClubs - Espace Admin';
$page_css = 'css/styleadmin.css';
$page_js = 'js/admin.js';
$active_page = 'admin';

$admin_id = $_SESSION['user_id'];

// 1. Récupérer les clubs gérés par cet admin
$stmt = $cnx->prepare("SELECT * FROM clubs WHERE admin_id = :admin_id ORDER BY created_at DESC");
$stmt->bindParam(':admin_id', $admin_id);
$stmt->execute();
$my_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau avec les IDs des clubs pour pouvoir récupérer leurs données plus tard
$club_ids = array();
foreach ($my_clubs as $club) {
    if (!in_array($club['id'], $club_ids)) {
        $club_ids[] = $club['id'];
    }
}

// 2. Initialiser les tableaux pour les autres données
$pending_requests = array();
$upcoming_events = array();
$club_stats = array();

// Si l'admin a au moins un club
if (count($club_ids) > 0) {
    // Calculer des statistiques pour chaque club
    foreach ($my_clubs as $club) {
        $cid = $club['id'];
        
        // Membres actifs
        $stmt_m = $cnx->prepare("SELECT COUNT(*) as total FROM membres WHERE club_id = :cid AND statut = 'accepte'");
        $stmt_m->bindParam(':cid', $cid);
        $stmt_m->execute();
        $row_m = $stmt_m->fetch(PDO::FETCH_ASSOC);
        
        // Demandes en attente
        $stmt_p = $cnx->prepare("SELECT COUNT(*) as total FROM membres WHERE club_id = :cid AND statut = 'en_attente'");
        $stmt_p->bindParam(':cid', $cid);
        $stmt_p->execute();
        $row_p = $stmt_p->fetch(PDO::FETCH_ASSOC);
        
        // Total événements
        $stmt_e = $cnx->prepare("SELECT COUNT(*) as total FROM evenements WHERE club_id = :cid");
        $stmt_e->bindParam(':cid', $cid);
        $stmt_e->execute();
        $row_e = $stmt_e->fetch(PDO::FETCH_ASSOC);
        
        $club_stats[$cid] = array(
            'membres' => $row_m['total'],
            'attente' => $row_p['total'],
            'events' => $row_e['total']
        );
    }

    // Récupérer toutes les demandes en attente pour ces clubs
    // Note: Pour faire simple avec PDO et éviter IN(), on va faire une jointure classique
    $stmt = $cnx->prepare("SELECT m.*, u.prenom, u.nom, u.email, u.filiere, c.nom as club_nom 
                           FROM membres m 
                           JOIN users u ON m.user_id = u.id 
                           JOIN clubs c ON m.club_id = c.id 
                           WHERE c.admin_id = :admin_id AND m.statut = 'en_attente' 
                           ORDER BY m.date_demande DESC");
    $stmt->bindParam(':admin_id', $admin_id);
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les événements futurs pour ces clubs
    $stmt = $cnx->prepare("SELECT e.*, c.nom as club_nom, 
                           (SELECT COUNT(*) FROM inscriptions_evenements WHERE evenement_id = e.id) as nb_inscrits 
                           FROM evenements e 
                           JOIN clubs c ON e.club_id = c.id 
                           WHERE c.admin_id = :admin_id AND e.date_debut >= NOW() 
                           ORDER BY e.date_debut ASC");
    $stmt->bindParam(':admin_id', $admin_id);
    $stmt->execute();
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Inclure le header
include '../includes/header.php';
?>

        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <h1 class="page-title mb-1">Espace Administrateur</h1>
                <p class="text-muted mb-0">Gérez vos clubs, événements et membres</p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button class="btn btn-accent px-4" data-bs-toggle="modal" data-bs-target="#createClubModal">
                    <i class="bi bi-plus-lg me-2"></i>Nouveau Club
                </button>
            </div>
        </div>

        <!-- Onglets -->
        <ul class="nav nav-tabs custom-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#clubs-tab">
                    Mes Clubs (<?= count($my_clubs) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#requests-tab">
                    Demandes 
                    <?php if (count($pending_requests) > 0): ?>
                        <span class="badge" style="background:rgba(239,68,68,0.2);color:#F87171;"><?= count($pending_requests) ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#events-tab">
                    Événements (<?= count($upcoming_events) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- TAB 1 : MES CLUBS -->
            <div class="tab-pane fade show active" id="clubs-tab">
                <?php if (empty($my_clubs)): ?>
                    <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                        <div class="card-body">
                            <h3 class="fw-bold mb-3">Vous ne gérez aucun club</h3>
                            <p class="text-muted mb-4">Créez votre premier club pour commencer à gérer votre communauté.</p>
                            <button class="btn btn-accent" data-bs-toggle="modal" data-bs-target="#createClubModal">Créer un club</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($my_clubs as $club): ?>
                            <?php $stats = $club_stats[$club['id']]; ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card admin-club-card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                                    <div class="card-header border-0 py-3" style="background: <?= htmlspecialchars($club['couleur_gradient']) ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="pill pill-blue"><?= htmlspecialchars(ucfirst($club['categorie'])) ?></div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-secondary-ghost rounded-circle" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end" style="background:var(--surface2);border:1px solid var(--border);">
                                                    <li><a class="dropdown-item" href="club_details.php?id=<?= $club['id'] ?>">Voir la page publique</a></li>
                                                    <li><button class="dropdown-item" onclick="editClub(<?= $club['id'] ?>, '<?= htmlspecialchars(addslashes($club['nom'])) ?>', '<?= htmlspecialchars(addslashes($club['description'])) ?>', '<?= htmlspecialchars($club['categorie']) ?>', '<?= htmlspecialchars($club['emoji']) ?>')">Modifier</button></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="../actions/club_delete.php" method="POST" onsubmit="return confirm('Attention ! Cela supprimera le club, tous ses membres et tous ses événements. Poursuivre ?')">
                                                            <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                                            <button type="submit" class="dropdown-item text-danger">Supprimer le club</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <span class="fs-1 me-3"><?= $club['emoji'] ?></span>
                                            <h3 class="h5 fw-bold mb-0"><?= htmlspecialchars($club['nom']) ?></h3>
                                        </div>
                                        
                                        <p class="text-muted small mb-4 text-truncate"><?= htmlspecialchars($club['description']) ?></p>
                                        
                                        <div class="row text-center g-2 mt-auto">
                                            <div class="col-4">
                                                <div class="p-2 rounded-3" style="background:var(--surface2);border:1px solid var(--border);">
                                                    <div class="fw-bold fs-5" style="color:#60A5FA;"><?= $stats['membres'] ?></div>
                                                    <div class="small" style="color:var(--text-muted);">Membres</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 rounded-3" style="background:<?= $stats['attente'] > 0 ? 'rgba(239,68,68,0.1)' : 'var(--surface2)' ?>;border:1px solid <?= $stats['attente'] > 0 ? 'rgba(239,68,68,0.2)' : 'var(--border)' ?>;">
                                                    <div class="fw-bold fs-5" style="color:<?= $stats['attente'] > 0 ? '#F87171' : 'var(--text)' ?>;"><?= $stats['attente'] ?></div>
                                                    <div class="small" style="color:<?= $stats['attente'] > 0 ? '#F87171' : 'var(--text-muted)' ?>;">Demandes</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="p-2 rounded-3" style="background:var(--surface2);border:1px solid var(--border);">
                                                    <div class="fw-bold fs-5" style="color:#34D399;"><?= $stats['events'] ?></div>
                                                    <div class="small" style="color:var(--text-muted);">Événements</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB 2 : DEMANDES -->
            <div class="tab-pane fade" id="requests-tab">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h2 class="h4 fw-bold mb-4">Demandes d'adhésion en attente</h2>
                        
                        <?php if (empty($pending_requests)): ?>
                            <p class="text-muted text-center py-4">Aucune demande en attente.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Filière</th>
                                            <th>Club</th>
                                            <th>Date</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_requests as $req): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="avatar-sm flex-shrink-0"><?= strtoupper(substr($req['prenom'], 0, 1) . substr($req['nom'], 0, 1)) ?></div>
                                                        <div>
                                                            <div class="fw-semibold"><?= htmlspecialchars($req['prenom'] . ' ' . $req['nom']) ?></div>
                                                            <div class="small text-muted"><?= htmlspecialchars($req['email']) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($req['filiere'] ?: 'Non spécifiée') ?></td>
                                                <td><span class="pill pill-blue"><?= htmlspecialchars($req['club_nom']) ?></span></td>
                                                <td><?= date('d/m/Y', strtotime($req['date_demande'])) ?></td>
                                                <td class="text-end">
                                                    <form action="../actions/membership_respond.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="membre_id" value="<?= $req['id'] ?>">
                                                        <input type="hidden" name="action" value="accepter">
                                                        <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill me-1">Accepter</button>
                                                    </form>
                                                    <form action="../actions/membership_respond.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="membre_id" value="<?= $req['id'] ?>">
                                                        <input type="hidden" name="action" value="refuser">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-pill">Refuser</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 3 : ÉVÉNEMENTS -->
            <div class="tab-pane fade" id="events-tab">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="h4 fw-bold mb-0">Événements programmés</h2>
                            <button class="btn btn-accent btn-sm px-3" data-bs-toggle="modal" data-bs-target="#createEventModal">
                                + Nouvel événement
                            </button>
                        </div>
                        
                        <?php if (empty($upcoming_events)): ?>
                            <p class="text-muted text-center py-4">Aucun événement à venir.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Événement</th>
                                            <th>Club</th>
                                            <th>Date</th>
                                            <th>Participants</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcoming_events as $evt): ?>
                                            <?php
                                                $d_start = new DateTime($evt['date_debut']);
                                                $d_end = new DateTime($evt['date_fin']);
                                                $is_full = $evt['nb_inscrits'] >= $evt['max_participants'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($evt['titre']) ?></div>
                                                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($evt['lieu']) ?></div>
                                                </td>
                                                <td><span class="pill pill-blue"><?= htmlspecialchars($evt['club_nom']) ?></span></td>
                                                <td>
                                                    <div><?= $d_start->format('d/m/Y') ?></div>
                                                    <div class="small text-muted"><?= $d_start->format('H:i') ?> - <?= $d_end->format('H:i') ?></div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="progress flex-grow-1" style="height: 8px;">
                                                            <div class="progress-bar <?= $is_full ? 'bg-danger' : 'bg-success' ?>" 
                                                                 style="width: <?= ($evt['nb_inscrits'] / floatval($evt['max_participants'])) * 100 ?>%"></div>
                                                        </div>
                                                        <span class="small <?= $is_full ? 'text-danger fw-bold' : 'text-muted' ?>">
                                                            <?= $evt['nb_inscrits'] ?>/<?= $evt['max_participants'] ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-secondary-ghost btn-sm rounded-pill mb-1" onclick="editEvent(<?= $evt['id'] ?>, '<?= htmlspecialchars(addslashes($evt['titre'])) ?>', '<?= htmlspecialchars(addslashes($evt['description'])) ?>', <?= $evt['club_id'] ?>, '<?= htmlspecialchars(addslashes($evt['lieu'])) ?>', '<?= $evt['date_debut'] ?>', '<?= $evt['date_fin'] ?>', <?= $evt['max_participants'] ?>)">Modifier</button>
                                                    <form action="../actions/event_delete.php" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet événement ?')">
                                                        <input type="hidden" name="event_id" value="<?= $evt['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill mb-1">Supprimer</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL : Créer un Club -->
        <div class="modal fade" id="createClubModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Créer un nouveau club</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../actions/club_create.php" method="POST">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-9">
                                    <label class="form-label fw-medium">Nom du club</label>
                                    <input type="text" name="nom" class="form-control" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Emoji</label>
                                    <input type="text" name="emoji" class="form-control" value="🌟" maxlength="5" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Catégorie</label>
                                    <select name="categorie" class="form-select" required>
                                        <option value="scientifique">Scientifique & Tech</option>
                                        <option value="culturel">Culturel & Arts</option>
                                        <option value="sportif">Sportif</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Description</label>
                                    <textarea name="description" class="form-control" rows="4" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3">
                            <button type="button" class="btn btn-secondary-ghost px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-accent px-4 py-2">Créer le club</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL : Modifier un Club -->
        <div class="modal fade" id="editClubModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Modifier le club</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../actions/club_update.php" method="POST">
                        <input type="hidden" name="club_id" id="edit_club_id">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-9">
                                    <label class="form-label fw-medium">Nom du club</label>
                                    <input type="text" name="nom" id="edit_club_nom" class="form-control" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Emoji</label>
                                    <input type="text" name="emoji" id="edit_club_emoji" class="form-control" maxlength="5" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Catégorie</label>
                                    <select name="categorie" id="edit_club_categorie" class="form-select" required>
                                        <option value="scientifique">Scientifique & Tech</option>
                                        <option value="culturel">Culturel & Arts</option>
                                        <option value="sportif">Sportif</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Description</label>
                                    <textarea name="description" id="edit_club_description" class="form-control" rows="4" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3">
                            <button type="button" class="btn btn-secondary-ghost px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-accent px-4 py-2">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL : Créer un Événement -->
        <div class="modal fade" id="createEventModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Créer un événement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../actions/event_create.php" method="POST">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Titre de l'événement</label>
                                    <input type="text" name="titre" class="form-control" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Club organisateur</label>
                                    <select name="club_id" class="form-select" required>
                                        <option value="">Sélectionner un club...</option>
                                        <?php foreach ($my_clubs as $club): ?>
                                            <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Lieu</label>
                                    <input type="text" name="lieu" class="form-control" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Début</label>
                                    <input type="datetime-local" name="date_debut" class="form-control" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Fin</label>
                                    <input type="datetime-local" name="date_fin" class="form-control" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Places max limitées à</label>
                                    <div class="input-group">
                                        <input type="number" name="max_participants" class="form-control" min="1" value="50" required />
                                        <span class="input-group-text">personnes</span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Description</label>
                                    <textarea name="description" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3">
                            <button type="button" class="btn btn-secondary-ghost px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-accent px-4 py-2">Planifier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL : Modifier un Événement -->
        <div class="modal fade" id="editEventModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content rounded-4 border-0">
                    <div class="modal-header border-bottom px-4 py-3">
                        <h5 class="modal-title fw-bold">Modifier l'événement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../actions/event_update.php" method="POST">
                        <input type="hidden" name="event_id" id="edit_evt_id">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Titre de l'événement</label>
                                    <input type="text" name="titre" id="edit_evt_titre" class="form-control" required />
                                </div>
                                <!-- Le club ne peut pas être changé -->
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Lieu</label>
                                    <input type="text" name="lieu" id="edit_evt_lieu" class="form-control" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Places max limitées à</label>
                                    <input type="number" name="max_participants" id="edit_evt_max" class="form-control" min="1" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Début</label>
                                    <input type="datetime-local" name="date_debut" id="edit_evt_debut" class="form-control" required />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Fin</label>
                                    <input type="datetime-local" name="date_fin" id="edit_evt_fin" class="form-control" required />
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Description</label>
                                    <textarea name="description" id="edit_evt_desc" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 py-3">
                            <button type="button" class="btn btn-secondary-ghost px-4" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-accent px-4 py-2">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>