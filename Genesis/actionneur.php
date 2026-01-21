<?php
/**
 * API de contrôle des actionneurs sécurisée - GENESIS
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger la configuration
require_once __DIR__ . '/config.php';

// Headers JSON
header('Content-Type: application/json');

// Vérifier l'authentification
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé. Veuillez vous connecter.']);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCSRFToken($_POST[CSRF_TOKEN_NAME])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Erreur de sécurité. Veuillez rafraîchir la page.']);
    exit();
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Action non reconnue'];

// Liste des actions autorisées
$allowed_actions = [
    'pompe_on' => 'POMPE_ON',
    'pompe_off' => 'POMPE_OFF',
    'electrovanne_open' => 'EV_OPEN',
    'electrovanne_close' => 'EV_CLOSE',
    'light_on' => 'LIGHT_ON',
    'light_off' => 'LIGHT_OFF'
];

// Messages de succès
$success_messages = [
    'pompe_on' => 'Pompe activée',
    'pompe_off' => 'Pompe désactivée',
    'electrovanne_open' => 'Électrovanne ouverte',
    'electrovanne_close' => 'Électrovanne fermée',
    'light_on' => 'Lumière activée',
    'light_off' => 'Lumière désactivée'
];

// Fonction pour envoyer la commande à l'Arduino
function sendToArduino($ip, $port, $command, $timeout = 5) {
    $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        error_log("Erreur connexion Arduino: $errstr ($errno)");
        return false;
    }

    // Définir un timeout pour la lecture/écriture
    stream_set_timeout($fp, $timeout);

    $result = fwrite($fp, $command);
    fclose($fp);

    return $result !== false;
}

// Vérifier si l'action est autorisée
if (!isset($allowed_actions[$action])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action non reconnue: ' . htmlspecialchars($action)]);
    exit();
}

// Envoyer la commande à l'Arduino
$command = $allowed_actions[$action];
if (sendToArduino(ARDUINO_IP, ARDUINO_PORT, $command, ARDUINO_TIMEOUT)) {
    $response = [
        'success' => true,
        'message' => $success_messages[$action],
        'action' => $action,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Logger l'action
    error_log("Actionneur: " . $_SESSION['username'] . " a exécuté '$action' - Commande: $command");
} else {
    $response = [
        'success' => false,
        'message' => 'Erreur de connexion à l\'Arduino. Vérifiez que l\'appareil est en ligne.',
        'action' => $action
    ];
}

echo json_encode($response);
?>
