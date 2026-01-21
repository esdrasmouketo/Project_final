<?php
/**
 * Sauvegarde technicien sécurisée - GENESIS
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
$prenom = trim($_POST['prenom'] ?? '');
$nom = trim($_POST['nom'] ?? '');
$role = trim($_POST['role'] ?? '');
$email = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');

// Validation des champs requis
if (empty($prenom) || empty($nom) || empty($role)) {
    $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires.";
    header('Location: ia.php');
    exit();
}

// Validation de l'email si fourni
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "L'adresse email n'est pas valide.";
    header('Location: ia.php');
    exit();
}

// Validation du téléphone (optionnel)
if (!empty($telephone) && !preg_match('/^[0-9+\-\s]+$/', $telephone)) {
    $_SESSION['error'] = "Le numéro de téléphone n'est pas valide.";
    header('Location: ia.php');
    exit();
}

// Limiter la longueur des champs
$prenom = mb_substr($prenom, 0, 100);
$nom = mb_substr($nom, 0, 100);
$role = mb_substr($role, 0, 100);
$email = mb_substr($email, 0, 255);
$telephone = mb_substr($telephone, 0, 20);

$photo = '';

// Gestion de l'upload de photo
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Valider le fichier uploadé
    $validation = validateUpload($_FILES['photo'], 'image');

    if (!$validation['valid']) {
        $_SESSION['error'] = implode(' ', $validation['errors']);
        header('Location: ia.php');
        exit();
    }

    // Créer le répertoire si nécessaire
    $uploadDir = __DIR__ . '/uploads/photos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom de fichier sécurisé
    $secureFilename = generateSecureFilename($_FILES['photo']['name']);
    $destination = $uploadDir . $secureFilename;

    if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
        $photo = 'uploads/photos/' . $secureFilename;
    } else {
        $_SESSION['error'] = "Erreur lors de l'upload de la photo.";
        header('Location: ia.php');
        exit();
    }
}

try {
    $stmt = $conn->prepare("INSERT INTO techniciens (prenom, nom, role, email, telephone, photo)
                            VALUES (:prenom, :nom, :role, :email, :telephone, :photo)");
    $stmt->execute([
        ':prenom' => $prenom,
        ':nom' => $nom,
        ':role' => $role,
        ':email' => $email,
        ':telephone' => $telephone,
        ':photo' => $photo
    ]);

    $_SESSION['success'] = "Technicien ajouté avec succès.";
    regenerateCSRFToken();

} catch (PDOException $e) {
    error_log("Erreur save_technicien: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'enregistrement.";

    // Supprimer la photo uploadée en cas d'erreur
    if (!empty($photo) && file_exists(__DIR__ . '/' . $photo)) {
        unlink(__DIR__ . '/' . $photo);
    }
}

header('Location: ia.php');
exit();
?>
