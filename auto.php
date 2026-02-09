<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <title>Simulation IoT Réaliste</title>
</head>
<body>

<?php
// ================== CONNEXION BDD ==================
$conn = new mysqli("localhost", "root", "passer", "ardbd");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// ================== FONCTIONS ==================
function clamp($value, $min, $max) {
    return max($min, min($max, $value));
}

function drift($value, $step) {
    return $value + rand(-$step * 10, $step * 10) / 10;
}

function calculerAjustement($valeur) {
    $entier = intval($valeur);
    if ($entier < 10) {
        return 40;
    }
    if ($entier % 2 == 0) {
        return 130;
    }
    return 150;
}

// ================== TEMPS ==================
$heure = date("G"); // heure courante (0-23)

// ================== CYCLE JOUR / NUIT ==================
if ($heure >= 6 && $heure <= 18) {
    $lumiere = rand(500, 900); // jour
    $temperature = rand(260, 320) / 10;
} else {
    $lumiere = rand(10, 80);   // nuit
    $temperature = rand(200, 250) / 10;
}

// ================== AJUSTEMENT TEMPÉRATURE ==================
$ajust_temperature = calculerAjustement($temperature);

// ================== CO2 (photosynthèse) ==================
$co2 = ($lumiere > 400)
    ? rand(380, 420)  // absorption
    : rand(420, 500); // accumulation

// ================== ARROSAGE INTELLIGENT ==================
$result = $conn->query("SELECT humidity, niveau_eau FROM table_capteurs ORDER BY id DESC LIMIT 1");

if ($result->num_rows > 0) {
    $last = $result->fetch_assoc();
    $humidity = drift($last['humidity'], 1);
    $niveau_eau = drift($last['niveau_eau'], 0.5);
} else {
    $humidity = 60;
    $niveau_eau = 70;
}

// seuils
$arrosage = 0;
if ($humidity < 55 && $niveau_eau > 15) {
    $arrosage = 1;
    $humidity += rand(2, 5);
    $niveau_eau -= rand(1, 3);
}

// ================== CONTRAINTES PHYSIQUES ==================
$humidity   = clamp($humidity, 40, 95);
$niveau_eau = clamp($niveau_eau, 0, 100);

// ================== AJUSTEMENTS CAPTEURS ==================
$ajust_humidite   = calculerAjustement($humidity);
$ajust_lumiere    = calculerAjustement($lumiere);
$ajust_eau        = calculerAjustement($niveau_eau);
$ajust_arrosage   = calculerAjustement($arrosage);
$ajust_co2        = calculerAjustement($co2);

// ================== INSERTION ==================
$stmt = $conn->prepare("
INSERT INTO table_capteurs
(niveau_eau, niveau_lumiere, arrosage, co2_level, temperature, humidity)
VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "dddddd",
    $niveau_eau,
    $lumiere,
    $arrosage,
    $co2,
    $temperature,
    $humidity
);

$stmt->execute();

// ================== AFFICHAGE ==================
echo "<h3>Simulation IoT réaliste en cours</h3>";
echo "<ul>
<li>Heure : {$heure}h</li>
<li>Lumière : {$lumiere} lux</li>
<li>Température : {$temperature} °C</li>
<li>Humidité : {$humidity} %</li>
<li>CO₂ : {$co2} ppm</li>
<li>Niveau d’eau : {$niveau_eau} %</li>
<li>Arrosage : " . ($arrosage ? "ACTIF" : "INACTIF") . "</li>
</ul>";

echo "<h3>Ajustements par capteur</h3>";
echo "<table border='1' cellpadding='5' cellspacing='0'>
<tr><th>Capteur</th><th>Valeur</th><th>Base entière</th><th>Ajustement</th></tr>
<tr><td>Température</td><td>{$temperature} °C</td><td>" . intval($temperature) . "</td><td>+{$ajust_temperature}</td></tr>
<tr><td>Humidité</td><td>{$humidity} %</td><td>" . intval($humidity) . "</td><td>+{$ajust_humidite}</td></tr>
<tr><td>Lumière</td><td>{$lumiere} lux</td><td>" . intval($lumiere) . "</td><td>+{$ajust_lumiere}</td></tr>
<tr><td>Niveau d'eau</td><td>{$niveau_eau} %</td><td>" . intval($niveau_eau) . "</td><td>+{$ajust_eau}</td></tr>
<tr><td>Arrosage</td><td>" . ($arrosage ? "ACTIF (1)" : "INACTIF (0)") . "</td><td>{$arrosage}</td><td>+{$ajust_arrosage}</td></tr>
<tr><td>CO₂</td><td>{$co2} ppm</td><td>" . intval($co2) . "</td><td>+{$ajust_co2}</td></tr>
</table>";

$stmt->close();
$conn->close();
?>

</body>
</html>
