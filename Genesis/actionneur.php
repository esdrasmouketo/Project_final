<?php
session_start();
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Action non reconnue'];

// IP et port de l'Arduino
$arduino_ip = '192.168.1.150';
$arduino_port = 80;

// Fonction pour envoyer la commande à l'Arduino
function sendToArduino($ip, $port, $command){
    $fp = @fsockopen($ip, $port, $errno, $errstr, 2);
    if (!$fp) return false;
    fwrite($fp, $command);
    fclose($fp);
    return true;
}

switch($action){
    case 'pompe_on':
        if(sendToArduino($arduino_ip, $arduino_port, "POMPE_ON")) $response = ['success'=>true, 'message'=>'Pompe activée'];
        else $response['message']='Erreur connexion Arduino';
        break;
    case 'pompe_off':
        if(sendToArduino($arduino_ip, $arduino_port, "POMPE_OFF")) $response = ['success'=>true, 'message'=>'Pompe désactivée'];
        else $response['message']='Erreur connexion Arduino';
        break;
    case 'electrovanne_open':
        if(sendToArduino($arduino_ip, $arduino_port, "EV_OPEN")) $response = ['success'=>true, 'message'=>'Électrovanne ouverte'];
        else $response['message']='Erreur connexion Arduino';
        break;
    case 'electrovanne_close':
        if(sendToArduino($arduino_ip, $arduino_port, "EV_CLOSE")) $response = ['success'=>true, 'message'=>'Électrovanne fermée'];
        else $response['message']='Erreur connexion Arduino';
        break;
    case 'light_on':
        if(sendToArduino($arduino_ip, $arduino_port, "LIGHT_ON")) $response = ['success'=>true, 'message'=>'Lumière activée'];
        else $response['message']='Erreur connexion Arduino';
        break;
    case 'light_off':
        if(sendToArduino($arduino_ip, $arduino_port, "LIGHT_OFF")) $response = ['success'=>true, 'message'=>'Lumière désactivée'];
        else $response['message']='Erreur connexion Arduino';
        break;
}

echo json_encode($response);
