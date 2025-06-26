<?php
session_start(); // Démarre la session PHP
// Fichier : index.php

require_once 'config/database.php'; // Inclut le fichier de connexion à la base de données

// --- Récupération des catégories depuis la base de données ---
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, nom FROM categories ORDER BY nom ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des catégories : " . $e->getMessage());
}


// --- Récupération des produits depuis la base de données ---
$produits = [];
try {
    $stmt = $pdo->query("SELECT id, nom, description, prix, image_url FROM produits ORDER BY date_ajout DESC LIMIT 6");
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des produits : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChicRush - Accueil</title>
    <!-- Inclusion du CSS de Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Police Inter via Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome pour les icônes (panier, etc.) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Variables CSS pour les thèmes */
        :root {
            --body-bg: #f0f2f5;
            --text-color: #333;
            --navbar-bg: #4a0e4e;
            --navbar-text: #e0f2f7;
            --navbar-hover-text: #a7e6ff;
            --card-bg: #ffffff;
            --footer-bg: #343a40;
            --footer-text: white;
            --primary-btn-bg: #7b2c83;
            --primary-btn-border: #7b2c83;
            --primary-btn-hover-bg: #9a4ca3;
            --primary-btn-hover-border: #9a4ca3;
            --success-color: #28a745;
            --input-border-color: #ced4da;
            --input-bg-color: #fff;
        }

        /* Thème sombre */
        body.dark-theme {
            --body-bg: #212529; /* Couleur de fond sombre */
            --text-color: #f8f9fa; /* Texte clair */
            --navbar-bg: #343a40; /* Navbar plus sombre */
            --navbar-text: #e0f2f7;
            --navbar-hover-text: #a7e6ff;
            --card-bg: #343a40; /* Cartes sombres */
            --footer-bg: #212529; /* Footer encore plus sombre */
            --footer-text: #f8f9fa;
            --primary-btn-bg: #6f42c1; /* Violet plus sombre pour les boutons */
            --primary-btn-border: #6f42c1;
            --primary-btn-hover-bg: #8c5ee6;
            --primary-btn-hover-border: #8c5ee6;
            --success-color: #28a745; /* Conserver le vert pour le prix */
            --input-border-color: #495057;
            --input-bg-color: #2f3337;
        }

        /* Styles généraux basés sur les variables */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .navbar {
            background-color: var(--navbar-bg) !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
            position: sticky; /* Rendre la navbar statique */
            top: 0; /* Positionner en haut de l'écran */
            width: 100%; /* S'assurer qu'elle occupe toute la largeur */
            z-index: 1020; /* Assurer qu'elle est au-dessus des autres éléments lors du défilement */
        }
        .navbar-brand, .nav-link {
            color: var(--navbar-text) !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: var(--navbar-hover-text) !important;
        }
        .product-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out, background-color 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background-color: var(--card-bg);
            color: var(--text-color); /* Pour s'assurer que le texte de la carte change avec le thème */
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }
        .footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            width: 100%;
            padding: 2.5rem 0; /* Padding cohérent */
            border-top-left-radius: 1.5rem; /* Coins arrondis pour le footer */
            border-top-right-radius: 1.5rem;
            transition: background-color 0.3s ease, color 0.3s ease;
            margin-left: 1rem; /* Aligner avec les marges des autres containers */
            margin-right: 1rem;
            box-sizing: border-box; /* Inclure padding et border dans la largeur */
        }
        .rounded-elements {
            border-radius: 0.75rem !important;
        }
        .carousel-item img {
            width: 100%; /* Assurer que l'image du carrousel occupe toute la largeur */
            height: 400px; /* Hauteur fixe pour les images du carrousel */
            object-fit: cover; /* Recouvrir l'espace sans déformer */
            border-radius: 0.75rem;
            filter: brightness(0.8);
        }
        /* Supprimer la bordure du carrousel en retirant les classes Bootstrap */
        #productCarousel {
            border-radius: 0.75rem !important; /* Conserver l'arrondi si désiré, ou 0 pour retirer */
            box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important; /* Conserver l'ombre si désiré, ou none pour retirer */
        }
        .carousel-caption h5, .carousel-caption p {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
        }
        .btn-primary {
            background-color: var(--primary-btn-bg);
            border-color: var(--primary-btn-border);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--primary-btn-hover-bg);
            border-color: var(--primary-btn-hover-border);
        }
        .text-success {
            color: var(--success-color) !important;
        }
        .form-select, .form-control {
            border-radius: 0.5rem;
            border-color: var(--input-border-color);
            background-color: var(--input-bg-color);
            color: var(--text-color);
            transition: border-color 0.3s ease, background-color 0.3s ease, color 0.3s ease;
        }
        /* Style du bouton de thème */
        #theme-switcher {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            padding: 1rem;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--primary-btn-bg);
            border-color: var(--primary-btn-border);
            color: var(--navbar-text);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        #theme-switcher:hover {
            background-color: var(--primary-btn-hover-bg);
            border-color: var(--primary-btn-hover-border);
        }
        /* Style pour les messages temporaires (Toast) */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
        }
    </style>
