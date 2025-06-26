<?php
// Fichier : config/database.php

// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost'); // Ou l'adresse de votre serveur MySQL (ex: '127.0.0.1')
define('DB_NAME', 'chicrush'); // Nom de votre base de données
define('DB_USER', 'root');     // Votre nom d'utilisateur MySQL
define('DB_PASS', '');         // Votre mot de passe MySQL

try {
    // Création de l'instance PDO
    // Le DSN (Data Source Name) spécifie le type de base de données et ses paramètres
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    // Options de connexion PDO
    // PDO::ATTR_ERRMODE: Comment PDO gère les erreurs. ERRMODE_EXCEPTION lève des exceptions.
    // PDO::ATTR_DEFAULT_FETCH_MODE: Le mode de récupération par défaut des résultats (ici, tableaux associatifs).
    // PDO::ATTR_EMULATE_PREPARES: Désactive la préparation des requêtes émulée pour une meilleure sécurité.
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Établissement de la connexion
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // echo "Connexion à la base de données établie avec succès !"; // Utile pour le débogage initial

} catch (PDOException $e) {
    // En cas d'erreur de connexion, affiche un message et arrête le script
    // En production, il est préférable de logguer l'erreur et d'afficher un message générique.
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
