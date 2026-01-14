<?php
session_start();

// Connexion à la base
$host='localhost'; $db='ardbd'; $user='root'; $pass=''; $charset='utf8mb4';
$dsn="mysql:host=$host;dbname=$db;charset=$charset";

try{
    $conn = new PDO($dsn,$user,$pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){ die("Erreur: ".$e->getMessage()); }

$heure_debut = $_POST['heure_debut'] ?? '06:00';
$heure_fin   = $_POST['heure_fin'] ?? '18:00';

// Récupérer ancienne valeur pour historique
$stmt = $conn->query("SELECT heure_debut, heure_fin FROM table_parametres WHERE id=1");
$ancien = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("UPDATE table_parametres SET heure_debut=?, heure_fin=? WHERE id=1");
$stmt->execute([$heure_debut, $heure_fin]);

// Historique
$user = $_SESSION['username'] ?? 'admin';
$histStmt = $conn->prepare("INSERT INTO historique_parametres (param, ancienne_valeur, nouvelle_valeur, utilisateur) VALUES (?, ?, ?, ?)");
$histStmt->execute(['Horaire LED/Arrosage', $ancien['heure_debut'].' - '.$ancien['heure_fin'], $heure_debut.' - '.$heure_fin, $user]);

$_SESSION['success'] = "Programmation horaire enregistrée";
header("Location: parametrage.php");
exit;
