<?php
// check_alerts.php
$host = 'localhost';
$db   = 'ardbd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error'=>$e->getMessage()]));
}

// Récupérer les dernières mesures critiques (depuis les 10 dernières secondes)
$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-10 seconds'));

$sql = "SELECT * FROM table_capteurs WHERE date_heure > ? AND 
        (temperature>30 OR temperature<15 OR niveau_eau<20 OR co2_level>800)";
$stmt = $conn->prepare($sql);
$stmt->execute([$last_check]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer alertes
$alerts = [];
foreach($results as $row){
    $msg = [];
    if($row['temperature']>30) $msg[] = "Trop chaud 🌡️ ({$row['temperature']}°C)";
    if($row['temperature']<15) $msg[] = "Trop froid ❄️ ({$row['temperature']}°C)";
    if($row['niveau_eau']<20) $msg[] = "Eau faible 💧 ({$row['niveau_eau']})";
    if($row['co2_level']>800) $msg[] = "CO₂ élevé 🟢 ({$row['co2_level']})";
    if($msg){
        $alerts[] = ['date'=>$row['date_heure'], 'messages'=>implode(' | ', $msg)];
    }
}

echo json_encode($alerts);
?>
