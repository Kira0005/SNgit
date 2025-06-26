<?php
// Fichier : confirmation.php
session_start();

$message = filter_input(INPUT_GET, 'message', FILTER_SANITIZE_STRING);
$messageType = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
$commandeId = filter_input(INPUT_GET, 'commande_id', FILTER_VALIDATE_INT);

// Afficher un message par défaut si aucun n'est passé
if (empty($message)) {
    $message = "Votre commande a été traitée. Merci !";
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChicRush - Confirmation de Commande</title>
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
            color: #333;
        }
        .confirmation-container {
            background-color: #ffffff;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
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

    <div class="confirmation-container rounded-elements">
        <h2 class="mb-4 text-purple">Commande Confirmée !</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?> fade show" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($commandeId): ?>
            <p class="lead">Votre commande N° <strong><?= htmlspecialchars($commandeId) ?></strong> a été enregistrée avec succès.</p>
        <?php endif; ?>
        
        <p>Un e-mail de confirmation avec votre facture a été envoyé à votre adresse e-mail.</p>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary rounded-pill btn-lg me-2">Continuer vos achats</a>
            <a href="account.php" class="btn btn-outline-secondary rounded-pill btn-lg">Voir mes commandes</a>
        </div>
    </div>

    <!-- Inclusion des scripts JavaScript de Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" xintegrity="sha384-I7E8VVD/ismYTF4hNIPjVpZVxpLtGPM9NInEN/fLsWfCxuRxN5KMQvY" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" xintegrity="sha384-0pUGZvbkm6XF6gxjEnlcoJoZ/z4pStJjIApsKqFhXZfL+Vb6b+R5S4/6Q4S+p6W4" crossorigin="anonymous"></script>
</body>
</html>
