<?php
/**
 * Sauvegarde fiche maintenance sécurisée - GENESIS
 */
require_once __DIR__ . '/auth_check.php';

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

$conn = getDBConnection();

// Récupérer et valider les données
$technicien_id = filter_var($_POST['technicien_id'] ?? '', FILTER_VALIDATE_INT);
$date_maint = $_POST['date_maintenance'] ?? '';
$description = trim($_POST['description'] ?? '');

// Validation du technicien
if ($technicien_id === false || $technicien_id <= 0) {
    $_SESSION['error'] = "Veuillez sélectionner un technicien valide.";
    header('Location: ia.php');
    exit();
}

// Vérifier que le technicien existe
$checkStmt = $conn->prepare("SELECT id FROM techniciens WHERE id = ?");
$checkStmt->execute([$technicien_id]);
if (!$checkStmt->fetch()) {
    $_SESSION['error'] = "Technicien non trouvé.";
    header('Location: ia.php');
    exit();
}

// Validation de la date
if (empty($date_maint)) {
    $_SESSION['error'] = "Veuillez indiquer une date de maintenance.";
    header('Location: ia.php');
    exit();
}

// Convertir et valider le format de date
$dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $date_maint);
if (!$dateTime) {
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $date_maint);
}
if (!$dateTime) {
    $_SESSION['error'] = "Format de date invalide.";
    header('Location: ia.php');
    exit();
}
$date_maint = $dateTime->format('Y-m-d H:i:s');

// Limiter la longueur de la description
$description = mb_substr($description, 0, 1000);

$fichier = '';

// Gestion de l'upload de fichier PDF
if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
    // Valider le fichier uploadé
    $validation = validateUpload($_FILES['fichier'], 'document');

    if (!$validation['valid']) {
        $_SESSION['error'] = implode(' ', $validation['errors']);
        header('Location: ia.php');
        exit();
    }

    // Créer le répertoire si nécessaire
    $uploadDir = __DIR__ . '/uploads/fiches/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier sécurisé
    $secureFilename = generateSecureFilename($_FILES['fichier']['name']);
    $destination = $uploadDir . $secureFilename;

    if (move_uploaded_file($_FILES['fichier']['tmp_name'], $destination)) {
        $fichier = 'uploads/fiches/' . $secureFilename;
    } else {
        $_SESSION['error'] = "Erreur lors de l'upload du fichier.";
        header('Location: ia.php');
        exit();
    }
}

try {
    $stmt = $conn->prepare("INSERT INTO maintenance_fiches (technicien_id, date_maintenance, description, fichier)
                            VALUES (:tech, :date, :desc, :fichier)");
    $stmt->execute([
        ':tech' => $technicien_id,
        ':date' => $date_maint,
        ':desc' => $description,
        ':fichier' => $fichier
    ]);

    $_SESSION['success'] = "Fiche de maintenance ajoutée avec succès.";
    regenerateCSRFToken();

} catch (PDOException $e) {
    error_log("Erreur save_fiche: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'enregistrement.";

    // Supprimer le fichier uploadé en cas d'erreur
    if (!empty($fichier) && file_exists(__DIR__ . '/' . $fichier)) {
        unlink(__DIR__ . '/' . $fichier);
    }
}

header('Location: ia.php');
exit();
?>
