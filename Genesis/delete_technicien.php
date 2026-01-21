<?php
/**
 * Suppression technicien sécurisée - GENESIS
 */
require_once __DIR__ . '/auth_check.php';

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Méthode non autorisée.";
    header('Location: ia.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCSRFToken($_POST[CSRF_TOKEN_NAME])) {
    $_SESSION['error'] = "Erreur de sécurité. Veuillez réessayer.";
    header('Location: ia.php');
    exit();
}

$conn = getDBConnection();

// Récupérer et valider l'ID
$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

if ($id === false || $id <= 0) {
    $_SESSION['error'] = "ID de technicien invalide.";
    header('Location: ia.php');
    exit();
}

try {
    // Récupérer la photo avant suppression pour la nettoyer
    $stmt = $conn->prepare("SELECT photo FROM techniciens WHERE id = ?");
    $stmt->execute([$id]);
    $technicien = $stmt->fetch();

    if (!$technicien) {
        $_SESSION['error'] = "Technicien non trouvé.";
        header('Location: ia.php');
        exit();
    }

    // Supprimer les fiches de maintenance associées
    $deleteMaintenances = $conn->prepare("DELETE FROM maintenance_fiches WHERE technicien_id = ?");
    $deleteMaintenances->execute([$id]);

    // Supprimer le technicien
    $deleteTech = $conn->prepare("DELETE FROM techniciens WHERE id = ?");
    $deleteTech->execute([$id]);

    // Supprimer la photo si elle existe
    if (!empty($technicien['photo']) && file_exists(__DIR__ . '/' . $technicien['photo'])) {
        unlink(__DIR__ . '/' . $technicien['photo']);
    }

    $_SESSION['success'] = "Technicien supprimé avec succès.";
    regenerateCSRFToken();

} catch (PDOException $e) {
    error_log("Erreur delete_technicien: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression.";
}

header('Location: ia.php');
exit();
?>
