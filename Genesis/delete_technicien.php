<?php
session_start();

$host='localhost'; $db='ardbd'; $user='root'; $pass=''; $charset='utf8mb4';
$dsn="mysql:host=$host;dbname=$db;charset=$charset";

try { 
    $conn = new PDO($dsn,$user,$pass); 
    $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); 
} catch(PDOException $e){ die("Erreur: ".$e->getMessage()); }

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("DELETE FROM techniciens WHERE id=:id");
$stmt->execute([':id'=>$id]);

header('Location: ia.php');
exit;
?>
