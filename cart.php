<?php
// Fichier : cart.php
session_start(); // Démarre la session PHP

require_once 'config/database.php'; // Inclut le fichier de connexion à la base de données

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$cartItems = [];
$cartTotal = 0;
$message = '';
$messageType = '';

// Gérer les messages passés via l'URL (par exemple, depuis process_order.php)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = filter_input(INPUT_GET, 'message', FILTER_SANITIZE_STRING);
    $messageType = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
}


// --- Traitement des actions du panier (Supprimer, Mettre à jour la quantité) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        try {
            $pdo->beginTransaction(); // Démarre une transaction

            // Récupérer l'ID du panier de l'utilisateur
            $stmt = $pdo->prepare("SELECT id FROM paniers WHERE utilisateur_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $panier = $stmt->fetch();

            if (!$panier) {
                $message = "Votre panier est vide.";
                $messageType = 'info';
                $pdo->rollBack();
                // Rediriger vers le panier avec un message d'erreur
                header("Location: cart.php?message=" . urlencode($message) . "&type=" . $messageType);
                exit();
            } else {
                $panierId = $panier['id'];

                if ($action === 'remove_item' && isset($_POST['product_id'])) {
                    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
                    if ($productId) {
                        $stmt = $pdo->prepare("DELETE FROM articles_panier WHERE panier_id = :panier_id AND produit_id = :product_id");
                        $stmt->execute([':panier_id' => $panierId, ':product_id' => $productId]);
                        if ($stmt->rowCount() > 0) {
                            $message = "Article retiré du panier.";
                            $messageType = 'success';
                        } else {
                            $message = "L'article n'a pas pu être retiré du panier.";
                            $messageType = 'danger';
                        }
                    } else {
                        $message = "ID produit invalide pour la suppression.";
                        $messageType = 'danger';
                    }
                } elseif ($action === 'update_quantity' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
                    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
                    $newQuantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

                    if ($productId && $newQuantity !== false && $newQuantity >= 0) {
                        if ($newQuantity == 0) {
                            // Si la quantité est 0, supprimer l'article du panier
                            $stmt = $pdo->prepare("DELETE FROM articles_panier WHERE panier_id = :panier_id AND produit_id = :product_id");
                            $stmt->execute([':panier_id' => $panierId, ':product_id' => $productId]);
                            $message = "Quantité mise à jour (article retiré si quantité zéro).";
                            $messageType = 'success';
                        } else {
                            // Mettre à jour la quantité
                            $stmt = $pdo->prepare("UPDATE articles_panier SET quantite = :quantite WHERE panier_id = :panier_id AND produit_id = :product_id");
                            $stmt->execute([':quantite' => $newQuantity, ':panier_id' => $panierId, ':product_id' => $productId]);
                            if ($stmt->rowCount() > 0) {
                                $message = "Quantité mise à jour.";
                                $messageType = 'success';
                            } else {
                                $message = "La quantité n'a pas pu être mise à jour.";
                                $messageType = 'info'; // Info car aucune modification si la quantité est la même
                            }
                        }
                    } else {
                        $message = "Données de quantité ou ID produit invalides.";
                        $messageType = 'danger';
                    }
                }
                // Si toutes les opérations sur les articles du panier sont terminées,
                // vérifiez s'il reste des articles dans le panier. Si non, supprimez le panier parent.
                $stmt = $pdo->prepare("SELECT COUNT(id) FROM articles_panier WHERE panier_id = :panier_id");
                $stmt->execute([':panier_id' => $panierId]);
                $remainingItems = $stmt->fetchColumn();

                if ($remainingItems == 0) {
                    $stmt = $pdo->prepare("DELETE FROM paniers WHERE id = :panier_id");
                    $stmt->execute([':panier_id' => $panierId]);
                }
            }
            $pdo->commit(); // Valide la transaction
            // Recharger la page pour afficher les changements
            header("Location: cart.php?message=" . urlencode($message) . "&type=" . $messageType);
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack(); // Annule la transaction en cas d'erreur
            error_log("Erreur PDO lors de l'action sur le panier : " . $e->getMessage());
            $message = "Une erreur est survenue lors du traitement de votre demande.";
            $messageType = 'danger';
            header("Location: cart.php?message=" . urlencode($message) . "&type=" . $messageType);
            exit();
        }
    }
}