</head>
<body>

    <!-- Barre de Navigation (Header) -->
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
                        <a class="nav-link active" aria-current="page" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Nouveautés</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="litiges.php"> Litiges</a>
                    </li>
                </ul>
              <!-- Barre de Recherche et Liste Déroulante de Catégories -->
                <div class="d-flex my-2 my-lg-0">
                    <div class="input-group">
                        <input type="text" class="form-control rounded-elements" placeholder="Rechercher..." aria-label="Rechercher">
                        <select class="form-select ms-2 rounded-elements" aria-label="Filtrer par catégorie">
                            <option value="0">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['id']) ?>">
                                    <?= htmlspecialchars($category['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                            <!-- Les catégories seront chargées dynamiquement ici par PHP -->
                        </select>
                    </div>
                </div>
                <ul class="navbar-nav d-flex ms-lg-3">
                    <li class="nav-item">
                        <!-- Lien Panier avec compteur -->
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Panier
                            <span id="cart-item-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                0 <!-- Ce chiffre sera mis à jour dynamiquement par JS -->
                                <span class="visually-hidden">articles dans le panier</span>
                            </span>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): // Si l'utilisateur est connecté ?>
                        <li class="nav-item">
                            <a class="nav-link" href="account.php"> Compte</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Déconnexion</a>
                        </li>
                    <?php else: // Si l'utilisateur n'est PAS connecté ?>
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

    <!-- Carrousel d'Offres -->
    <div class="container my-4">
        <div id="productCarousel" class="carousel slide rounded-elements shadow-lg" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="./asset/images/carrousel/shop.avif" class="d-block w-100" alt="Nouvelle Collection Printemps">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Nouvelle Collection Printemps</h5>
                        <p>Découvrez nos dernières arrivées, des styles frais et éclatants pour la saison.</p>
                        <a href="#" class="btn btn-light rounded-pill">Voir plus</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="./asset/images/carrousel/soldes_robes.jpg" class="d-block w-100" alt="Soldes Robes">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Soldes Flash : Jusqu'à -50% sur les robes !</h5>
                        <p>Ne manquez pas nos offres exclusives sur une sélection de robes élégantes.</p>
                        <a href="#" class="btn btn-light rounded-pill">Profiter des offres</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="./asset/images/carrousel/accessoires.jpg" class="d-block w-100" alt="Accessoires Indispensables">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Accessoires Indispensables</h5>
                        <p>Complétez votre look avec notre collection d'accessoires uniques et tendances.</p>
                        <a href="#" class="btn btn-light rounded-pill">Découvrir</a>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Précédent</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Suivant</span>
            </button>
        </div>
    </div>

    <!-- Contenu Principal (Vitrine des Produits) -->
    <div class="container my-4">
        <h2 class="mb-4 text-center">Nos Produits Phares</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if (empty($produits)): ?>
                <div class="col-12 text-center py-5">
                    <p class="lead text-muted">Aucun produit à afficher pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($produits as $produit): ?>
                    <div class="col">
                        <div class="card product-card h-100 rounded-elements">
                            <!-- Correction du chemin de l'image pour utiliser les barres obliques -->
                            <img src="<?= htmlspecialchars($produit['image_url'] ? './asset/images/produits/'. $produit['image_url'] : 'https://placehold.co/400x300/F0F0F0/000000?text=Image+Produit') ?>" class="card-img-top rounded-top rounded-elements" alt="<?= htmlspecialchars($produit['nom']) ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($produit['nom']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($produit['description'], 0, 100)) ?>...</p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fs-5 fw-bold text-success"><?= number_format($produit['prix'], 2, ',', ' ') ?> fcfa</span>
                                    <!-- Bouton mis à jour pour la logique AJAX -->
                                    <button class="btn btn-primary rounded-pill add-to-cart-btn" data-product-id="<?= htmlspecialchars($produit['id']) ?>">
                                        <i class="fas fa-cart-plus me-1"></i> Ajouter au panier
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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

    <!-- Bouton de changement de thème -->
    <button id="theme-switcher" class="btn" aria-label="Changer le thème">
        <i class="fas fa-moon" id="theme-icon"></i>
    </button>

    <!-- Conteneur pour les messages Toast Bootstrap -->
    <div class="toast-container">
        <div id="cartToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toast-message">
                    Article ajouté au panier !
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>


    <!-- Inclusion des scripts JavaScript de Bootstrap 5 (Popper.js et Bootstrap.js) -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" xintegrity="sha384-I7E8VVD/ismYTF4hNIPjVpZVxpLtGPM9NInEN/fLsWfCxuRxN5KMQvY" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" xintegrity="sha384-0pUGZvbkm6XF6gxjEnlcoJoZ/z4pStJjIApsKqFhXZfL+Vb6b+R5S4/6Q4S+p6W4" crossorigin="anonymous"></script>

    <script>
        // Logique JavaScript pour le panier
        const cartCountElement = document.getElementById('cart-item-count');
        const cartToast = new bootstrap.Toast(document.getElementById('cartToast'));
        const toastMessageElement = document.getElementById('toast-message');

        // Fonction pour récupérer le nombre d'articles du panier depuis le serveur
        async function getCartItemCount() {
            try {
                const response = await fetch('get_cart_count.php'); // Un nouveau script pour juste le count
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
            cartCountElement.textContent = count;
            if (count === 0) {
                cartCountElement.classList.add('d-none');
            } else {
                cartCountElement.classList.remove('d-none');
            }
        }

        // Fonction pour ajouter au panier via AJAX
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', async (event) => {
                const productId = event.currentTarget.dataset.productId;
                event.currentTarget.disabled = true; // Désactive le bouton pendant l'ajout
                event.currentTarget.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ajout...';

                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', 1); // Pour l'instant, ajoutons 1 article

                try {
                    const response = await fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        updateCartCountDisplay(data.total_items);
                        toastMessageElement.textContent = data.message;
                        cartToast.show();
                    } else {
                        toastMessageElement.textContent = data.message;
                        cartToast.show();
                        if (data.action === 'redirect_to_login') {
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 2000); // Redirige après 2 secondes pour lire le message
                        }
                    }
                } catch (error) {
                    console.error('Erreur lors de l\'ajout au panier:', error);
                    toastMessageElement.textContent = 'Erreur: Impossible d\'ajouter l\'article.';
                    cartToast.show();
                } finally {
                    event.currentTarget.disabled = false; // Réactive le bouton
                    event.currentTarget.innerHTML = '<i class="fas fa-cart-plus me-1"></i> Ajouter au panier';
                }
            });
        });

        // Logique JavaScript pour le changement de thème (inchangée)
        const themeSwitcher = document.getElementById('theme-switcher');
        const themeIcon = document.getElementById('theme-icon');

        function setInitialTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-theme');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                document.body.classList.remove('dark-theme');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }

        // Initialiser l'affichage du compteur et le thème au chargement de la page
        document.addEventListener('DOMContentLoaded', () => {
            getCartItemCount(); // Récupère le vrai nombre d'articles du panier au chargement
            setInitialTheme();
        });

        themeSwitcher.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            if (document.body.classList.contains('dark-theme')) {
                localStorage.setItem('theme', 'dark');
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                localStorage.setItem('theme', 'light');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        });
    </script>
</body>
</html>
