<?php
// Fichier : litiges.php
session_start(); // Démarre la session PHP

require_once 'config/database.php'; // Inclut le fichier de connexion à la base de données

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';
$litiges = []; // Tableau pour stocker l'historique des litiges

// Dossier où les pièces jointes seront uploadées
define('UPLOAD_DIR', 'uploads/litiges/');

// Assurez-vous que le dossier d'upload existe et est inscriptible
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true); // Crée le dossier avec les permissions 0755
}

// --- Traitement de la soumission du formulaire de litige ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_litige'])) {
    $commandeId = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);
    $produitRef = filter_input(INPUT_POST, 'produit_ref', FILTER_SANITIZE_STRING); // Peut être le nom ou une référence
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $sujet = 'Litige Commande #' . ($commandeId ?: 'N/A') . ($produitRef ? ' - Produit: ' . $produitRef : '');

    // Validation basique
    if (!$commandeId || empty($description)) {
        $message = "Veuillez renseigner un numéro de commande valide et une description.";
        $messageType = 'danger';
    } else {
        try {
            $pdo->beginTransaction(); // Démarre une transaction

            // 1. Vérifier si la commande existe et appartient à l'utilisateur
            $stmt = $pdo->prepare("SELECT id FROM commandes WHERE id = :commande_id AND utilisateur_id = :user_id");
            $stmt->execute([':commande_id' => $commandeId, ':user_id' => $userId]);
            $commandeExists = $stmt->fetch();

            if (!$commandeExists) {
                $message = "Le numéro de commande ne correspond pas à vos commandes ou n'existe pas.";
                $messageType = 'danger';
                $pdo->rollBack();
            } else {
                // 2. Insérer le litige dans la table `litiges`
                // Nous allons chercher l'ID du produit si `produit_ref` est fourni et correspond à un produit
                $produitId = null;
                if (!empty($produitRef)) {
                    $stmtProd = $pdo->prepare("SELECT id FROM produits WHERE nom = :produit_nom OR id = :produit_id_num LIMIT 1");
                    // Tente de convertir $produitRef en int pour chercher par ID, sinon cherche par nom
                    $stmtProd->execute([':produit_nom' => $produitRef, ':produit_id_num' => is_numeric($produitRef) ? (int)$produitRef : 0]);
                    $product = $stmtProd->fetch();
                    if ($product) {
                        $produitId = $product['id'];
                    }
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO litiges (utilisateur_id, commande_id, produit_id, sujet, description, statut, date_creation)
                     VALUES (:utilisateur_id, :commande_id, :produit_id, :sujet, :description, 'ouvert', NOW())"
                );
                $stmt->execute([
                    ':utilisateur_id' => $userId,
                    ':commande_id' => $commandeId,
                    ':produit_id' => $produitId,
                    ':sujet' => $sujet,
                    ':description' => $description
                ]);
                $litigeId = $pdo->lastInsertId();

                // 3. Gérer les pièces jointes
                if (!empty($_FILES['pieces_jointes']['name'][0])) {
                    $totalFiles = count($_FILES['pieces_jointes']['name']);
                    for ($i = 0; $i < $totalFiles; $i++) {
                        $fileName = $_FILES['pieces_jointes']['name'][$i];
                        $fileTmpName = $_FILES['pieces_jointes']['tmp_name'][$i];
                        $fileSize = $_FILES['pieces_jointes']['size'][$i];
                        $fileError = $_FILES['pieces_jointes']['error'][$i];
                        $fileType = $_FILES['pieces_jointes']['type'][$i];

                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf']; // Extensions autorisées
                        $maxFileSize = 5 * 1024 * 1024; // 5MB

                        if ($fileError === 0 && in_array($fileExt, $allowedExtensions) && $fileSize < $maxFileSize) {
                            $newFileName = uniqid('', true) . '.' . $fileExt;
                            $fileDestination = UPLOAD_DIR . $newFileName;

                            if (move_uploaded_file($fileTmpName, $fileDestination)) {
                                $stmtFile = $pdo->prepare(
                                    "INSERT INTO litiges_pieces_jointes (litige_id, chemin_fichier, type_fichier, taille_fichier, date_ajout)
                                     VALUES (:litige_id, :chemin_fichier, :type_fichier, :taille_fichier, NOW())"
                                );
                                $stmtFile->execute([
                                    ':litige_id' => $litigeId,
                                    ':chemin_fichier' => $fileDestination,
                                    ':type_fichier' => $fileType,
                                    ':taille_fichier' => $fileSize
                                ]);
                            } else {
                                error_log("Erreur lors du déplacement du fichier uploadé : " . $fileName);
                                $message = "Erreur lors de l'upload d'un fichier. Veuillez réessayer.";
                                $messageType = 'warning';
                                break; // Arrête le traitement des fichiers si une erreur survient
                            }
                        } else {
                            $message = "Fichier invalide ou trop volumineux : " . htmlspecialchars($fileName);
                            $messageType = 'warning';
                            break;
                        }
                    }
                }
                
                if ($messageType !== 'danger') { // Si aucune erreur critique
                    $message = "Votre litige a été soumis avec succès ! Nous vous répondrons dans les plus brefs délais.";
                    $messageType = 'success';
                    $pdo->commit();
                    // Rediriger pour éviter la soumission multiple et afficher le message GET
                    header("Location: litiges.php?message=" . urlencode($message) . "&type=" . $messageType);
                    exit();
                } else {
                    $pdo->rollBack();
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur PDO lors de la soumission du litige : " . $e->getMessage());
            $message = "Une erreur est survenue lors de la soumission de votre litige. Veuillez réessayer plus tard.";
            $messageType = 'danger';
        }
    }
}

// --- Récupération de l'historique des litiges de l'utilisateur ---
try {
    $stmt = $pdo->prepare("
        SELECT
            l.id,
            l.commande_id,
            p.nom AS produit_nom,
            l.sujet,
            l.description,
            l.statut,
            l.date_creation,
            GROUP_CONCAT(lj.chemin_fichier) AS pieces_jointes_paths
        FROM
            litiges l
        LEFT JOIN
            produits p ON l.produit_id = p.id
        LEFT JOIN
            litiges_pieces_jointes lj ON l.id = lj.litige_id
        WHERE
            l.utilisateur_id = :user_id
        GROUP BY
            l.id
        ORDER BY
            l.date_creation DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $litiges = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération des litiges : " . $e->getMessage());
    $message = "Impossible de charger votre historique de litiges pour le moment.";
    $messageType = 'danger';
}

// Gérer les messages passés via l'URL (ex: après soumission réussie)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = filter_input(INPUT_GET, 'message', FILTER_SANITIZE_STRING);
    $messageType = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChicRush - Gestion des Litiges</title>
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
        .status-badge {
            padding: 0.3em 0.6em;
            border-radius: 0.5em;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-ouvert { background-color: #ffc107; color: #333; } /* jaune */
        .status-en_cours { background-color: #17a2b8; color: white; } /* info */
        .status-resolu { background-color: #28a745; color: white; } /* vert */
        .status-ferme { background-color: #6c757d; color: white; } /* gris */
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
                        <a class="nav-link active" aria-current="page" href="litiges.php">Gestion des Litiges</a>
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
                            <a class="nav-link" href="account.php">Mon Compte</a>
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

    <!-- Contenu de la Page Litiges -->
    <div class="container my-4">
        <div class="card p-4 rounded-elements">
            <h2 class="mb-4 text-center text-purple">Gestion des Litiges</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Formulaire de soumission de litige -->
            <div class="mb-5">
                <h4 class="mb-3">Soumettre un Nouveau Litige</h4>
                <form action="litiges.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="commande_id" class="form-label">Numéro de Commande <span class="text-danger">*</span></label>
                        <input type="number" class="form-control rounded-elements" id="commande_id" name="commande_id" required min="1">
                        <div class="form-text">Indiquez le numéro de commande concerné par le litige.</div>
                    </div>
                    <div class="mb-3">
                        <label for="produit_ref" class="form-label">Nom ou Référence de l'Article (Optionnel)</label>
                        <input type="text" class="form-control rounded-elements" id="produit_ref" name="produit_ref">
                        <div class="form-text">Ex: "Robe Rouge", "PROD123". Utile si le litige ne concerne qu'un article spécifique.</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description Détaillée du Problème <span class="text-danger">*</span></label>
                        <textarea class="form-control rounded-elements" id="description" name="description" rows="5" required></textarea>
                        <div class="form-text">Expliquez clairement la nature du problème (ex: article endommagé, mauvaise taille, article manquant).</div>
                    </div>
                    <div class="mb-4">
                        <label for="pieces_jointes" class="form-label">Pièces Jointes (Photos, PDF - Max 5MB par fichier)</label>
                        <input type="file" class="form-control rounded-elements" id="pieces_jointes" name="pieces_jointes[]" multiple accept="image/*, application/pdf">
                        <div class="form-text">Joignez des photos ou des documents pertinents (max. 5 fichiers).</div>
                    </div>
                    <button type="submit" name="submit_litige" class="btn btn-primary rounded-pill">
                        <i class="fas fa-paper-plane me-2"></i> Soumettre le Litige
                    </button>
                </form>
            </div>

            <hr class="my-5">

            <!-- Historique des litiges -->
            <h4 class="mb-3">Mon Historique de Litiges</h4>
            <?php if (empty($litiges)): ?>
                <div class="text-center py-5">
                    <p class="lead text-muted">Vous n'avez soumis aucun litige pour le moment.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID Litige</th>
                                <th>Commande #</th>
                                <th>Article</th>
                                <th>Sujet</th>
                                <th>Statut</th>
                                <th>Date Soumission</th>
                                <th>Pièces Jointes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($litiges as $litige): ?>
                                <tr>
                                    <td><?= htmlspecialchars($litige['id']) ?></td>
                                    <td><?= htmlspecialchars($litige['commande_id']) ?></td>
                                    <td><?= htmlspecialchars($litige['produit_nom'] ?: 'Toute la commande') ?></td>
                                    <td><?= htmlspecialchars($litige['sujet']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= htmlspecialchars($litige['statut']) ?>">
                                            <?= htmlspecialchars(str_replace('_', ' ', ucfirst($litige['statut']))) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($litige['date_creation']))) ?></td>
                                    <td>
                                        <?php if (!empty($litige['pieces_jointes_paths'])): ?>
                                            <?php $attachments = explode(',', $litige['pieces_jointes_paths']); ?>
                                            <?php foreach ($attachments as $path): ?>
                                                <?php $fileName = basename($path); ?>
                                                <a href="<?= htmlspecialchars($path) ?>" target="_blank" class="badge bg-info text-decoration-none my-1 me-1">
                                                    <i class="fas fa-paperclip"></i> <?= htmlspecialchars($fileName) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            Aucune
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info rounded-elements" title="Voir les détails">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <!-- Plus d'actions (ex: Répondre) peuvent être ajoutées ici -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        // (Copie de la fonction de index.php/cart.php pour la cohérence de la navbar)
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

        document.addEventListener('DOMContentLoaded', () => {
            getCartItemCount(); // Met à jour le compteur du panier au chargement de la page
        });
    </script>
</body>
</html>
