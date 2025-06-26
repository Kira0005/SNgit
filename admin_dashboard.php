<?php
session_start();
// Vérifie si l'utilisateur est connecté et a le rôle 'admin'
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChicRush - Tableau de Bord Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f2f5; color: #333; }
        .container { background-color: #ffffff; padding: 3rem; border-radius: 1rem; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .btn-danger { background-color: #dc3545; border-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; border-color: #bd2130; }
        .text-primary { color: #4a0e4e !important; }
    </style>
</head>
<body class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="container text-center">
        <h1 class="mb-4 text-primary">Bienvenue, Administrateur !</h1>
        <p class="lead">Ceci est votre tableau de bord. Vous pouvez gérer les produits, les commandes, les utilisateurs, etc.</p>
        <p class="text-muted">Des fonctionnalités complètes seront ajoutées ici.</p>
        <a href="logout.php" class="btn btn-danger mt-4">Déconnexion</a>
    </div>
</body>
</html>
