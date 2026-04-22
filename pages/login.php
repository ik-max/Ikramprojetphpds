<?php
// ============================================
// Page de connexion et inscription
// ============================================

// Inclure la connexion à la base de données
require_once '../actions/connexion.php';
// Démarrer la session
require_once '../actions/auth_check.php';

// Si l'utilisateur est déjà connecté, le rediriger
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin_club') {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// Définir le titre et le style de la page
$page_title = 'UniClubs - Connexion';
$page_css = 'css/stylelogin.css';
$active_page = 'login';

// Récupérer les statistiques
$result = $cnx->query("SELECT COUNT(*) as total FROM clubs");
$ligne = $result->fetch(PDO::FETCH_ASSOC);
$nb_clubs = $ligne['total'];

$result = $cnx->query("SELECT COUNT(*) as total FROM users");
$ligne = $result->fetch(PDO::FETCH_ASSOC);
$nb_users = $ligne['total'];

$result = $cnx->query("SELECT COUNT(*) as total FROM evenements");
$ligne = $result->fetch(PDO::FETCH_ASSOC);
$nb_events = $ligne['total'];

// Inclure le header
include '../includes/header.php';
?>

        <div class="row g-4 align-items-stretch">
            <!-- LEFT -->
            <div class="col-lg-6">
                <section class="login-hero h-100 p-4 p-lg-5">
                    <span class="badge hero-badge mb-3">UniClubs</span>
                    <h1 class="hero-title mb-3">
                        Gérez vos clubs <br />
                        universitaires <br />
                        avec style.
                    </h1>
                    <p class="hero-sub mb-4">
                        Une plateforme centralisée pour découvrir des clubs, participer
                        à des événements et connecter les étudiants de votre université.
                    </p>

                    <div class="row text-center g-3 stats-row">
                        <div class="col-4">
                            <div class="stat-num"><?= $nb_clubs ?></div>
                            <div class="stat-label">Clubs actifs</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-num"><?= $nb_users ?></div>
                            <div class="stat-label">Étudiants</div>
                        </div>
                        <div class="col-4">
                            <div class="stat-num"><?= $nb_events ?></div>
                            <div class="stat-label">Événements</div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- RIGHT -->
            <div class="col-lg-6">
                <div class="card auth-card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 p-lg-5">
                        <ul class="nav nav-pills auth-tabs mb-4" id="authTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#login-tab-pane" type="button">
                                    Connexion
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#register-tab-pane" type="button">
                                    Inscription
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <!-- LOGIN -->
                            <div class="tab-pane fade show active" id="login-tab-pane">
                                <h2 class="form-title mb-2">Bon retour 👋</h2>
                                <p class="form-sub mb-4">
                                    Connectez-vous à votre compte UniClubs
                                </p>

                                <form action="../auth/do_login.php" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Email universitaire</label>
                                        <input type="email" name="email" class="form-control" placeholder="prenom.nom@univ.tn" required />
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label">Mot de passe</label>
                                        <input type="password" name="password" class="form-control" placeholder="••••••••" required />
                                    </div>

                                    <button type="submit" class="btn btn-accent w-100">
                                        Se connecter
                                    </button>
                                </form>
                            </div>

                            <!-- REGISTER -->
                            <div class="tab-pane fade" id="register-tab-pane">
                                <h2 class="form-title mb-2">Créer un compte</h2>
                                <p class="form-sub mb-4">
                                    Rejoignez la communauté UniClubs
                                </p>

                                <form action="../auth/do_register.php" method="POST">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Prénom</label>
                                            <input type="text" name="prenom" class="form-control" placeholder="Ahmed" required />
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Nom</label>
                                            <input type="text" name="nom" class="form-control" placeholder="Ben Ali" required />
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Email universitaire</label>
                                            <input type="email" name="email" class="form-control" placeholder="prenom.nom@univ.tn" required />
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Mot de passe</label>
                                            <input type="password" name="mot_de_passe" class="form-control" placeholder="Minimum 8 caractères" required minlength="8" />
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Rôle</label>
                                            <select name="role" class="form-select" required>
                                                <option value="">Choisir un rôle...</option>
                                                <option value="etudiant">Étudiant</option>
                                                <option value="admin_club">Administrateur de club</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-dark w-100 mt-4">
                                        Créer mon compte
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include '../includes/footer.php'; ?>
