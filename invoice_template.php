<?php
// Fichier : invoice_template.php
// Ce fichier est inclus par process_order.php.
// Les variables $commandeId, $cartItems, $montantTotal, $user (et autres) sont disponibles ici.
// Ce HTML sera capturé par ob_start() et Dompdf pour générer la facture PDF.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture Commande #<?= htmlspecialchars($commandeId) ?></title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; line-height: 1.6; color: #333; }
        .container { width: 90%; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #4a0e4e; margin: 0; }
        .header p { margin: 5px 0; font-size: 0.9em; }
        .section { margin-bottom: 20px; }
        .section h2 { color: #4a0e4e; font-size: 1.2em; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f2f2f2; color: #555; }
        .text-right { text-align: right; }
        .total-section { margin-top: 30px; text-align: right; }
        .total-section p { font-size: 1.1em; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 0.8em; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Facture ChicRush</h1>
            <p>Commande N°: <strong><?= htmlspecialchars($commandeId) ?></strong></p>
            <p>Date de la commande: <strong><?= date('d/m/Y H:i:s') ?></strong></p>
            <p>Adresse e-mail: contact@chicrosh.com | Téléphone: +237 6XXXXXXXXX</p>
        </div>

        <div class="section">
            <h2>Informations Client</h2>
            <p><strong>Nom:</strong> <?= htmlspecialchars($user['prenom']) ?> <?= htmlspecialchars($user['nom']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Adresse de livraison:</strong> <?= htmlspecialchars($user['adresse']) ?>, <?= htmlspecialchars($user['code_postal']) ?> <?= htmlspecialchars($user['ville']) ?>, <?= htmlspecialchars($user['pays']) ?></p>
        </div>

        <div class="section">
            <h2>Détails de la Commande</h2>
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th class="text-right">Prix Unitaire</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['produit_nom']) ?></td>
                            <td><?= htmlspecialchars($item['quantite']) ?></td>
                            <td class="text-right"><?= number_format($item['produit_prix'], 2, ',', ' ') ?> €</td>
                            <td class="text-right"><?= number_format($item['quantite'] * $item['produit_prix'], 2, ',', ' ') ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <p>Montant Total (TTC): <strong><?= number_format($montantTotal, 2, ',', ' ') ?> €</strong></p>
        </div>

        <div class="footer">
            <p>&copy; <?= date('Y') ?> ChicRush. Tous droits réservés.</p>
            <p>Merci pour votre confiance !</p>
        </div>
    </div>
</body>
</html>
