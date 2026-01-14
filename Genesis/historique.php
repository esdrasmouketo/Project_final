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
// Filtre par date et paramètre
// =====================
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$param_filter = $_GET['param_filter'] ?? 'all';

$sql = "SELECT * FROM table_capteurs";
$where = [];
$params = [];

if($date_debut && $date_fin){
    $where[] = "date_heure BETWEEN ? AND ?";
    $params[] = $date_debut.' 00:00:00';
    $params[] = $date_fin.' 23:59:59';
}

if($param_filter && $param_filter != 'all'){
    switch($param_filter){
        case 'temperature': $where[] = "(temperature>30 OR temperature<15)"; break;
        case 'eau': $where[] = "niveau_eau<20"; break;
        case 'co2': $where[] = "co2_level>800"; break;
        case 'arrosage': $where[] = "arrosage=1"; break;
    }
}

if($where) $sql .= " WHERE ".implode(' AND ', $where);
$sql .= " ORDER BY date_heure DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dates = $temperature = $humidity = $niveau_eau = $niveau_lumiere = $co2 = $arrosage = [];
foreach($results as $row){
    $dates[] = $row['date_heure'];
    $temperature[] = $row['temperature'];
    $humidity[] = $row['humidity'];
    $niveau_eau[] = $row['niveau_eau'];
    $niveau_lumiere[] = $row['niveau_lumiere'];
    $co2[] = $row['co2_level'];
    $arrosage[] = $row['arrosage'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Historique des mesures - Genesis</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ===== GLOBAL ===== */
body { font-family: 'Helvetica Neue', Arial, sans-serif; margin:0; padding:0; background:#f8f9fa; overflow-x:hidden; }

/* ===== NAVBAR TOP ===== */
.navbar-top {
    position: fixed; top:0; left:0; width:100%; height:60px;
    background:#fff; border-bottom:1px solid #ddd; box-shadow:0 2px 4px rgba(0,0,0,0.05);
    z-index:2000; display:flex; align-items:center; justify-content:space-between; padding:0 20px;
}
.navbar-top .brand { font-weight:bold; color:#28a745; font-size:20px; }
.navbar-top .buttons a, .navbar-top .buttons button { margin-left:10px; }
.navbar-toggle { display:none; cursor:pointer; }
.navbar-toggle .icon-bar { width:22px; height:2px; background:#28a745; margin:4px 0; transition:0.4s; }

/* ===== SIDEBAR ===== */
.sidebar {
    position:fixed; top:60px; left:0; height:100%; width:220px;
    background:#fff; border-right:1px solid #ddd; box-shadow:2px 0 5px rgba(0,0,0,0.05);
    padding-top:20px; z-index:1000; transition:transform 0.3s ease;
}
.sidebar h2 { text-align:center; font-weight:bold; color:#28a745; margin-bottom:30px; font-size:22px; }
.sidebar ul { list-style:none; padding:0; }
.sidebar ul li { padding:12px 20px; border-bottom:1px solid #eee; }
.sidebar ul li a { color:#333; text-decoration:none; display:flex; align-items:center; font-size:15px; }
.sidebar ul li.active a { background:#28a745; color:#fff; border-radius:5px; }

/* ===== CONTENU ===== */
.main-content { margin-left:240px; padding:80px 30px 30px 30px; transition: margin-left 0.3s ease; }
.alert-high { background-color: #f8d7da; } /* rouge */
.alert-low { background-color: #fff3cd; }  /* jaune */

/* ===== RESPONSIVE ===== */
@media (max-width:768px){
    .navbar-toggle { display:block; }
    .sidebar { transform:translateX(-100%); width:200px; position:fixed; height:100%; top:60px; }
    .sidebar.show { transform:translateX(0); }
    .main-content { margin-left:0; padding:100px 15px 15px 15px; }
}
</style>
</head>
<body>

<!-- ===== NAVBAR TOP ===== -->
<div class="navbar-top">
    <div class="brand"><i class="fa fa-leaf"></i> Genesis</div>
    <div class="navbar-toggle" id="toggleSidebar">
        <div class="icon-bar"></div>
        <div class="icon-bar"></div>
        <div class="icon-bar"></div>
    </div>
    <div class="buttons">
        <a href="logout.php" class="btn btn-danger"><i class="fa fa-sign-out"></i> Déconnexion</a>
    </div>
</div>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar" id="sidebar">
    <h2>Menu</h2>
    <ul>
        <li><a href="index.php"><i class="fa fa-home"></i> Accueil</a></li>
        <li><a href="parametrage.php"><i class="fa fa-cog"></i> Paramétrage</a></li>
        <li class="active"><a href="historique.php"><i class="fa fa-history"></i> Historique</a></li>
        <li><a href="ia.php"><i class="fa fa-android"></i> Assistant IA</a></li>
    </ul>
</div>

<!-- ===== CONTENU PRINCIPAL ===== -->
<div class="main-content">
<h2 class="text-success"><i class="fa fa-history"></i> Historique des mesures</h2>
<hr>

<!-- Filtres -->
<form method="get" class="form-inline">
    <label>Date début:</label>
    <input type="date" name="date_debut" value="<?= htmlspecialchars($date_debut) ?>" class="form-control">
    <label>Date fin:</label>
    <input type="date" name="date_fin" value="<?= htmlspecialchars($date_fin) ?>" class="form-control">
    <label>Filtrer paramètre:</label>
    <select name="param_filter" class="form-control">
        <option value="Tout" <?= $param_filter=='all'?'selected':'' ?>>Tous</option>
        <option value="temperature" <?= $param_filter=='temperature'?'selected':'' ?>>Température critique</option>
        <option value="eau" <?= $param_filter=='eau'?'selected':'' ?>>Eau faible</option>
        <option value="co2" <?= $param_filter=='co2'?'selected':'' ?>>CO₂ élevé</option>
        <option value="arrosage" <?= $param_filter=='arrosage'?'selected':'' ?>>Arrosage actif</option>
    </select>
    <button type="submit" class="btn btn-success">Filtrer</button>
</form>
<br>

<!-- Tableau -->
<table id="tableHistorique" class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>Date & Heure</th>
            <th>Température (°C)</th>
            <th>Humidité (%)</th>
            <th>Niveau d'eau</th>
            <th>Lumière</th>
            <th>CO₂</th>
            <th>Arrosage</th>
            <th>Alertes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($results as $row): 
            $alertes = [];
            if($row['temperature']>30) $alertes[]="Trop chaud 🌡️";
            if($row['temperature']<15) $alertes[]="Trop froid ❄️";
            if($row['niveau_eau']<20) $alertes[]="Eau faible 💧";
            if($row['co2_level']>800) $alertes[]="CO₂ élevé 🟢";
        ?>
        <tr class="<?= ($row['temperature']>30||$row['temperature']<15)?'alert-high':(($row['niveau_eau']<20)?'alert-low':'') ?>">
            <td><?= $row['date_heure'] ?></td>
            <td><?= $row['temperature'] ?></td>
            <td><?= $row['humidity'] ?></td>
            <td><?= $row['niveau_eau'] ?></td>
            <td><?= $row['niveau_lumiere'] ?></td>
            <td><?= $row['co2_level'] ?></td>
            <td><?= $row['arrosage']? 'Oui':'Non' ?></td>
            <td><?= implode(', ',$alertes) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<hr>
<h3 class="text-success">Graphiques</h3>
<canvas id="chartMulti" height="100"></canvas>
</div>

<script>
// Toggle sidebar mobile
const toggleBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('show'); });

// DataTable avec export
$(document).ready(function() {
    $('#tableHistorique').DataTable({
        dom: 'Bfrtip',
        buttons: ['excel', 'print'],
        order: [[0,'desc']]
    });
});

// Graphique Chart.js
const labels = <?= json_encode($dates) ?>;
const data = {
    labels: labels,
    datasets: [
        { label: 'Température (°C)', data: <?= json_encode($temperature) ?>, borderColor:'red', fill:false, tension:0.3, pointRadius:2 },
        { label: 'Humidité (%)', data: <?= json_encode($humidity) ?>, borderColor:'blue', fill:false, tension:0.3, pointRadius:2 },
        { label: 'Niveau d\'eau (L)', data: <?= json_encode($niveau_eau) ?>, borderColor:'aqua', fill:false, tension:0.3, pointRadius:2 },
        { label: 'Lumière (lux)', data: <?= json_encode($niveau_lumiere) ?>, borderColor:'orange', fill:false, tension:0.3, pointRadius:2 },
        { label: 'CO₂ (ppm)', data: <?= json_encode($co2) ?>, borderColor:'green', fill:false, tension:0.3, pointRadius:2 }
    ]
};
const config = { type:'line', data:data, options:{ responsive:true, plugins:{ legend:{ position:'top' } } } };
new Chart(document.getElementById('chartMulti'), config);
</script>

</body>
</html>
