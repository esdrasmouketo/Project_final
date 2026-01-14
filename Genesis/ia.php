<?php
session_start();

// Connexion DB
$host='localhost'; $db='ardbd'; $user='root'; $pass=''; $charset='utf8mb4';
$dsn="mysql:host=$host;dbname=$db;charset=$charset";
try { 
    $conn = new PDO($dsn,$user,$pass); 
    $conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); 
} catch(PDOException $e){ 
    die("Erreur: ".$e->getMessage()); 
}

// Récupération des techniciens
$stmt = $conn->query("SELECT * FROM techniciens ORDER BY nom");
$techniciens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des fiches maintenance
$stmt2 = $conn->query("SELECT m.*, t.nom, t.prenom FROM maintenance_fiches m 
                       JOIN techniciens t ON m.technicien_id=t.id 
                       ORDER BY m.date_maintenance DESC");
$maintenances = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Techniciens & Maintenance - Genesis</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f8f9fa;margin:0;padding:0;overflow-x:hidden;}
.navbar-top{position:fixed;top:0;left:0;width:100%;height:60px;background:#fff;border-bottom:1px solid #ddd;box-shadow:0 2px 4px rgba(0,0,0,0.05);z-index:2000;display:flex;align-items:center;justify-content:space-between;padding:0 20px;}
.navbar-top .brand{font-weight:bold;color:#28a745;font-size:20px;}
.navbar-top .buttons a{margin-left:10px;}
.sidebar{position:fixed;top:60px;left:0;height:100%;width:220px;background:#fff;border-right:1px solid #ddd;padding-top:20px;z-index:1000;transition:transform 0.3s ease;}
.sidebar h2{text-align:center;font-weight:bold;color:#28a745;margin-bottom:30px;font-size:22px;}
.sidebar ul{list-style:none;padding:0;}
.sidebar ul li{padding:12px 20px;border-bottom:1px solid #eee;}
.sidebar ul li a{color:#333;text-decoration:none;display:flex;align-items:center;font-size:15px;}
.sidebar ul li.active a{background:#28a745;color:#fff;border-radius:5px;}
.main-content{margin-left:240px;padding:80px 30px 30px 30px;transition:margin-left 0.3s ease;}
.card{background:#fff;padding:15px;margin:10px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.1);}
.card img{width:80px;height:80px;border-radius:50%;margin-right:15px;display:inline-block;vertical-align:middle;}
</style>
</head>
<body>

<div class="navbar-top">
<div class="brand"><i class="fa fa-leaf"></i> Genesis</div>
<div class="buttons">
<a href="logout.php" class="btn btn-danger"><i class="fa fa-sign-out"></i> Déconnexion</a>
</div>
</div>

<div class="sidebar">
<h2>Menu</h2>
<ul>
<li><a href="index.php"><i class="fa fa-home"></i> Accueil</a></li>
<li class="active"><a href="ia.php"><i class="fa fa-android"></i> Techniciens</a></li>
<li><a href="parametrage.php"><i class="fa fa-cog"></i> Paramétrage</a></li>
<li><a href="historique.php"><i class="fa fa-history"></i> Historique</a></li>
</ul>
</div>

<div class="main-content">
<h2 class="text-success"><i class="fa fa-users"></i> Gestion des techniciens et maintenance</h2>

<!-- Onglets -->
<ul class="nav nav-tabs">
  <li class="active"><a href="#techniciens" data-toggle="tab">Techniciens</a></li>
  <li><a href="#fiche" data-toggle="tab">Fiches de maintenance</a></li>
</ul>

<div class="tab-content" style="margin-top:20px;">

<!-- Techniciens -->
<div class="tab-pane fade in active" id="techniciens">
<button class="btn btn-success" data-toggle="modal" data-target="#addTechModal"><i class="fa fa-plus"></i> Ajouter un technicien</button>
<div class="row" style="margin-top:20px;">
<?php foreach($techniciens as $t): ?>
<div class="col-sm-6 col-md-4">
<div class="card">
<img src="<?= $t['photo'] ?? 'default.png' ?>" alt="Photo">
<strong><?= $t['prenom'].' '.$t['nom'] ?></strong><br>
<small><?= $t['role'] ?></small><br>
<small>Email: <?= $t['email'] ?></small><br>
<small>Téléphone: <?= $t['telephone'] ?></small><br><br>
<button class="btn btn-primary btn-xs" onclick="editTech(<?= $t['id'] ?>)">Modifier</button>
<button class="btn btn-danger btn-xs" onclick="deleteTech(<?= $t['id'] ?>)">Supprimer</button>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- Fiches de maintenance -->
<div class="tab-pane fade" id="fiche">
<button class="btn btn-success" data-toggle="modal" data-target="#addFicheModal"><i class="fa fa-plus"></i> Ajouter une fiche</button>
<table class="table table-bordered table-striped" style="margin-top:20px;">
<thead>
<tr><th>Date</th><th>Technicien</th><th>Description</th><th>Fichier</th></tr>
</thead>
<tbody>
<?php foreach($maintenances as $m): ?>
<tr>
<td><?= $m['date_maintenance'] ?></td>
<td><?= $m['prenom'].' '.$m['nom'] ?></td>
<td><?= $m['description'] ?></td>
<td><a href="<?= $m['fichier'] ?>" target="_blank"><i class="fa fa-file-pdf-o"></i> Voir</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div>

<!-- Modal Ajouter Technicien -->
<div class="modal fade" id="addTechModal" tabindex="-1" role="dialog">
<div class="modal-dialog">
<div class="modal-content">
<form method="post" action="save_technicien.php" enctype="multipart/form-data">
<div class="modal-header"><h4>Ajouter Technicien</h4></div>
<div class="modal-body">
<div class="form-group"><label>Prénom:</label><input type="text" name="prenom" class="form-control" required></div>
<div class="form-group"><label>Nom:</label><input type="text" name="nom" class="form-control" required></div>
<div class="form-group"><label>Rôle:</label><input type="text" name="role" class="form-control" required></div>
<div class="form-group"><label>Email:</label><input type="email" name="email" class="form-control"></div>
<div class="form-group"><label>Téléphone:</label><input type="text" name="telephone" class="form-control"></div>
<div class="form-group"><label>Photo:</label><input type="file" name="photo" class="form-control"></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-success">Ajouter</button><button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button></div>
</form>
</div>
</div>
</div>

<!-- Modal Ajouter Fiche -->
<div class="modal fade" id="addFicheModal" tabindex="-1" role="dialog">
<div class="modal-dialog">
<div class="modal-content">
<form method="post" action="save_fiche.php" enctype="multipart/form-data">
<div class="modal-header"><h4>Ajouter Fiche de maintenance</h4></div>
<div class="modal-body">
<div class="form-group"><label>Technicien:</label>
<select name="technicien_id" class="form-control" required>
<?php foreach($techniciens as $t): ?>
<option value="<?= $t['id'] ?>"><?= $t['prenom'].' '.$t['nom'] ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group"><label>Date:</label><input type="datetime-local" name="date_maintenance" class="form-control" required></div>
<div class="form-group"><label>Description:</label><textarea name="description" class="form-control"></textarea></div>
<div class="form-group"><label>Fichier PDF:</label><input type="file" name="fichier" class="form-control" accept=".pdf"></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-success">Ajouter</button><button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button></div>
</form>
</div>
</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script>
function editTech(id){ alert('Fonction modifier technicien ID '+id); /* À compléter avec modal */ }
function deleteTech(id){ if(confirm('Supprimer ce technicien ?')){ window.location='delete_technicien.php?id='+id; } }
</script>
</body>
</html>
