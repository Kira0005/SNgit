<?php
session_start(); // Démarre la session PHP

require_once 'config/database.php'; // Inclut le fichier de connexion à la base de données

$message = ''; // Variable pour stocker les messages de succès ou d'erreur
$messageType = ''; // 'success' ou 'danger'

// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Correction: Remplacement de FILTER_SANITIZE_STRING (déprécié) par htmlspecialchars et trim
    // htmlspecialchars est utilisé pour protéger contre les attaques XSS si l'identifiant est affiché.
    // trim supprime les espaces blancs inutiles au début et à la fin.
    $identifiant = htmlspecialchars(trim($_POST['identifiant'] ?? ''), ENT_QUOTES, 'UTF-8');
    $motDePasse = $_POST['mot_de_passe'];

    // --- Logique d'authentification ---

    // 1. Vérification des rôles spécifiques (Admin, Comptable)
    // Ces identifiants et mots de passe sont codés en dur pour cet exemple.
    // Pour une application réelle, ils devraient être gérés de manière plus sécurisée,
    // par exemple dans une table 'utilisateurs' avec une colonne 'role' et des mots de passe hachés.
    if ($identifiant === 'admin' && $motDePasse === 'admin1') {
        $_SESSION['user_id'] = 'admin_id_unique'; // ID factice unique pour l'admin
        $_SESSION['user_role'] = 'admin';
        // Rediriger vers le tableau de bord admin
        header("Location: admin_dashboard.php");
        exit();
    } elseif ($identifiant === 'Ali' && $motDePasse === '123compte') {
        $_SESSION['user_id'] = 'comptable_id_unique'; // ID factice unique pour le comptable
        $_SESSION['user_role'] = 'comptable';
        // Rediriger vers le tableau de bord comptable
        header("Location: comptable_dashboard.php");
        exit();
    } else {
        // 2. Tentative de connexion pour un utilisateur normal (client)
        // L'identifiant client est basé sur l'email, car c'est le champ unique dans la table 'utilisateurs'.
        try {
            $stmt = $pdo->prepare("SELECT id, mot_de_passe FROM utilisateurs WHERE email = :email");
            $stmt->execute([':email' => $identifiant]);
            $user = $stmt->fetch();

            if ($user && password_verify($motDePasse, $user['mot_de_passe'])) {
                // Mot de passe correct, utilisateur client connecté
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = 'client'; // Définir le rôle comme 'client'

                // Mettre à jour la date de dernière connexion de l'utilisateur
                // Assurez-vous que la colonne 'date_derniere_connexion' existe dans votre table 'utilisateurs'
                $updateStmt = $pdo->prepare("UPDATE utilisateurs SET date_derniere_connexion = NOW() WHERE id = :id");
                $updateStmt->execute([':id' => $user['id']]);

                $message = "Connexion réussie !";
                $messageType = 'success';
                header("Location: index.php"); // Rediriger vers la page d'accueil (ou tableau de bord client)
                exit();
            } else {
                // Identifiant (email) ou mot de passe incorrect pour le client
                $message = "Identifiant ou mot de passe incorrect.";
                $messageType = 'danger';
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la connexion : " . $e->getMessage()); // Log l'erreur pour le débogage
            $message = "Une erreur de base de données est survenue. Veuillez réessayer plus tard.";
            $messageType = 'danger';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChicRush - Connexion</title>
    <!-- Inclusion du CSS de Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Police Inter via Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .form-container {
            background-color: #ffffff;
            padding: 2.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
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
        .rounded-elements {
            border-radius: 0.75rem !important;
        }
    </style>
</head>
<body>

    <div class="form-container rounded-elements">
        <h2 class="text-center mb-4 text-purple">Connexion</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="identifiant" class="form-label">E-mail ou Nom d'utilisateur</label>
                <input type="text" class="form-control rounded-elements" id="identifiant" name="identifiant" required
                       value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input type="password" class="form-control rounded-elements" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 rounded-elements">Se connecter</button>
        </form>
        <p class="text-center mt-3">
            Pas encore de compte ? <a href="signup.php" class="text-purple text-decoration-none">Inscrivez-vous ici</a>
        </p>
    </div>

    <!-- Inclusion des scripts JavaScript de Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" xintegrity="sha384-I7E8VVD/ismYTF4hNIPjVpZVxpLtGPM9NInEN/fLsWfCxuRxN5KMQvY" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" xintegrity="sha384-0pUGZvbkm6XF6gxjEnlcoJoZ/z4pStJjIApsKqFhXZfL+Vb6b+R5S4/6Q4S+p6W4" crossorigin="anonymous"></script>
</body>
</html>
