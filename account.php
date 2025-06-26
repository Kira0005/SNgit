<?php
// Fichier : account.php
session_start(); // Démarre la session PHP

require_once 'config/database.php'; // Inclut le fichier de connexion à la base de données

// Redirige si l'utilisateur n'est PAS connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role']; // Récupère le rôle de l'utilisateur

$userInfo = null; // Variable pour stocker les informations de l'utilisateur
$orders = [];     // Variable pour stocker l'historique des commandes
$message = '';    // Message de succès/erreur pour la modification de profil
$messageType = ''; // Type de message (success/danger)

// --- Récupération initiale des informations de l'utilisateur ---
if ($userRole === 'client') {
    try {
        $stmt = $pdo->prepare("SELECT nom, prenom, email, adresse, ville, code_postal, pays, telephone FROM utilisateurs WHERE id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $userInfo = $stmt->fetch();

        if (!$userInfo) { // Si l'utilisateur n'est pas trouvé (cas rare, mais géré)
            session_destroy(); // Détruit la session invalide
            header("Location: login.php?message=" . urlencode("Votre session est invalide. Veuillez vous reconnecter.") . "&type=danger");
            exit();
        }

        // Récupérer l'historique des commandes de l'utilisateur
        $stmtOrders = $pdo->prepare("SELECT id, date_commande, montant_total, statut_commande FROM commandes WHERE utilisateur_id = :user_id ORDER BY date_commande DESC");
        $stmtOrders->execute([':user_id' => $userId]);
        $orders = $stmtOrders->fetchAll();

    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération des informations du compte : " . $e->getMessage());
        $message = "Impossible de charger les informations de votre compte pour le moment.";
        $messageType = 'danger';
    }
}

// --- Traitement de la mise à jour des informations de l'utilisateur (si le formulaire est soumis) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if ($userRole === 'client' && $userInfo) { // S'assurer que seul un client peut modifier son profil
        $newNom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $newPrenom = filter_input(INPUT_POST, 'prenom', FILTER_SANITIZE_STRING);
        $newAdresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);
        $newVille = filter_input(INPUT_POST, 'ville', FILTER_SANITIZE_STRING);
        $newCodePostal = filter_input(INPUT_POST, 'code_postal', FILTER_SANITIZE_STRING);
        $newPays = filter_input(INPUT_POST, 'pays', FILTER_SANITIZE_STRING);
        $newTelephone = filter_input(INPUT_POST, 'telephone', FILTER_SANITIZE_STRING);

        // Simple validation pour ne pas avoir de champs vides
        if (empty($newNom) || empty($newPrenom) || empty($newAdresse) || empty($newVille) || empty($newCodePostal) || empty($newPays) || empty($newTelephone)) {
            $message = "Veuillez remplir tous les champs obligatoires.";
            $messageType = 'danger';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE utilisateurs SET
                     nom = :nom, prenom = :prenom, adresse = :adresse,
                     ville = :ville, code_postal = :code_postal, pays = :pays, telephone = :telephone
                     WHERE id = :user_id"
                );
                $stmt->execute([
                    ':nom' => $newNom,
                    ':prenom' => $newPrenom,
                    ':adresse' => $newAdresse,
                    ':ville' => $newVille,
                    ':code_postal' => $newCodePostal,
                    ':pays' => $newPays,
                    ':telephone' => $newTelephone,
                    ':user_id' => $userId
                ]);

                if ($stmt->rowCount() > 0) {
                    $message = "Vos informations ont été mises à jour avec succès !";
                    $messageType = 'success';
                    // Re-récupérer les informations pour qu'elles s'affichent mises à jour immédiatement
                    $stmt = $pdo->prepare("SELECT nom, prenom, email, adresse, ville, code_postal, pays, telephone FROM utilisateurs WHERE id = :user_id");
                    $stmt->execute([':user_id' => $userId]);
                    $userInfo = $stmt->fetch();
                } else {
                    $message = "Aucune modification détectée ou erreur lors de la mise à jour.";
                    $messageType = 'info';
                }
            } catch (PDOException $e) {
                error_log("Erreur PDO lors de la mise à jour du profil : " . $e->getMessage());
                $message = "Une erreur de base de données est survenue lors de la mise à jour.";
                $messageType = 'danger';
            }
        }
    } else {
        $message = "Vous n'êtes pas autorisé à modifier ce profil.";
        $messageType = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChicRush - Mon Compte</title>
    <!-- Inclusion du CSS de Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Police Inter via Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }
        .navbar {
            background-color: #4a0e4e !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .navbar-brand, .nav-link {
            color: #e0f2f7 !important;
        }
        .nav-link:hover {
            color: #a7e6ff !important;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2.5rem 0;
            border-top-left-radius: 1.5rem;
            border-top-right-radius: 1.5rem;
        }
        .rounded-elements {
            border-radius: 0.75rem !important;
        }
        .card {
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #7b2c83;
            border-color: #7b2c83;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #9a4ca3;
            border-color: #9a4ca3;
        }
        .text-purple {
            color: #4a0e4e;
        }
        /* Style pour la section de modification de profil */
        #edit-profile-section {
            display: none; /* Caché par défaut */
            margin-top: 2rem;
            padding: 1.5rem;
            background-color: #fefefe;
            border: 1px solid #eee;
            border-radius: 0.75rem;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <!-- Barre de Navigation (Header) - Réplique celle de index.php pour la cohérence -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-lg rounded-elements mx-3 mt-3">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fa-solid fa-gem me-2"></i> ChicRush
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Nouveautés</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="litiges.php"> Litiges</a>
                    </li>
                </ul>
                <ul class="navbar-nav d-flex ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Panier
                            <span id="cart-item-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <!-- Le nombre d'articles sera mis à jour par JS -->
                            </span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="account.php">Mon Compte</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="signup.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu de la Page Mon Compte -->
    <div class="container my-4">
        <div class="card p-4 rounded-elements">
            <h2 class="mb-4 text-center text-purple">Mon Compte</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($userRole === 'client' && $userInfo): ?>
                <div class="row">
                    <div class="col-md-6">
                        <h4>Mes Informations</h4>
                        <p><strong>Nom:</strong> <?= htmlspecialchars($userInfo['nom']) ?></p>
                        <p><strong>Prénom:</strong> <?= htmlspecialchars($userInfo['prenom']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($userInfo['email']) ?></p>
                        <p><strong>Adresse:</strong> <?= htmlspecialchars($userInfo['adresse']) ?></p>
                        <p><strong>Ville:</strong> <?= htmlspecialchars($userInfo['ville']) ?></p>
                        <p><strong>Code Postal:</strong> <?= htmlspecialchars($userInfo['code_postal']) ?></p>
                        <p><strong>Pays:</strong> <?= htmlspecialchars($userInfo['pays']) ?></p>
                        <p><strong>Téléphone:</strong> <?= htmlspecialchars($userInfo['telephone']) ?></p>
                        <button class="btn btn-primary rounded-elements mt-3" id="editProfileBtn">Modifier mes informations</button>
                    </div>
                    <div class="col-md-6">
                        <h4>Mes Commandes</h4>
                        <?php if (empty($orders)): ?>
                            <p class="text-muted">Vous n'avez pas encore passé de commande.</p>
                            <a href="index.php" class="btn btn-outline-primary rounded-elements">Découvrir nos produits</a>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>N° Commande</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['id']) ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($order['date_commande']))) ?></td>
                                                <td><?= number_format($order['montant_total'], 2, ',', ' ') ?> €</td>
                                                <td><?= htmlspecialchars($order['statut_commande']) ?></td>
                                                <td>
                                                    <a href="order_details.php?id=<?= htmlspecialchars($order['id']) ?>" class="btn btn-sm btn-info rounded-elements" title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section de Modification de Profil (cachée par défaut) -->
                <div id="edit-profile-section" class="mt-4">
                    <h4 class="mb-3">Modifier mes Informations Personnelles</h4>
                    <form action="account.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control rounded-elements" id="nom_edit" name="nom" required
                                   value="<?= htmlspecialchars($userInfo['nom'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control rounded-elements" id="prenom_edit" name="prenom" required
                                   value="<?= htmlspecialchars($userInfo['prenom'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <input type="text" class="form-control rounded-elements" id="adresse_edit" name="adresse" required
                                   value="<?= htmlspecialchars($userInfo['adresse'] ?? '') ?>">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ville" class="form-label">Ville</label>
                                <input type="text" class="form-control rounded-elements" id="ville_edit" name="ville" required
                                       value="<?= htmlspecialchars($userInfo['ville'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="code_postal" class="form-label">Code Postal</label>
                                <input type="text" class="form-control rounded-elements" id="code_postal_edit" name="code_postal" required
                                       value="<?= htmlspecialchars($userInfo['code_postal'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="pays" class="form-label">Pays</label>
                            <input type="text" class="form-control rounded-elements" id="pays_edit" name="pays" required
                                   value="<?= htmlspecialchars($userInfo['pays'] ?? '') ?>">
                        </div>
                        <div class="mb-4">
                            <label for="telephone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control rounded-elements" id="telephone_edit" name="telephone" required
                                   value="<?= htmlspecialchars($userInfo['telephone'] ?? '') ?>">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-secondary rounded-elements me-2" id="cancelEditBtn">Annuler</button>
                            <button type="submit" class="btn btn-primary rounded-elements">Sauvegarder les modifications</button>
                        </div>
                    </form>
                </div>
            <?php elseif ($userRole === 'admin'): ?>
                <div class="text-center py-5">
                    <p class="lead">Bienvenue, Administrateur !</p>
                    <p>Accédez à votre tableau de bord ici : <a href="admin_dashboard.php" class="text-purple">Aller au Tableau de Bord Admin</a></p>
                    <a href="logout.php" class="btn btn-danger mt-3">Déconnexion</a>
                </div>
            <?php elseif ($userRole === 'comptable'): ?>
                <div class="text-center py-5">
                    <p class="lead">Bienvenue, Comptable !</p>
                    <p>Accédez à votre tableau de bord ici : <a href="comptable_dashboard.php" class="text-purple">Aller au Tableau de Bord Comptable</a></p>
                    <a href="logout.php" class="btn btn-danger mt-3">Déconnexion</a>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <p class="lead text-muted">Vous devez être connecté pour accéder à cette page.</p>
                    <a href="login.php" class="btn btn-primary rounded-elements">Se connecter</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pied de Page (Footer) -->
    <footer class="footer mt-5 rounded-elements mx-3">
        <div class="container text-center">
            <p>&copy; 2024 ChicRush. Tous droits réservés.</p>
            <div class="d-flex justify-content-center">
                <a href="#" class="text-white mx-2"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="text-white mx-2"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white mx-2"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <!-- Inclusion des scripts JavaScript de Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" xintegrity="sha384-I7E8VVD/ismYTF4hNIPjVpZVxpLtGPM9NInEN/fLsWfCxuRxN5KMQvY" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" xintegrity="sha384-0pUGZvbkm6XF6gxjEnlcoJoZ/z4pStJjIApsKqFhXZfL+Vb6b+R5S4/6Q4S+p6W4" crossorigin="anonymous"></script>
    <script>
        // Fonction pour récupérer le nombre d'articles du panier depuis le serveur
        async function getCartItemCount() {
            try {
                const response = await fetch('get_cart_count.php');
                const data = await response.json();
                if (data.success) {
                    updateCartCountDisplay(data.total_items);
                } else {
                    console.error('Erreur lors de la récupération du nombre d\'articles du panier:', data.message);
                    updateCartCountDisplay(0);
                }
            } catch (error) {
                console.error('Erreur de réseau lors de la récupération du nombre d\'articles du panier:', error);
                updateCartCountDisplay(0);
            }
        }

        function updateCartCountDisplay(count) {
            const cartCountElement = document.getElementById('cart-item-count');
            if (cartCountElement) {
                cartCountElement.textContent = count;
                if (count === 0) {
                    cartCountElement.classList.add('d-none');
                } else {
                    cartCountElement.classList.remove('d-none');
                }
            }
        }

        // Logique JavaScript pour le bouton de modification de profil
        document.addEventListener('DOMContentLoaded', () => {
            getCartItemCount(); // Met à jour le compteur du panier au chargement de la page

            const editProfileBtn = document.getElementById('editProfileBtn');
            const editProfileSection = document.getElementById('edit-profile-section');
            const cancelEditBtn = document.getElementById('cancelEditBtn');

            if (editProfileBtn && editProfileSection && cancelEditBtn) {
                editProfileBtn.addEventListener('click', () => {
                    editProfileSection.style.display = 'block'; // Affiche la section
                    editProfileBtn.style.display = 'none'; // Cache le bouton d'édition
                    // Défiler vers la section de modification si elle n'est pas visible
                    editProfileSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });

                cancelEditBtn.addEventListener('click', () => {
                    editProfileSection.style.display = 'none'; // Cache la section
                    editProfileBtn.style.display = 'block'; // Affiche le bouton d'édition
                });
            }
        });
    </script>
</body>
</html>
