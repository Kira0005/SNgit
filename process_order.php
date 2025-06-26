<?php
// Fichier : process_order.php
session_start(); // Démarre la session PHP

require_once 'config/database.php'; // Connexion à la base de données
require_once 'dompdf-3.1.0\dompdf\vendor\autoload.php'; // Autoloader de Composer pour Dompdf et PHPMailer
require_once 'PHPMailer-master\PHPMailer-master\src\PHPMailer.php';
require_once 'PHPMailer-master\PHPMailer-master\src\SMTP.php';
require_once 'PHPMailer-master\PHPMailer-master\src\Exception.php';



use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirige si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

try {
    $pdo->beginTransaction(); // Démarre une transaction pour la commande et le panier

    // 1. Récupérer l'ID du panier de l'utilisateur
    $stmt = $pdo->prepare("SELECT id FROM paniers WHERE utilisateur_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $panier = $stmt->fetch();

    if (!$panier) {
        $message = "Votre panier est vide. Impossible de valider la commande.";
        $messageType = 'danger';
        $pdo->rollBack(); // Annule la transaction si le panier est vide
        // Rediriger vers le panier avec un message d'erreur
        header("Location: cart.php?message=" . urlencode($message) . "&type=" . $messageType);
        exit();
    }

    $panierId = $panier['id'];

    // 2. Récupérer les articles du panier et les détails du produit
    $stmt = $pdo->prepare("
        SELECT
            ap.quantite,
            p.id AS produit_id,
            p.nom AS produit_nom,
            p.prix AS produit_prix,
            p.description AS produit_description
        FROM
            articles_panier ap
        JOIN
            produits p ON ap.produit_id = p.id
        WHERE
            ap.panier_id = :panier_id
    ");
    $stmt->execute([':panier_id' => $panierId]);
    $cartItems = $stmt->fetchAll();

    if (empty($cartItems)) {
        $message = "Votre panier est vide. Impossible de valider la commande.";
        $messageType = 'danger';
        $pdo->rollBack();
        header("Location: cart.php?message=" . urlencode($message) . "&type=" . $messageType);
        exit();
    }

    // 3. Calculer le montant total de la commande
    $montantTotal = 0;
    foreach ($cartItems as $item) {
        $montantTotal += $item['quantite'] * $item['produit_prix'];
    }

    // 4. Récupérer les détails de l'utilisateur pour la commande et l'email
    $stmt = $pdo->prepare("SELECT nom, prenom, email, adresse, ville, code_postal, pays FROM utilisateurs WHERE id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "Erreur: Utilisateur non trouvé.";
        $messageType = 'danger';
        $pdo->rollBack();
        header("Location: cart.php?message=" . urlencode($message) . "&type=" . $messageType);
        exit();
    }

    // 5. Insérer la commande dans la table `commandes`
    $stmt = $pdo->prepare(
        "INSERT INTO commandes (utilisateur_id, date_commande, statut_commande, montant_total, adresse_livraison, ville_livraison, code_postal_livraison, pays_livraison, mode_paiement)
         VALUES (:utilisateur_id, NOW(), :statut_commande, :montant_total, :adresse_livraison, :ville_livraison, :code_postal_livraison, :pays_livraison, :mode_paiement)"
    );
    $stmt->execute([
        ':utilisateur_id' => $userId,
        ':statut_commande' => 'en_attente', // Statut initial de la commande
        ':montant_total' => $montantTotal,
        ':adresse_livraison' => $user['adresse'],
        ':ville_livraison' => $user['ville'],
        ':code_postal_livraison' => $user['code_postal'],
        ':pays_livraison' => $user['pays'],
        ':mode_paiement' => 'A la livraison' // Ou tout autre mode de paiement par défaut
    ]);
    $commandeId = $pdo->lastInsertId(); // Récupère l'ID de la commande nouvellement créée

    // 6. Insérer les détails de la commande dans la table `details_commande`
    foreach ($cartItems as $item) {
        $stmt = $pdo->prepare(
            "INSERT INTO details_commande (commande_id, produit_id, quantite, prix_unitaire)
             VALUES (:commande_id, :produit_id, :quantite, :prix_unitaire)"
        );
        $stmt->execute([
            ':commande_id' => $commandeId,
            ':produit_id' => $item['produit_id'],
            ':quantite' => $item['quantite'],
            ':prix_unitaire' => $item['produit_prix'] // Prix du produit au moment de la commande
        ]);
        // Optionnel: Mettre à jour le stock du produit si nécessaire (décrémenter la quantité_stock)
        // $stmtUpdateStock = $pdo->prepare("UPDATE produits SET quantite_stock = quantite_stock - :quantite WHERE id = :produit_id");
        // $stmtUpdateStock->execute([':quantite' => $item['quantite'], ':produit_id' => $item['produit_id']]);
    }

    // 7. Vider le panier de l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM articles_panier WHERE panier_id = :panier_id");
    $stmt->execute([':panier_id' => $panierId]);
    $stmt = $pdo->prepare("DELETE FROM paniers WHERE id = :panier_id");
    $stmt->execute([':panier_id' => $panierId]);

    $pdo->commit(); // Valide toutes les opérations de la transaction

    // --- Génération de la facture PDF avec Dompdf ---
    $options = new Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isHtml5ParserEnabled', true); // Important pour un HTML moderne
    $options->set('isRemoteEnabled', true); // Permet de charger des images ou CSS externes (si besoin)
    $dompdf = new Dompdf($options);

    // Contenu HTML de la facture (très important de le construire proprement)
    ob_start(); // Commence la mise en tampon de sortie pour capturer le HTML
    include 'invoice_template.php'; // Inclut un template HTML pour la facture
    $html = ob_get_clean(); // Récupère le contenu HTML et vide le tampon

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // S'assurer que le dossier 'factures' existe
    $facturesDir = 'factures';
    if (!is_dir($facturesDir)) {
        mkdir($facturesDir, 0755, true); // Crée le dossier avec les permissions 0755
    }
    $pdfFileName = 'facture_chicrush_commande_' . $commandeId . '.pdf';
    $pdfFilePath = $facturesDir . '/' . $pdfFileName;
    file_put_contents($pdfFilePath, $dompdf->output());

    // --- Envoi de la facture par e-mail avec PHPMailer ---
    $mail = new PHPMailer(true);

    try {
        // Configuration du serveur SMTP (À MODIFIER AVEC VOS PROPRES PARAMÈTRES !)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Ex: 'smtp.gmail.com', 'mail.votre-domaine.com'
        $mail->SMTPAuth   = true;
        $mail->Username   = 'alex6.draken@gmail.com'; // Votre adresse e-mail SMTP
        $mail->Password   = 'qrpt xbbz upuk zmzv'; // Le mot de passe de votre compte email SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Ou PHPMailer::ENCRYPTION_SMTPS pour le port 465
        $mail->Port       = 587; // Port SMTP (587 pour TLS, 465 pour SMTPS)

        // Expéditeur et destinataire
        $mail->setFrom('no-reply@chicrosh.com', 'ChicRush');
        $mail->addAddress($user['email'], $user['prenom'] . ' ' . $user['nom']);

        // Pièce jointe
        $mail->addAttachment($pdfFilePath, $pdfFileName);

        // Contenu de l'e-mail
        $mail->isHTML(true);
        $mail->Subject = 'Votre facture ChicRush - Commande #' . $commandeId;
        $mail->Body    = '
            <p>Bonjour ' . htmlspecialchars($user['prenom']) . ',</p>
            <p>Nous vous remercions pour votre commande n° <strong>' . htmlspecialchars($commandeId) . '</strong> passée sur ChicRush.</p>
            <p>Vous trouverez votre facture en pièce jointe de cet e-mail.</p>
            <p>Pour toute question, n\'hésitez pas à nous contacter.</p>
            <p>Cordialement,<br>L\'équipe ChicRush</p>
        ';
        $mail->AltBody = 'Bonjour ' . htmlspecialchars($user['prenom']) . ', Votre commande n°' . htmlspecialchars($commandeId) . ' sur ChicRush a été confirmée. Vous trouverez votre facture en pièce jointe. L\'équipe ChicRush.';

        $mail->send();
        $message = "Commande validée et facture envoyée par e-mail !";
        $messageType = 'success';

    } catch (Exception $e) {
        // En cas d'échec de l'envoi d'email, la commande est tout de même validée dans la DB
        error_log("Erreur lors de l'envoi de l'e-mail: {$mail->ErrorInfo}");
        $message = "Commande validée, mais l'envoi de la facture par e-mail a échoué. Veuillez nous contacter si vous ne la recevez pas.";
        $messageType = 'warning';
    }

    // Rediriger vers une page de confirmation ou vers l'accueil
    header("Location: confirmation.php?commande_id=" . $commandeId . "&message=" . urlencode($message) . "&type=" . $messageType);
    exit();

} catch (PDOException $e) {
    $pdo->rollBack(); // Annule la transaction si une erreur de base de données survient
    error_log("Erreur PDO lors de la validation de commande : " . $e->getMessage());
    $message = "Une erreur est survenue lors de la validation de votre commande. Veuillez réessayer.";
    $messageType = 'danger';
    header("Location: cart.php?message=" . urlencode($message) . "&type=" . $messageType);
    exit();
}
