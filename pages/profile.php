<?php
// ============================================
// Page Profil de l'utilisateur
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
$page_title = 'UniClubs - Profil';
$page_css = 'css/styleprofile.css';
$active_page = 'profile';

// Récupérer l'id de l'utilisateur
$user_id = $_SESSION['user_id'];

// Récupérer les données à jour depuis la base de données
$stmt = $cnx->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

// ---- STATISTIQUES ----

// Nombre de clubs
$stmt = $cnx->prepare("SELECT COUNT(*) as total FROM membres WHERE user_id = :uid AND statut = 'accepte'");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
$nb_clubs = $ligne['total'];

// Nombre d'événements
$stmt = $cnx->prepare("SELECT COUNT(*) as total FROM inscriptions_evenements WHERE user_id = :uid");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
$nb_events = $ligne['total'];

// ---- MES CLUBS ----
$stmt = $cnx->prepare("SELECT c.*, m.statut, m.date_demande, CASE WHEN c.admin_id = :uid THEN 1 ELSE 0 END as is_admin FROM membres m JOIN clubs c ON m.club_id = c.id WHERE m.user_id = :uid2 ORDER BY m.date_demande DESC");
$stmt->bindParam(':uid', $user_id);
$stmt->bindParam(':uid2', $user_id);
$stmt->execute();
$my_clubs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- ACTIVITÉ RÉCENTE ----
$stmt = $cnx->prepare("SELECT e.titre, c.nom as club_nom, ie.date_inscription FROM inscriptions_evenements ie JOIN evenements e ON ie.evenement_id = e.id JOIN clubs c ON e.club_id = c.id WHERE ie.user_id = :uid ORDER BY ie.date_inscription DESC LIMIT 5");
$stmt->bindParam(':uid', $user_id);
$stmt->execute();
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les initiales du nom
$initiales = strtoupper(substr($data['prenom'], 0, 1) . substr($data['nom'], 0, 1));

// Inclure le header
include '../includes/header.php';
?>

        <!-- HERO -->
        <section class="profile-hero p-4 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-md-auto text-center">
                    <div class="profile-avatar mx-auto"><?= $initiales ?></div>
                </div>

                <div class="col">
                    <span class="badge badge-accent mb-3">Mon Profil</span>
                    <h1 class="page-title mb-2"><?= htmlspecialchars($data['prenom'] . ' ' . $data['nom']) ?></h1>
                    <p class="hero-mail mb-1"><?= htmlspecialchars($data['email']) ?></p>
                    <p class="hero-sub mb-0"><?php if (!empty($data['filiere'])) { echo htmlspecialchars($data['filiere']); } else { echo 'Filière non renseignée'; } ?></p>
                </div>

                <div class="col-lg-auto ms-lg-auto">
                    <div class="row g-4 text-center profile-stats">
                        <div class="col-4">
                            <div class="stat-number"><?= $nb_clubs ?></div>
                            <div class="stat-text">Clubs</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-number"><?= $nb_events ?></div>
                            <div class="stat-text">Événements</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-number"><?= count($my_clubs) ?></div>
                            <div class="stat-text">Adhésions</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Modifier le profil -->
                <div class="card profile-card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="section-title mb-4">
                            <i class="bi bi-pencil-fill me-2 icon-accent"></i>
                            Modifier mon profil
                        </h2>

                        <form action="../actions/profile_update.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($data['prenom']) ?>" required />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Nom</label>
                                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($data['nom']) ?>" required />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Adresse email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($data['email']) ?>" required />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Filière / Département</label>
                                    <input type="text" name="filiere" class="form-control" value="<?= htmlspecialchars($data['filiere']) ?>" placeholder="Ex: Génie Informatique - 2ème année" />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Bio</label>
                                    <textarea name="bio" class="form-control" rows="4" placeholder="Parlez de vous..."><?= htmlspecialchars($data['bio']) ?></textarea>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-accent px-4">Enregistrer les modifications</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Changer le mot de passe -->
                <div class="card profile-card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <h2 class="section-title mb-4">
                            <i class="bi bi-lock-fill me-2 text-warning"></i>
                            Changer le mot de passe
                        </h2>

                        <form action="password_update.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Mot de passe actuel</label>
                                <input type="password" name="old_password" class="form-control" placeholder="••••••••" required />
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nouveau mot de passe</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required minlength="8" />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Confirmer</label>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required minlength="8" />
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-secondary-ghost px-4">Mettre à jour</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Mes Clubs -->
                <div class="card profile-card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h3 class="side-title mb-4">
                            <i class="bi bi-mortarboard-fill me-2 icon-accent"></i>
                            Mes Clubs
                        </h3>

                        <?php if (empty($my_clubs)): ?>
                            <p class="text-muted">Aucun club. <a href="clubs.php">Explorer les clubs</a></p>
                        <?php else: ?>
                            <?php for ($i = 0; $i < count($my_clubs); $i++): ?>
                                <?php $club = $my_clubs[$i]; ?>
                                <div class="club-item <?= $i == count($my_clubs)-1 ? 'mb-0' : '' ?>">
                                    <div class="club-icon"><?= $club['emoji'] ?></div>
                                    <div class="flex-grow-1">
                                        <div class="club-name"><?= htmlspecialchars($club['nom']) ?></div>
                                        <div class="club-sub">
                                            <?php if ($club['statut'] == 'accepte'): ?>
                                                Membre depuis <?= date('M Y', strtotime($club['date_demande'])) ?>
                                            <?php elseif ($club['statut'] == 'en_attente'): ?>
                                                Demande en cours...
                                            <?php else: ?>
                                                Demande refusée
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($club['statut'] == 'accepte'): ?>
                                        <span class="status-pill active-pill">Actif</span>
                                    <?php elseif ($club['statut'] == 'en_attente'): ?>
                                        <span class="status-pill wait-pill">En attente</span>
                                    <?php else: ?>
                                        <span class="pill pill-red">Refusé</span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Activité récente -->
                <div class="card profile-card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h3 class="side-title mb-4">
                            <i class="bi bi-clock-history me-2 icon-accent"></i>
                            Activité récente
                        </h3>

                        <?php if (empty($recent_activity)): ?>
                            <p class="text-muted">Aucune activité récente.</p>
                        <?php else: ?>
                            <?php for ($i = 0; $i < count($recent_activity); $i++): ?>
                                <?php $act = $recent_activity[$i]; ?>
                                <div class="activity-item <?= $i == count($recent_activity)-1 ? 'mb-0 border-0 pb-0' : '' ?>">
                                    <span class="dot blue"></span>
                                    <div>
                                        <div class="activity-main">Inscrit à <?= htmlspecialchars($act['titre']) ?></div>
                                        <div class="activity-sub"><?= htmlspecialchars($act['club_nom']) ?> · <?= date('d M Y', strtotime($act['date_inscription'])) ?></div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>