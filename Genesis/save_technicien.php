<?php
session_start();

$host='localhost'; $db='ardbd'; $user='root'; $pass=''; $charset='utf8mb4';
$dsn="mysql:host=$host;dbname=$db;charset=$charset";

try { 
    $conn = new PDO($dsn,$user,$pass); 
    $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); 
} catch(PDOException $e){ die("Erreur: ".$e->getMessage()); }

$prenom = $_POST['prenom'] ?? '';
$nom = $_POST['nom'] ?? '';
$role = $_POST['role'] ?? '';
$email = $_POST['email'] ?? '';
$telephone = $_POST['telephone'] ?? '';
$photo = '';

if(isset($_FILES['photo']) && $_FILES['photo']['error']==0){
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photo = 'uploads/photos/'.uniqid().'.'.$ext;
    if(!is_dir('uploads/photos')) mkdir('uploads/photos',0777,true);
    move_uploaded_file($_FILES['photo']['tmp_name'],$photo);
}

$stmt = $conn->prepare("INSERT INTO techniciens (prenom, nom, role, email, telephone, photo) 
                        VALUES (:prenom, :nom, :role, :email, :telephone, :photo)");
$stmt->execute([
    ':prenom'=>$prenom,
    ':nom'=>$nom,
    ':role'=>$role,
    ':email'=>$email,
    ':telephone'=>$telephone,
    ':photo'=>$photo
]);

header('Location: ia.php');
exit;
?>
