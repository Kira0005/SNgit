<?php
session_start(); // Démarre la session PHP, essentielle pour la gestion des utilisateurs

require_once 'config/database.php'; // Inclut le fichier de connexion à la base de données

$message = ''; // Variable pour stocker les messages de succès ou d'erreur
$messageType = ''; // 'success' ou 'danger' pour le style Bootstrap

// Vérifie si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données du formulaire
    // Utilisation de trim() pour supprimer les espaces blancs et accès direct à $_POST,
    // en se fiant aux requêtes préparées de PDO pour la prévention des injections SQL,
    // et à htmlspecialchars pour l'affichage en sortie.
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL); // Garde FILTER_SANITIZE_EMAIL pour la validation de l'email
    $motDePasse = $_POST['mot_de_passe'] ?? ''; // Le mot de passe ne doit pas être modifié avant le hachage
    $confirmMotDePasse = $_POST['confirm_mot_de_passe'] ?? '';
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $codePostal = trim($_POST['code_postal'] ?? '');
    $pays = trim($_POST['pays'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');

    // --- Validation côté serveur ---
    if (empty($nom) || empty($prenom) || empty($email) || empty($motDePasse) || empty($confirmMotDePasse) ||
        empty($adresse) || empty($ville) || empty($codePostal) || empty($pays) || empty($telephone)) {
        $message = "Veuillez remplir tous les champs.";
        $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "L'adresse e-mail n'est pas valide.";
        $messageType = 'danger';
    } elseif ($motDePasse !== $confirmMotDePasse) {
        $message = "Les mots de passe ne correspondent pas.";
        $messageType = 'danger';
    } elseif (strlen($motDePasse) < 8) {
        $message = "Le mot de passe doit contenir au moins 8 caractères.";
        $messageType = 'danger';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->rowCount() > 0) {
                $message = "Cette adresse e-mail est déjà utilisée.";
                $messageType = 'danger';
            } else {
                // Hacher le mot de passe avant de l'insérer dans la base de données
                $motDePasseHache = password_hash($motDePasse, PASSWORD_DEFAULT);

                // Insertion du nouvel utilisateur dans la base de données
                // date_inscription sera définie par NOW() en SQL
                // date_derniere_connexion sera définie lors de la première connexion (voir login.php)
                $stmt = $pdo->prepare(
                    "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, adresse, ville, code_postal, pays, telephone, date_inscription)
                     VALUES (:nom, :prenom, :email, :mot_de_passe, :adresse, :ville, :code_postal, :pays, :telephone, NOW())"
                );

                $stmt->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':mot_de_passe' => $motDePasseHache,
                    ':adresse' => $adresse,
                    ':ville' => $ville,
                    ':code_postal' => $codePostal,
                    ':pays' => $pays,
                    ':telephone' => $telephone
                ]);

                // Si l'insertion est réussie
                if ($stmt->rowCount() > 0) {
                    $message = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
                    $messageType = 'success';
                    // Rediriger vers la page de connexion après un court délai
                    header("Refresh:3; url=login.php");
                    exit(); // Arrête l'exécution du script
                } else {
                    $message = "Une erreur est survenue lors de la création de votre compte.";
                    $messageType = 'danger';
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de l'inscription : " . $e->getMessage());
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
    <title>ChicRush - Inscription</title>
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
            max-width: 500px;
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
        <h2 class="text-center mb-4 text-purple">Inscription</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" class="form-control rounded-elements" id="nom" name="nom" required
                        value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" class="form-control rounded-elements" id="prenom" name="prenom" required
                        value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Adresse E-mail</label>
                <input type="email" class="form-control rounded-elements" id="email" name="email" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input type="password" class="form-control rounded-elements" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div class="mb-4">
                <label for="confirm_mot_de_passe" class="form-label">Confirmer le mot de passe</label>
                <input type="password" class="form-control rounded-elements" id="confirm_mot_de_passe" name="confirm_mot_de_passe" required>
            </div>

            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse</label>
                <input type="text" class="form-control rounded-elements" id="adresse" name="adresse" required
                        value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ville" class="form-label">Ville</label>
                    <input type="text" class="form-control rounded-elements" id="ville" name="ville" required
                            value="<?= htmlspecialchars($_POST['ville'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="code_postal" class="form-label">Code Postal</label>
                    <input type="text" class="form-control rounded-elements" id="code_postal" name="code_postal" required
                            value="<?= htmlspecialchars($_POST['code_postal'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-3">
                <label for="pays" class="form-label">Pays</label>
                <input type="text" class="form-control rounded-elements" id="pays" name="pays" required
                        value="<?= htmlspecialchars($_POST['pays'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label for="telephone" class="form-label">Téléphone</label>
                <input type="tel" class="form-control rounded-elements" id="telephone" name="telephone" required
                        value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary w-100 rounded-elements">S'inscrire</button>
        </form>
        <p class="text-center mt-3">
            Vous avez déjà un compte ? <a href="login.php" class="text-purple text-decoration-none">Connectez-vous ici</a>
        </p>
    </div>

    <!-- Inclusion des scripts JavaScript de Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" xintegrity="sha384-I7E8VVD/ismYTF4hNIPjVpZVxpLtGPM9NInEN/fLsWfCxuRxN5KMQvY" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" xintegrity="sha384-0pUGZvbkm6XF6gxjEnlcoJoZ/z4pStJjIApsKqFhXZfL+Vb6b+R5S4/6Q4S+p6W4" crossorigin="anonymous"></script>
</body>
</html>
