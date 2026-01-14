<?php
session_start();

$host='localhost'; $db='ardbd'; $user='root'; $pass=''; $charset='utf8mb4';
$dsn="mysql:host=$host;dbname=$db;charset=$charset";

try { 
    $conn = new PDO($dsn,$user,$pass); 
    $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); 
} catch(PDOException $e){ die("Erreur: ".$e->getMessage()); }

$technicien_id = $_POST['technicien_id'] ?? '';
$date_maint = $_POST['date_maintenance'] ?? date('Y-m-d H:i:s');
$description = $_POST['description'] ?? '';
$fichier = '';

if(isset($_FILES['fichier']) && $_FILES['fichier']['error']==0){
    $ext = pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION);
    $fichier = 'uploads/fiches/'.uniqid().'.'.$ext;
    if(!is_dir('uploads/fiches')) mkdir('uploads/fiches',0777,true);
    move_uploaded_file($_FILES['fichier']['tmp_name'],$fichier);
}

$stmt = $conn->prepare("INSERT INTO maintenance_fiches (technicien_id, date_maintenance, description, fichier) 
                        VALUES (:tech, :date, :desc, :fichier)");
$stmt->execute([
    ':tech'=>$technicien_id,
    ':date'=>$date_maint,
    ':desc'=>$description,
    ':fichier'=>$fichier
]);

header('Location: ia.php');
exit;
?>
