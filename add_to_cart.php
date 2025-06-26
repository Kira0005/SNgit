<?php
// Fichier : add_to_cart.php
session_start(); // Démarre la session PHP

// Définit l'en-tête pour indiquer que la réponse est du JSON
header('Content-Type: application/json');

// Inclut le fichier de connexion à la base de données
require_once 'config/database.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour ajouter des articles au panier.', 'action' => 'redirect_to_login']);
    exit();
}

$userId = $_SESSION['user_id'];
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

// Assure que la quantité est au moins 1
if ($quantity <= 0) {
    $quantity = 1;
}

// Validation des données reçues
if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID produit invalide.']);
    exit();
}

try {
    $pdo->beginTransaction(); // Démarre une transaction pour assurer l'intégrité des données

    // 1. Vérifier si un panier existe pour cet utilisateur, sinon le créer
    $stmt = $pdo->prepare("SELECT id FROM paniers WHERE utilisateur_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $panier = $stmt->fetch();

    $panierId = null;
    if ($panier) {
        $panierId = $panier['id'];
    } else {
        // Créer un nouveau panier pour l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO paniers (utilisateur_id, date_creation, date_derniere_maj) VALUES (:user_id, NOW(), NOW())");
        $stmt->execute([':user_id' => $userId]);
        $panierId = $pdo->lastInsertId(); // Récupère l'ID du panier nouvellement créé
    }

    // 2. Vérifier si l'article existe déjà dans ce panier
    $stmt = $pdo->prepare("SELECT id, quantite FROM articles_panier WHERE panier_id = :panier_id AND produit_id = :product_id");
    $stmt->execute([':panier_id' => $panierId, ':product_id' => $productId]);
    $articlePanier = $stmt->fetch();

    if ($articlePanier) {
        // L'article existe déjà, met à jour la quantité
        $nouvelleQuantite = $articlePanier['quantite'] + $quantity;
        $stmt = $pdo->prepare("UPDATE articles_panier SET quantite = :quantite WHERE id = :id");
        $stmt->execute([':quantite' => $nouvelleQuantite, ':id' => $articlePanier['id']]);
    } else {
        // L'article n'existe pas, l'ajoute au panier
        $stmt = $pdo->prepare("INSERT INTO articles_panier (panier_id, produit_id, quantite, date_ajout) VALUES (:panier_id, :product_id, :quantite, NOW())");
        $stmt->execute([':panier_id' => $panierId, ':product_id' => $productId, ':quantite' => $quantity]);
    }

    // 3. Calculer le nombre total d'articles différents dans le panier de l'utilisateur
    $stmt = $pdo->prepare("SELECT COUNT(id) AS total_items FROM articles_panier WHERE panier_id = :panier_id");
    $stmt->execute([':panier_id' => $panierId]);
    $totalItems = $stmt->fetchColumn();

    $pdo->commit(); // Valide la transaction

    echo json_encode(['success' => true, 'message' => 'Article ajouté au panier !', 'total_items' => $totalItems]);

} catch (PDOException $e) {
    $pdo->rollBack(); // Annule la transaction en cas d'erreur
    error_log("Erreur PDO lors de l'ajout au panier : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'ajout au panier.']);
}
