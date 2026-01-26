<?php
/**
 * Page gestion techniciens et maintenance sécurisée - GENESIS
 */
require_once __DIR__ . '/auth_check.php';

$conn = getDBConnection();

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
.card img{width:80px;height:80px;border-radius:50%;margin-right:15px;display:inline-block;vertical-align:middle;object-fit:cover;}
.navbar-toggle{display:none;cursor:pointer;}
.navbar-toggle .icon-bar{width:22px;height:2px;background:#28a745;margin:4px 0;transition:0.4s;}

@media (max-width:768px){
    .navbar-toggle{display:block;}
    .sidebar{transform:translateX(-100%);width:200px;}
    .sidebar.show{transform:translateX(0);}
    .main-content{margin-left:0;padding:80px 15px 15px 15px;}
}
</style>
</head>
<body>

<?php include __DIR__ . '/menu.php'; ?>

<div class="main-content">
<h2 class="text-success"><i class="fa fa-users"></i> Gestion des techniciens et maintenance</h2>

<?php if(!empty($_SESSION['success'])): ?>
<div class="alert alert-success"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if(!empty($_SESSION['error'])): ?>
<div class="alert alert-danger"><?php echo e($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

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
<img src="<?php echo e($t['photo'] ?? 'default.png'); ?>" alt="Photo" onerror="this.src='default.png'">
<strong><?php echo e($t['prenom'].' '.$t['nom']); ?></strong><br>
<small><?php echo e($t['role']); ?></small><br>
<small>Email: <?php echo e($t['email']); ?></small><br>
<small>Téléphone: <?php echo e($t['telephone']); ?></small><br><br>
<form method="post" action="delete_technicien.php" style="display:inline;" onsubmit="return confirm('Supprimer ce technicien ?');">
    <?php echo csrfField(); ?>
    <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
    <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Supprimer</button>
</form>
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
<td><?php echo e($m['date_maintenance']); ?></td>
<td><?php echo e($m['prenom'].' '.$m['nom']); ?></td>
<td><?php echo e($m['description']); ?></td>
<td>
<?php if(!empty($m['fichier'])): ?>
<a href="<?php echo e($m['fichier']); ?>" target="_blank" rel="noopener noreferrer"><i class="fa fa-file-pdf-o"></i> Voir</a>
<?php else: ?>
-
<?php endif; ?>
</td>
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
<?php echo csrfField(); ?>
<div class="modal-header"><h4>Ajouter Technicien</h4></div>
<div class="modal-body">
<div class="form-group"><label>Prénom:</label><input type="text" name="prenom" class="form-control" required maxlength="100"></div>
<div class="form-group"><label>Nom:</label><input type="text" name="nom" class="form-control" required maxlength="100"></div>
<div class="form-group"><label>Rôle:</label><input type="text" name="role" class="form-control" required maxlength="100"></div>
<div class="form-group"><label>Email:</label><input type="email" name="email" class="form-control" maxlength="255"></div>
<div class="form-group"><label>Téléphone:</label><input type="tel" name="telephone" class="form-control" maxlength="20" pattern="[0-9+\-\s]+"></div>
<div class="form-group">
    <label>Photo (JPG, PNG, GIF - max 5MB):</label>
    <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.gif">
</div>
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
<?php echo csrfField(); ?>
<div class="modal-header"><h4>Ajouter Fiche de maintenance</h4></div>
<div class="modal-body">
<div class="form-group"><label>Technicien:</label>
<select name="technicien_id" class="form-control" required>
<option value="">-- Sélectionner --</option>
<?php foreach($techniciens as $t): ?>
<option value="<?php echo (int)$t['id']; ?>"><?php echo e($t['prenom'].' '.$t['nom']); ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group"><label>Date:</label><input type="datetime-local" name="date_maintenance" class="form-control" required></div>
<div class="form-group"><label>Description:</label><textarea name="description" class="form-control" maxlength="1000"></textarea></div>
<div class="form-group"><label>Fichier PDF (max 5MB):</label><input type="file" name="fichier" class="form-control" accept=".pdf"></div>
</div>
<div class="modal-footer"><button type="submit" class="btn btn-success">Ajouter</button><button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button></div>
</form>
</div>
</div>
</div>
<!-- Formulaire de réinitialisation de mot de passe -->
<div class="panel panel-warning">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fa fa-key"></i> Réinitialiser mot de passe
        </h3>
    </div>
    <div class="panel-body">
        <form method="post" action="reset_password.php">
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label for="reset_email">Email de l'utilisateur :</label>
                <input type="email" class="form-control" id="reset_email" 
                       name="email" required>
            </div>

            <div class="form-group">
                <label for="new_password">Nouveau mot de passe :</label>
                <input type="password" class="form-control" id="new_password" 
                       name="new_password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe :</label>
                <input type="password" class="form-control" id="confirm_password" 
                       name="confirm_password" required minlength="6">
            </div>

            <button type="submit" class="btn btn-warning">
                <i class="fa fa-refresh"></i> Réinitialiser
            </button>
        </form>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script>
// Toggle sidebar mobile
const toggleBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('show'); });
}
</script>
</body>
</html>
