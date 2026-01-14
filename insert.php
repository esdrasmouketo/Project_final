<?php
// Connexion à la base de données MySQL
$servername = "localhost"; // Remplacez par votre serveur de base de données
$username = "root"; // Remplacez par votre nom d'utilisateur
$password = ""; // Remplacez par votre mot de passe
$dbname = "ardbd"; // Nom de la base de données

// Créer une connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Vérifier si tous les paramètres requis sont présents dans la requête GET
if (isset($_GET['niveau_eau'], $_GET['niveau_lumiere'], $_GET['arrosage'], 
    $_GET['co2_level'], $_GET['temperature'], $_GET['humidity'])) {

    // Récupérer et valider les données envoyées par la requête GET
    $niveau_eau = filter_var($_GET['niveau_eau'], FILTER_VALIDATE_FLOAT);
    $niveau_lumiere = filter_var($_GET['niveau_lumiere'], FILTER_VALIDATE_FLOAT);
    $arrosage = filter_var($_GET['arrosage'], FILTER_VALIDATE_INT);
    $co2_level = filter_var($_GET['co2_level'], FILTER_VALIDATE_FLOAT);
    $temperature = filter_var($_GET['temperature'], FILTER_VALIDATE_FLOAT);
    $humidity = filter_var($_GET['humidity'], FILTER_VALIDATE_FLOAT);

    // Vérification si la validation a échoué
    if ($niveau_eau === false || $niveau_lumiere === false || $arrosage === false || 
        $co2_level === false || $temperature === false || $humidity === false) {
        echo "Erreur: Une ou plusieurs valeurs sont invalides.";
    } else {
        // Préparer une requête SQL pour insérer les données dans la table `table_capteurs`
        $stmt = $conn->prepare("INSERT INTO table_capteurs (niveau_eau, niveau_lumiere, arrosage, co2_level, temperature, humidity)
                                VALUES (?, ?, ?, ?, ?, ?)");
        
        // Lier les paramètres
        $stmt->bind_param("dddddd", $niveau_eau, $niveau_lumiere, $arrosage, $co2_level, $temperature, $humidity);
        
        // Exécuter la requête
        if ($stmt->execute()) {
            echo "Données insérées avec succès";
        } else {
            echo "Erreur d'insertion: " . $stmt->error;
        }
        
        // Fermer la déclaration préparée
        $stmt->close();
    }

} else {
    echo "Erreur: Les paramètres requis ne sont pas présents dans la requête.";
}

// Fermer la connexion à la base de données
$conn->close();
?>
