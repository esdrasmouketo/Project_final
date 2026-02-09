<?php
/**
 * Configuration centralisée - GENESIS
 * IMPORTANT: En production, ce fichier ne doit PAS être accessible publiquement
 * et les credentials doivent être stockés dans des variables d'environnement
 */

// =============================================
// CONFIGURATION BASE DE DONNÉES
// =============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'ardbd');
define('DB_USER', 'root');
define('DB_PASS', 'passer'); // En production: utiliser getenv('DB_PASS')
define('DB_CHARSET', 'utf8mb4');

// =============================================
// CONFIGURATION SÉCURITÉ
// =============================================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 1800); // 30 minutes d'inactivité max
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes de blocage

// Extensions autorisées pour les uploads
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf']);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

// =============================================
// CONFIGURATION ARDUINO
// =============================================
define('ARDUINO_IP', '192.168.1.150');
define('ARDUINO_PORT', 80);
define('ARDUINO_TIMEOUT', 5);

// =============================================
// FONCTION DE CONNEXION PDO SÉCURISÉE
// =============================================
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Sécurité: requêtes préparées natives
        ];

        try {
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En production: logger l'erreur, ne pas l'afficher
            error_log("Erreur de connexion DB: " . $e->getMessage());
            die('Erreur de connexion à la base de données.');
        }
    }

    return $conn;
}

// =============================================
// FONCTIONS DE SÉCURITÉ
// =============================================

/**
 * Génère un token CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Vérifie le token CSRF
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Régénère le token CSRF (après soumission réussie)
 */
function regenerateCSRFToken() {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Retourne le champ HTML caché pour CSRF
 */
function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCSRFToken() . '">';
}

/**
 * Échappe les données pour affichage HTML (anti-XSS)
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie si l'extension de fichier est autorisée
 */
function isAllowedExtension($filename, $type = 'image') {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ($type === 'image') ? ALLOWED_IMAGE_EXTENSIONS : ALLOWED_DOCUMENT_EXTENSIONS;
    return in_array($ext, $allowed);
}

/**
 * Vérifie le type MIME réel du fichier
 */
function isValidMimeType($filepath, $type = 'image') {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    if ($type === 'image') {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    } else {
        $allowedMimes = ['application/pdf'];
    }

    return in_array($mime, $allowedMimes);
}

/**
 * Valide et sécurise un fichier uploadé
 */
function validateUpload($file, $type = 'image') {
    $errors = [];

    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur lors de l'upload du fichier.";
        return ['valid' => false, 'errors' => $errors];
    }

    // Vérifier la taille
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = "Le fichier est trop volumineux (max: " . (MAX_UPLOAD_SIZE / 1024 / 1024) . " MB).";
    }

    // Vérifier l'extension
    if (!isAllowedExtension($file['name'], $type)) {
        $allowed = ($type === 'image') ? ALLOWED_IMAGE_EXTENSIONS : ALLOWED_DOCUMENT_EXTENSIONS;
        $errors[] = "Extension non autorisée. Extensions permises: " . implode(', ', $allowed);
    }

    // Vérifier le type MIME
    if (!isValidMimeType($file['tmp_name'], $type)) {
        $errors[] = "Le type de fichier n'est pas valide.";
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}

/**
 * Génère un nom de fichier sécurisé
 */
function generateSecureFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

/**
 * Configure les headers de sécurité HTTP
 */
function setSecurityHeaders() {
    // Empêcher le clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    // Protection XSS navigateur
    header('X-XSS-Protection: 1; mode=block');
    // Empêcher le sniffing MIME
    header('X-Content-Type-Options: nosniff');
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Content Security Policy (basique)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com https://maxcdn.bootstrapcdn.com https://code.jquery.com https://cdn.datatables.net https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://maxcdn.bootstrapcdn.com https://cdn.datatables.net; font-src 'self' https://maxcdn.bootstrapcdn.com; img-src 'self' data:;");
}

/**
 * Vérifie le timeout de session
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}
?>
