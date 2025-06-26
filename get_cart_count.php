<?php
// Fichier : get_cart_count.php
session_start(); // Démarre la session PHP

header('Content-Type: application/json'); // Indique que la réponse est du JSON

require_once 'config/database.php'; // Inclut le fichier de connexion

$totalItems = 0; // Initialise le compteur à 0

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    try {
        // Récupérer l'ID du panier de l'utilisateur
        $stmt = $pdo->prepare("SELECT id FROM paniers WHERE utilisateur_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $panier = $stmt->fetch();

        if ($panier) {
            $panierId = $panier['id'];
            // Compter le nombre d'articles distincts dans le panier (nombre de lignes)
            $stmt = $pdo->prepare("SELECT COUNT(id) AS total_items FROM articles_panier WHERE panier_id = :panier_id");
            $stmt->execute([':panier_id' => $panierId]);
            $totalItems = $stmt->fetchColumn();
        }
        echo json_encode(['success' => true, 'total_items' => $totalItems]);
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération du nombre d'articles du panier : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
    }
} else {
    // Si pas connecté, le total est 0
    echo json_encode(['success' => true, 'total_items' => 0]);
}
