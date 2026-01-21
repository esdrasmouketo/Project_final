<?php
/**
 * Middleware d'authentification - GENESIS
 * Inclure ce fichier au début de chaque page protégée
 */

// Démarrer la session si pas encore fait
if (session_status() === PHP_SESSION_NONE) {
    // Configuration sécurisée des cookies de session
    session_set_cookie_params([
        'lifetime' => 0, // Jusqu'à fermeture du navigateur
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']), // HTTPS uniquement si disponible
        'httponly' => true, // Pas accessible via JavaScript
        'samesite' => 'Strict' // Protection CSRF cookies
    ]);
    session_start();
}

// Charger la configuration
require_once __DIR__ . '/config.php';

// Appliquer les headers de sécurité
setSecurityHeaders();

// Vérifier le timeout de session
if (!checkSessionTimeout()) {
    header('Location: login.php?error=session_expired');
    exit();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    // Sauvegarder l'URL demandée pour redirection après login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Régénérer l'ID de session périodiquement (protection fixation de session)
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Mettre à jour le timestamp d'activité
$_SESSION['last_activity'] = time();
?>
