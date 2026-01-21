<?php
/**
 * Endpoint de réception des données Arduino sécurisé - GENESIS
 *
 * Ce fichier reçoit les données des capteurs depuis l'Arduino via GET.
 * IMPORTANT: En production, cet endpoint devrait être protégé par:
 * - Une clé API secrète
 * - Une restriction d'adresse IP
 * - HTTPS
 */

// Configuration
$ALLOWED_IPS = ['127.0.0.1', '::1', '192.168.1.150']; // IPs autorisées (localhost + Arduino)
$API_KEY = 'GENESIS_SECRET_KEY_2024'; // Clé API à configurer

// Headers de réponse
header('Content-Type: text/plain; charset=utf-8');

// Vérifier l'IP source (optionnel mais recommandé en production)
// Décommentez en production après avoir configuré les IPs autorisées
/*
$client_ip = $_SERVER['REMOTE_ADDR'];
if (!in_array($client_ip, $ALLOWED_IPS)) {
    http_response_code(403);
    echo "Accès refusé";
    error_log("Tentative d'accès non autorisée à conexion_arduino.php depuis: $client_ip");
    exit;
}
*/

// Vérifier la clé API (optionnel mais recommandé en production)
// Décommentez en production après avoir configuré la clé
/*
$provided_key = $_GET['api_key'] ?? '';
if (!hash_equals($API_KEY, $provided_key)) {
    http_response_code(401);
    echo "Clé API invalide";
    exit;
}
*/

require_once __DIR__ . "/conexion_privada.php";

// Créer la connexion
$conexionObj = new conexion();
$conexionObj->conectar();

// Valider et filtrer les paramètres
$pre_php = filter_var($_GET["pre_php"] ?? null, FILTER_VALIDATE_FLOAT);
$hum_php = filter_var($_GET["hum_php"] ?? null, FILTER_VALIDATE_FLOAT);
$temp_php = filter_var($_GET["temp_php"] ?? null, FILTER_VALIDATE_FLOAT);
$dist_php = filter_var($_GET["dist_php"] ?? null, FILTER_VALIDATE_FLOAT);

// Vérifier que tous les paramètres sont présents et valides
if ($pre_php === false || $hum_php === false || $temp_php === false || $dist_php === false) {
    http_response_code(400);
    echo "Erreur: Paramètres invalides ou manquants";
    error_log("conexion_arduino.php: Paramètres invalides reçus - " . json_encode($_GET));
    exit;
}

// Vérifier les plages de valeurs réalistes
$errors = [];
if ($temp_php < -50 || $temp_php > 100) {
    $errors[] = "Température hors limites (-50 à 100°C)";
}
if ($hum_php < 0 || $hum_php > 100) {
    $errors[] = "Humidité hors limites (0 à 100%)";
}
if ($pre_php < 0) {
    $errors[] = "Pression négative invalide";
}
if ($dist_php < 0) {
    $errors[] = "Distance négative invalide";
}

if (!empty($errors)) {
    http_response_code(400);
    echo "Erreur de validation: " . implode(", ", $errors);
    error_log("conexion_arduino.php: Validation échouée - " . implode(", ", $errors));
    exit;
}

try {
    // Utiliser une requête préparée pour éviter les injections SQL
    $sql = "INSERT INTO tabla_sensor (presion, humedad, temperatura, distancia, fecha) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conexionObj->conexion->prepare($sql);

    $stmt->bindValue(1, $pre_php, PDO::PARAM_STR);
    $stmt->bindValue(2, $hum_php, PDO::PARAM_STR);
    $stmt->bindValue(3, $temp_php, PDO::PARAM_STR);
    $stmt->bindValue(4, $dist_php, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo "Ingreso Exitoso";
    } else {
        http_response_code(500);
        echo "Erreur d'insertion";
        error_log("conexion_arduino.php: Erreur d'insertion - " . implode(", ", $stmt->errorInfo()));
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erreur de base de données";
    error_log("conexion_arduino.php: Exception PDO - " . $e->getMessage());
}
?>
