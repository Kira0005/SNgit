<?php
// Fichier : logout.php
session_start(); // Démarre la session PHP (nécessaire pour accéder aux variables de session)

// Efface toutes les variables de session
$_SESSION = array(); // Vide le tableau $_SESSION

// Si vous voulez détruire complètement la session, supprimez également le cookie de session.
// Note : Cela détruira la session, pas seulement les données de session !
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruit la session (supprime le fichier de session sur le serveur)
session_destroy();

// Redirige l'utilisateur vers la page de connexion
header("Location: login.php");
exit(); // Très important d'appeler exit() après une redirection pour arrêter l'exécution du script
?>
