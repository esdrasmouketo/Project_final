<?php
session_start();

// =====================
// Connexion à la base
// =====================
$host = 'localhost';
$db   = 'ardbd';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// =====================
// Récupération des paramètres
// =====================
$stmt = $conn->query("SELECT * FROM table_parametres LIMIT 1");
$parametres = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];

// =====================
// Historique des modifications
// =====================
$historyStmt = $conn->query("SELECT * FROM historique_parametres ORDER BY date_modif DESC LIMIT 10");
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Paramétrage - Genesis</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<style>
body { font-family:'Helvetica Neue', Arial, sans-serif; margin:0; padding:0; background:#f8f9fa; overflow-x:hidden; }
.navbar-top { position:fixed; top:0; left:0; width:100%; height:60px; background:#fff; border-bottom:1px solid #ddd; box-shadow:0 2px 4px rgba(0,0,0,0.05); z-index:2000; display:flex; align-items:center; justify-content:space-between; padding:0 20px; }
.navbar-top .brand { font-weight:bold; color:#28a745; font-size:20px; }
.navbar-top .buttons a, .navbar-top .buttons button { margin-left:10px; }
.navbar-toggle { display:none; cursor:pointer; }
.navbar-toggle .icon-bar { width:22px; height:2px; background:#28a745; margin:4px 0; transition:0.4s; }

.sidebar { position:fixed; top:60px; left:0; height:100%; width:220px; background:#fff; border-right:1px solid #ddd; box-shadow:2px 0 5px rgba(0,0,0,0.05); padding-top:20px; z-index:1000; transition:transform 0.3s ease; }
.sidebar h2 { text-align:center; font-weight:bold; color:#28a745; margin-bottom:30px; font-size:22px; }
.sidebar ul { list-style:none; padding:0; }
.sidebar ul li { padding:12px 20px; border-bottom:1px solid #eee; }
.sidebar ul li a { color:#333; text-decoration:none; display:flex; align-items:center; font-size:15px; }
.sidebar ul li.active a { background:#28a745; color:#fff; border-radius:5px; }

.main-content { margin-left:240px; padding:80px 30px 30px 30px; transition:margin-left 0.3s ease; }
.tab-content { margin-top:20px; }

.alert { margin-top:10px; }

@media (max-width:768px){
    .navbar-toggle { display:block; }
    .sidebar { transform:translateX(-100%); width:200px; position:fixed; height:100%; top:60px; }
    .sidebar.show { transform:translateX(0); }
    .main-content { margin-left:0; padding:100px 15px 15px 15px; }
}
</style>
</head>
<body>

<?php include __DIR__ . '/menu.php'; ?>

<div class="main-content">
<h2 class="text-success"><i class="fa fa-cog"></i> Paramétrage du système</h2>
<hr>

<!-- Affichage messages -->
<?php if(!empty($_SESSION['success'])): ?>
<div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if(!empty($_SESSION['error'])): ?>
<div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>

<!-- ===== NAV TABS ===== -->
<ul class="nav nav-tabs">
  <li class="active"><a href="#seuils" data-toggle="tab">Seuils</a></li>
  <li><a href="#equipements" data-toggle="tab">Arrosage / Équipements</a></li>
  <li><a href="#notifications" data-toggle="tab">Notifications</a></li>
  <li><a href="#actionneur" data-toggle="tab">Contrôle manuel</a></li>
  <li><a href="#horaire" data-toggle="tab">Programmation horaire</a></li>
  <li><a href="#historique" data-toggle="tab">Historique</a></li>
</ul>

<div class="tab-content">
<!-- SEUILS -->
<div class="tab-pane fade in active" id="seuils">
<form method="post" action="parametrage.php" class="form-horizontal">
    <div class="form-group">
        <label class="col-sm-2 control-label">Température max (°C)</label>
        <div class="col-sm-4"><input type="number" name="temp_max" class="form-control" value="<?= $parametres['temp_max'] ?? 30 ?>"></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Humidité min (%)</label>
        <div class="col-sm-4"><input type="number" name="humidity_min" class="form-control" value="<?= $parametres['humidity_min'] ?? 30 ?>"></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Niveau d'eau min (L)</label>
        <div class="col-sm-4"><input type="number" name="eau_min" class="form-control" value="<?= $parametres['eau_min'] ?? 20 ?>"></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">CO₂ max (ppm)</label>
        <div class="col-sm-4"><input type="number" name="co2_max" class="form-control" value="<?= $parametres['co2_max'] ?? 800 ?>"></div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-4"><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Enregistrer</button></div>
    </div>
</form>
</div>

<!-- ARROSAGE / EQUIPEMENTS -->
<div class="tab-pane fade" id="equipements">
<form method="post" action="parametrage.php" class="form-horizontal">
    <div class="form-group">
        <label class="col-sm-2 control-label">Pompe</label>
        <div class="col-sm-4">
            <select name="pompe" class="form-control">
                <option value="auto" <?= ($parametres['pompe']??'auto')=='auto'?'selected':'' ?>>Automatique</option>
                <option value="manuel" <?= ($parametres['pompe']??'auto')=='manuel'?'selected':'' ?>>Manuel</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Électrovanne</label>
        <div class="col-sm-4">
            <select name="electrovanne" class="form-control">
                <option value="auto" <?= ($parametres['electrovanne']??'auto')=='auto'?'selected':'' ?>>Automatique</option>
                <option value="manuel" <?= ($parametres['electrovanne']??'auto')=='manuel'?'selected':'' ?>>Manuel</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-4"><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Enregistrer</button></div>
    </div>
</form>
</div>

<!-- NOTIFICATIONS -->
<div class="tab-pane fade" id="notifications">
<form method="post" action="parametrage.php" class="form-horizontal">
    <div class="form-group">
        <label class="col-sm-2 control-label">Alertes Email</label>
        <div class="col-sm-4"><input type="checkbox" name="alert_email" <?= !empty($parametres['alert_email'])?'checked':'' ?>></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Alertes SMS</label>
        <div class="col-sm-4"><input type="checkbox" name="alert_sms" <?= !empty($parametres['alert_sms'])?'checked':'' ?>></div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-4"><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Enregistrer</button></div>
    </div>
</form>
</div>

<!-- CONTRÔLE MANUEL -->
<div class="tab-pane fade" id="actionneur">
<h4>Commandes manuelles</h4>
<button class="btn btn-primary" onclick="sendAction('pompe_on')">Pompe ON</button>
<button class="btn btn-danger" onclick="sendAction('pompe_off')">Pompe OFF</button>
<button class="btn btn-primary" onclick="sendAction('electrovanne_open')">Électrovanne ON</button>
<button class="btn btn-danger" onclick="sendAction('electrovanne_close')">Électrovanne OFF</button>
<button class="btn btn-warning" onclick="sendAction('light_on')">Lumière ON</button>
<button class="btn btn-default" onclick="sendAction('light_off')">Lumière OFF</button>
</div>

<!-- PROGRAMMATION HORAIRE -->
<div class="tab-pane fade" id="horaire">
<h4>Définir heures LED / Arrosage</h4>
<form method="post" action="parametrage.php" class="form-inline">
    <label>Heure début:</label>
    <input type="time" name="heure_debut" class="form-control" value="<?= $parametres['heure_debut'] ?? '06:00' ?>">
    <label>Heure fin:</label>
    <input type="time" name="heure_fin" class="form-control" value="<?= $parametres['heure_fin'] ?? '18:00' ?>">
    <button type="submit" class="btn btn-success">Enregistrer</button>
</form>
</div>

<!-- HISTORIQUE -->
<div class="tab-pane fade" id="historique">
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Date</th>
            <th>Paramètre modifié</th>
            <th>Ancienne valeur</th>
            <th>Nouvelle valeur</th>
            <th>Utilisateur</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($history as $h): ?>
        <tr>
            <td><?= $h['date_modif'] ?></td>
            <td><?= $h['param'] ?></td>
            <td><?= $h['ancienne_valeur'] ?></td>
            <td><?= $h['nouvelle_valeur'] ?></td>
            <td><?= $h['utilisateur'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

</div>

<!-- ===== JS ===== -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
<script>
// Toggle sidebar mobile
const toggleBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('show'); });

// Commandes manuelles actionneur
function sendAction(action){
    $.post('actionneur.php', {action: action}, function(data){
        alert(data.message);
    }, 'json');
}
</script>

</body>
</html>
