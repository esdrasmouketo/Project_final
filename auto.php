<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <!-- Rafraichissement automatique de la page toutes les 5 secondes -->
    <meta http-equiv="refresh" content="5">
    <title>Simulation IoT Réaliste</title>

    <!-- ==================== STYLE CSS ==================== -->
    <!-- Design vert et blanc pour l'interface de simulation -->
    <style>
        /* Reset des marges et padding par defaut du navigateur */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Style general du body : fond vert tres clair, police moderne */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f7f0;
            color: #2d2d2d;
            padding: 30px;
        }

        /* Conteneur principal centre avec largeur max de 800px */
        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* Titre principal : bandeau vert fonce avec texte blanc */
        h2 {
            color: #ffffff;
            background-color: #2e7d32;
            padding: 15px 25px;
            border-radius: 8px 8px 0 0; /* Coins arrondis en haut seulement */
            margin-bottom: 0;
        }

        /* Sous-titres : texte vert avec ligne de separation */
        h3 {
            color: #2e7d32;
            margin: 25px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #a5d6a7;
        }

        /* Carte blanche contenant les donnees */
        .card {
            background: #ffffff;
            border: 1px solid #c8e6c9;
            border-radius: 0 0 8px 8px; /* Coins arrondis en bas seulement */
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(46,125,50,0.1); /* Ombre verte legere */
        }

        /* Liste sans puces */
        ul {
            list-style: none;
            padding: 0;
        }

        /* Chaque element de liste : flexbox pour aligner label a gauche et valeur a droite */
        ul li {
            padding: 8px 12px;
            border-bottom: 1px solid #e8f5e9;
            display: flex;
            justify-content: space-between;
        }

        /* Pas de bordure sur le dernier element */
        ul li:last-child { border-bottom: none; }

        /* Effet survol sur les elements de liste */
        ul li:hover { background-color: #f1f8e9; }

        /* Tableau des ajustements : pleine largeur, bordures fusionnees */
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
        }

        /* En-tetes du tableau : fond vert fonce, texte blanc */
        th {
            background-color: #2e7d32;
            color: #ffffff;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }

        /* Cellules du tableau */
        td {
            padding: 10px 15px;
            border-bottom: 1px solid #e8f5e9;
        }

        /* Alternance de couleur sur les lignes du tableau (pair = vert clair) */
        tr:nth-child(even) { background-color: #f1f8e9; }
        tr:nth-child(odd) { background-color: #ffffff; }

        /* Effet survol sur les lignes du tableau */
        tr:hover { background-color: #dcedc8; }

        /* Badge : etiquette arrondie pour mettre en valeur une information */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.9em;
        }

        /* Badge vert : fond vert clair, texte vert fonce */
        .badge-green { background-color: #c8e6c9; color: #2e7d32; }
    </style>
</head>
<body>
<!-- Conteneur principal de la page -->
<div class="container">

<?php
// ================== CONNEXION A LA BASE DE DONNEES ==================
// Connexion a MySQL via mysqli : serveur localhost, utilisateur root, mot de passe vide, base "ardbd"
$conn = new mysqli("localhost", "root", "", "ardbd");
// Si la connexion echoue, on arrete le script et on affiche l'erreur
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

// ================== FONCTIONS UTILITAIRES ==================

/**
 * clamp() - Limite une valeur entre un minimum et un maximum
 * @param float $value : la valeur a limiter
 * @param float $min   : la borne inferieure
 * @param float $max   : la borne superieure
 * @return float : la valeur bornee entre $min et $max
 * Exemple : clamp(110, 0, 100) retourne 100
 */
function clamp($value, $min, $max) {
    return max($min, min($max, $value));
}

/**
 * drift() - Ajoute une variation aleatoire a une valeur (simulation de bruit capteur)
 * @param float $value : la valeur de depart
 * @param float $step  : l'amplitude maximale de la variation
 * @return float : la valeur avec une variation aleatoire entre -$step et +$step
 * Exemple : drift(60, 1) peut retourner entre 59.0 et 61.0
 */
function drift($value, $step) {
    return $value + rand(-$step * 10, $step * 10) / 10;
}

/**
 * calculerAjustement() - Calcule l'ajustement en fonction de la valeur d'un capteur
 * Regles :
 *   - Si la valeur (partie entiere) est inferieure a 10 : retourne +40
 *   - Si la valeur (partie entiere) est paire et >= 10  : retourne +130
 *   - Si la valeur (partie entiere) est impaire et >= 10 : retourne +150
 * @param float $valeur : la valeur du capteur
 * @return int : l'ajustement a appliquer (40, 130 ou 150)
 */
function calculerAjustement($valeur) {
    $entier = intval($valeur); // On prend la partie entiere pour determiner la parite
    if ($entier < 10) {
        return 40;  // Toute valeur < 10 : ajustement de 40
    }
    if ($entier % 2 == 0) {
        return 130; // Valeur paire >= 10 : ajustement de 130
    }
    return 150;     // Valeur impaire >= 10 : ajustement de 150
}

// ================== TEMPS ==================
// Recupere l'heure courante au format 24h (0 a 23) sans zero initial
$heure = date("G");

// ================== CYCLE JOUR / NUIT ==================
// Simule des conditions differentes selon que c'est le jour (6h-18h) ou la nuit
if ($heure >= 6 && $heure <= 18) {
    // JOUR : lumiere forte (500-900 lux), temperature elevee (26.0-32.0 °C)
    $lumiere = rand(500, 900);
    $temperature = rand(260, 320) / 10; // Division par 10 pour avoir un float (ex: 260/10 = 26.0)
} else {
    // NUIT : lumiere faible (10-80 lux), temperature plus basse (20.0-25.0 °C)
    $lumiere = rand(10, 80);
    $temperature = rand(200, 250) / 10;
}

// ================== AJUSTEMENT TEMPERATURE ==================
// Calcule l'ajustement pour la temperature generee
$ajust_temperature = calculerAjustement($temperature);

// ================== CO2 (photosynthese) ==================
// Si lumiere > 400 lux (jour) : la plante absorbe le CO2 (380-420 ppm)
// Sinon (nuit) : le CO2 s'accumule (420-500 ppm)
$co2 = ($lumiere > 400)
    ? rand(380, 420)  // Absorption par photosynthese en journee
    : rand(420, 500); // Accumulation la nuit (pas de photosynthese)

// ================== ARROSAGE INTELLIGENT ==================
// Recupere les dernieres valeurs d'humidite et de niveau d'eau depuis la BDD
$result = $conn->query("SELECT humidity, niveau_eau FROM table_capteurs ORDER BY id DESC LIMIT 1");

if ($result->num_rows > 0) {
    // Si des donnees existent, on applique un drift (variation realiste) aux valeurs precedentes
    $last = $result->fetch_assoc();
    $humidity = drift($last['humidity'], 1);       // Variation de +/- 1% d'humidite
    $niveau_eau = drift($last['niveau_eau'], 0.5); // Variation de +/- 0.5% du niveau d'eau
} else {
    // Si la table est vide (premiere execution), on initialise avec des valeurs par defaut
    $humidity = 60;    // 60% d'humidite
    $niveau_eau = 70;  // 70% de niveau d'eau
}

// Seuils d'arrosage automatique
$arrosage = 0; // Par defaut, l'arrosage est desactive
if ($humidity < 55 && $niveau_eau > 15) {
    // Si l'humidite est trop basse (<55%) ET qu'il reste assez d'eau (>15%) :
    $arrosage = 1;                  // On active l'arrosage
    $humidity += rand(2, 5);        // L'humidite augmente de 2 a 5%
    $niveau_eau -= rand(1, 3);      // Le niveau d'eau baisse de 1 a 3%
}

// ================== CONTRAINTES PHYSIQUES ==================
// On borne les valeurs pour rester dans des plages realistes
$humidity   = clamp($humidity, 40, 95);   // Humidite entre 40% et 95%
$niveau_eau = clamp($niveau_eau, 0, 100); // Niveau d'eau entre 0% et 100%

// ================== AJUSTEMENTS POUR TOUS LES CAPTEURS ==================
// On applique la meme fonction calculerAjustement() a chaque capteur
$ajust_humidite   = calculerAjustement($humidity);    // Ajustement pour l'humidite
$ajust_lumiere    = calculerAjustement($lumiere);      // Ajustement pour la lumiere
$ajust_eau        = calculerAjustement($niveau_eau);   // Ajustement pour le niveau d'eau
$ajust_arrosage   = calculerAjustement($arrosage);     // Ajustement pour l'arrosage (toujours +40 car 0 ou 1 < 10)
$ajust_co2        = calculerAjustement($co2);          // Ajustement pour le CO2

// ================== INSERTION EN BASE DE DONNEES ==================
// Prepare une requete securisee (prepared statement) pour inserer les donnees des capteurs + ajustements
$stmt = $conn->prepare("
INSERT INTO table_capteurs
(niveau_eau, niveau_lumiere, arrosage, co2_level, temperature, humidity,
 ajust_temperature, ajust_humidite, ajust_lumiere, ajust_eau, ajust_arrosage, ajust_co2)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// Lie les parametres : "dddddd" = 6 doubles (capteurs) + "iiiiii" = 6 entiers (ajustements)
$stmt->bind_param(
    "ddddddiiiiii",
    $niveau_eau,        // Parametre 1 : niveau d'eau
    $lumiere,           // Parametre 2 : niveau de lumiere
    $arrosage,          // Parametre 3 : statut arrosage (0 ou 1)
    $co2,               // Parametre 4 : niveau de CO2
    $temperature,       // Parametre 5 : temperature
    $humidity,          // Parametre 6 : humidite
    $ajust_temperature, // Parametre 7 : ajustement temperature
    $ajust_humidite,    // Parametre 8 : ajustement humidite
    $ajust_lumiere,     // Parametre 9 : ajustement lumiere
    $ajust_eau,         // Parametre 10 : ajustement niveau d'eau
    $ajust_arrosage,    // Parametre 11 : ajustement arrosage
    $ajust_co2          // Parametre 12 : ajustement CO2
);

// Execute l'insertion dans la base de donnees
if (!$stmt->execute()) {
    echo "<p style='color:red;font-weight:bold;'>ERREUR INSERT : " . $stmt->error . "</p>";
}
if ($conn->error) {
    echo "<p style='color:red;font-weight:bold;'>ERREUR MySQL : " . $conn->error . "</p>";
}

// ================== AFFICHAGE HTML ==================

// Titre principal de la page
echo "<h2>Simulation IoT GENESIS</h2>";

// Ouverture de la carte blanche
echo "<div class='card'>";

// Section 1 : Liste des valeurs actuelles des capteurs
echo "<h3>Donnees capteurs</h3>";
echo "<ul>
<li><span>Heure</span><span>{$heure}h</span></li>
<li><span>Lumiere</span><span>{$lumiere} lux</span></li>
<li><span>Temperature</span><span>{$temperature} °C</span></li>
<li><span>Humidite</span><span>{$humidity} %</span></li>
<li><span>CO2</span><span>{$co2} ppm</span></li>
<li><span>Niveau d'eau</span><span>{$niveau_eau} %</span></li>
<li><span>Arrosage</span><span class='badge badge-green'>" . ($arrosage ? "ACTIF" : "INACTIF") . "</span></li>
</ul>";

// Section 2 : Tableau recapitulatif des ajustements par capteur
// Affiche pour chaque capteur : sa valeur, la base entiere utilisee, et l'ajustement calcule
echo "<h3>Ajustements par capteur</h3>";
echo "<table>
<tr><th>Capteur</th><th>Valeur</th><th>Base entiere</th><th>Ajustement</th></tr>
<tr><td>Temperature</td><td>{$temperature} °C</td><td>" . intval($temperature) . "</td><td><span class='badge badge-green'>+{$ajust_temperature}</span></td></tr>
<tr><td>Humidite</td><td>{$humidity} %</td><td>" . intval($humidity) . "</td><td><span class='badge badge-green'>+{$ajust_humidite}</span></td></tr>
<tr><td>Lumiere</td><td>{$lumiere} lux</td><td>" . intval($lumiere) . "</td><td><span class='badge badge-green'>+{$ajust_lumiere}</span></td></tr>
<tr><td>Niveau d'eau</td><td>{$niveau_eau} %</td><td>" . intval($niveau_eau) . "</td><td><span class='badge badge-green'>+{$ajust_eau}</span></td></tr>
<tr><td>Arrosage</td><td>" . ($arrosage ? "ACTIF (1)" : "INACTIF (0)") . "</td><td>{$arrosage}</td><td><span class='badge badge-green'>+{$ajust_arrosage}</span></td></tr>
<tr><td>CO2</td><td>{$co2} ppm</td><td>" . intval($co2) . "</td><td><span class='badge badge-green'>+{$ajust_co2}</span></td></tr>
</table>";

// Fermeture de la carte blanche
echo "</div>";

// Fermeture de la requete preparee et de la connexion a la base de donnees
$stmt->close();
$conn->close();
?>

</div> <!-- Fermeture du conteneur principal -->
</body>
</html>
