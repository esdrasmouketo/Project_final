<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!-- Rafraichissement automatique de la page toutes les 5 secondes -->
    <meta http-equiv="refresh" content="5">
    <title>Capteurs IoT GENESIS</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f7f0;
            color: #2d2d2d;
            padding: 30px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        h2 {
            color: #ffffff;
            background-color: #2e7d32;
            padding: 15px 25px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }

        h3 {
            color: #2e7d32;
            margin: 25px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #a5d6a7;
        }

        .card {
            background: #ffffff;
            border: 1px solid #c8e6c9;
            border-radius: 0 0 8px 8px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(46,125,50,0.1);
        }

        ul {
            list-style: none;
            padding: 0;
        }

        ul li {
            padding: 8px 12px;
            border-bottom: 1px solid #e8f5e9;
            display: flex;
            justify-content: space-between;
        }

        ul li:last-child { border-bottom: none; }
        ul li:hover { background-color: #f1f8e9; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .badge-green { background-color: #c8e6c9; color: #2e7d32; }
        .badge-red { background-color: #ffcdd2; color: #c62828; }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #888;
            font-size: 1.2em;
        }

        .timestamp {
            text-align: right;
            color: #888;
            font-size: 0.85em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">

<?php
// ================== CONNEXION A LA BASE DE DONNEES ==================
$conn = new mysqli("localhost", "root", "", "ardbd");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// ================== FONCTION D'AJUSTEMENT ==================
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

// ================== LECTURE DES DERNIERES DONNEES REELLES ==================
$result = $conn->query("SELECT * FROM table_capteurs ORDER BY id DESC LIMIT 1");

echo "<h2>Capteurs IoT GENESIS</h2>";
echo "<div class='card'>";

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();

    // Valeurs brutes recues du drone
    $temperature   = $data['temperature'];
    $humidity      = $data['humidity'];
    $lumiere       = $data['niveau_lumiere'];
    $niveau_eau    = $data['niveau_eau'];
    $co2           = $data['co2_level'];
    $arrosage      = $data['arrosage'];
    $date_heure    = $data['date_heure'];

    // Application des conditions (ajustements) aux valeurs
    $aff_temperature = $temperature + calculerAjustement($temperature);
    $aff_humidity    = $humidity    + calculerAjustement($humidity);
    $aff_lumiere     = $lumiere     + calculerAjustement($lumiere);
    $aff_niveau_eau  = $niveau_eau  + calculerAjustement($niveau_eau);
    $aff_co2         = $co2         + calculerAjustement($co2);
    $aff_arrosage    = $arrosage    + calculerAjustement($arrosage);

    echo "<h3>Donnees capteurs (avec conditions)</h3>";
    echo "<ul>
    <li><span>Temperature</span><span>{$aff_temperature} °C</span></li>
    <li><span>Humidite</span><span>{$aff_humidity} %</span></li>
    <li><span>Lumiere</span><span>{$aff_lumiere} lux</span></li>
    <li><span>CO2</span><span>{$aff_co2} ppm</span></li>
    <li><span>Niveau d'eau</span><span>{$aff_niveau_eau} %</span></li>
    <li><span>Arrosage</span><span class='badge badge-green'>" . ($arrosage ? "ACTIF" : "INACTIF") . "</span></li>
    </ul>";

    echo "<p class='timestamp'>Derniere mise a jour : {$date_heure}</p>";

} else {
    echo "<p class='no-data'>Aucune donnee recue. En attente des capteurs du drone...</p>";
}

echo "</div>";

$conn->close();
?>

</div>
</body>
</html>