// --- Récupération du contenu du panier de l'utilisateur ---
try {
    $stmt = $pdo->prepare("
        SELECT
            ap.id AS article_panier_id,
            ap.quantite,
            p.id AS produit_id,
            p.nom AS produit_nom,
            p.description AS produit_description,
            p.prix AS produit_prix,
            p.image_url AS produit_image
        FROM
            paniers pa
        JOIN
            articles_panier ap ON pa.id = ap.panier_id
        JOIN
            produits p ON ap.produit_id = p.id
        WHERE
            pa.utilisateur_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $cartItems = $stmt->fetchAll();

    // Calcul du total du panier
    foreach ($cartItems as $item) {
        $cartTotal += $item['quantite'] * $item['produit_prix'];
    }

} catch (PDOException $e) {
    error_log("Erreur PDO lors de la récupération du panier : " . $e->getMessage());
    $message = "Impossible de charger votre panier pour le moment.";
    $messageType = 'danger';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChicRush - Mon Panier</title>
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
            background-color: #4a0e4e !important; /* Violet foncé captivant */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .navbar-brand, .nav-link {
            color: #e0f2f7 !important; /* Texte clair pour contraste */
        }
        .nav-link:hover {
            color: #a7e6ff !important; /* Effet hover lumineux */
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
        .cart-table img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
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
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .total-summary {
            background-color: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                        <a class="nav-link" href="litiges.php">Litiges</a>
                    </li>
                </ul>
                <ul class="navbar-nav d-flex ms-lg-3">
                    <li class="nav-item">
                        <a class="nav-link active position-relative" aria-current="page" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Panier
                            <span id="cart-item-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <!-- Le nombre d'articles sera mis à jour par JS -->
                            </span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="account.php">Compte</a>
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

    <!-- Contenu du Panier -->
    <div class="container my-4">
        <div class="card p-4 rounded-elements">
            <h2 class="mb-4 text-center">Mon Panier</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="text-center py-5">
                    <p class="lead text-muted">Votre panier est vide.</p>
                    <a href="index.php" class="btn btn-primary rounded-pill">Continuer vos achats</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle cart-table">
                        <thead>
                            <tr class="table-light">
                                <th scope="col">Produit</th>
                                <th scope="col">Description</th>
                                <th scope="col">Prix Unitaire</th>
                                <th scope="col">Quantité</th>
                                <th scope="col">Total</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= htmlspecialchars($item['produit_image'] ? './asset/images/produits/' . $item['produit_image'] : 'https://placehold.co/80x80/F0F0F0/000000?text=Produit') ?>" class="me-3" alt="<?= htmlspecialchars($item['produit_nom']) ?>">
                                            <h6 class="mb-0"><?= htmlspecialchars($item['produit_nom']) ?></h6>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars(substr($item['produit_description'], 0, 50)) ?>...</td>
                                    <td><?= number_format($item['produit_prix'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <form action="cart.php" method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['produit_id']) ?>">
                                            <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantite']) ?>" min="0" class="form-control form-control-sm w-auto rounded-elements" style="max-width: 80px;">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm ms-2 rounded-elements" title="Mettre à jour la quantité">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?= number_format($item['quantite'] * $item['produit_prix'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <form action="cart.php" method="POST">
                                            <input type="hidden" name="action" value="remove_item">
                                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['produit_id']) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm rounded-elements" title="Supprimer l'article">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end mt-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="total-summary">
                            <h5 class="mb-3">Récapitulatif du panier</h5>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total des articles:</span>
                                <span class="fw-bold"><?= number_format($cartTotal, 2, ',', ' ') ?> €</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fs-5 fw-bold text-primary">
                                <span>Total à payer:</span>
                                <span><?= number_format($cartTotal, 2, ',', ' ') ?> €</span>
                            </div>
                            <form action="process_order.php" method="POST">
                                <button type="submit" class="btn btn-primary w-100 mt-4 rounded-pill btn-lg">
                                    <i class="fas fa-cash-register me-2"></i> Valider le panier
                                </button>
                            </form>
                        </div>
                    </div>
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

        document.addEventListener('DOMContentLoaded', () => {
            getCartItemCount(); // Met à jour le compteur du panier au chargement de la page
        });
    </script>
</body>
</html>
