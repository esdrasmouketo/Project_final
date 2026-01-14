<?php
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$database = "ardbd";
$conn = new mysqli($servername, $username, $password, $database);
// Vérifier la connexion
if ($conn->connect_error) {
die("Échec de la connexion : " . $conn->connect_error);
}
// Vérifier si les données sont bien reçues
if (isset($_GET['temperature']) && isset($_GET['humidite'])) {
$temperature = floatval($_GET['temperature']);
$humidite = floatval($_GET['humidite']);
// Préparer la requête SQL
$sql = "INSERT INTO table_capteurs (temperature, humidite) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("dd", $temperature, $humidite);
if ($stmt->execute()) {
echo "Données enregistrées avec succès.";
} else {
echo "Erreur : " . $stmt->error;
}
$stmt->close();
} else {
echo "Données manquantes.";
}
$conn->close();
?>