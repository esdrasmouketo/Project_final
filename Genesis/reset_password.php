<?php
/**
 * Réinitialisation de mot de passe - GENESIS
 * Fichier protégé - Accès admin uniquement
 */
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/config.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ia.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCSRFToken($_POST[CSRF_TOKEN_NAME])) {
    $_SESSION['error'] = "Erreur de sécurité. Veuillez réessayer.";
    header('Location: ia.php');
    exit();
}

// Récupérer les données
$email = trim($_POST['email'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
$errors = [];

if (empty($email)) {
    $errors[] = "L'email est requis.";
}

if (empty($new_password)) {
    $errors[] = "Le nouveau mot de passe est requis.";
}

if ($new_password !== $confirm_password) {
    $errors[] = "Les mots de passe ne correspondent pas.";
}

if (strlen($new_password) < 6) {
    $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
}

// Valider l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'adresse email n'est pas valide.";
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header('Location: ia.php');
    exit();
}

try {
    $conn = getDBConnection();

    // Vérifier que l'utilisateur existe
    $stmt = $conn->prepare("SELECT id FROM techniciens WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "Cet utilisateur n'existe pas.";
        header('Location: ia.php');
        exit();
    }

    // Générer le hash bcrypt du nouveau mot de passe
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe
    $updateStmt = $conn->prepare("UPDATE techniciens SET mot_de_passe = ? WHERE email = ?");
    $updateStmt->execute([$hashed_password, $email]);

    $_SESSION['success'] = "Mot de passe réinitialisé avec succès pour " . e($email);
    header('Location: ia.php');
    exit();

} catch (PDOException $e) {
    error_log("Erreur réinitialisation: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la réinitialisation.";
    header('Location: ia.php');
    exit();
}
?>
