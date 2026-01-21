<?php
/**
 * Page de paramétrage sécurisée - GENESIS
 */
require_once __DIR__ . '/auth_check.php';

$conn = getDBConnection();

// Traitement du formulaire POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $_SESSION['error'] = "Erreur de sécurité. Veuillez réessayer.";
        header('Location: parametrage.php');
        exit();
    }

    try {
        // Déterminer quel formulaire a été soumis et traiter les données
        $updates = [];

        // Récupérer les paramètres actuels pour l'historique
        $currentStmt = $conn->query("SELECT * FROM table_parametres LIMIT 1");
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC) ?? [];

        // Seuils
        if (isset($_POST['temp_max'])) {
            $temp_max = filter_var($_POST['temp_max'], FILTER_VALIDATE_FLOAT);
            $humidity_min = filter_var($_POST['humidity_min'], FILTER_VALIDATE_FLOAT);
            $eau_min = filter_var($_POST['eau_min'], FILTER_VALIDATE_FLOAT);
            $co2_max = filter_var($_POST['co2_max'], FILTER_VALIDATE_FLOAT);

            if ($temp_max !== false) $updates['temp_max'] = $temp_max;
            if ($humidity_min !== false) $updates['humidity_min'] = $humidity_min;
            if ($eau_min !== false) $updates['eau_min'] = $eau_min;
            if ($co2_max !== false) $updates['co2_max'] = $co2_max;
        }

        // Équipements
        if (isset($_POST['pompe'])) {
            $pompe = in_array($_POST['pompe'], ['auto', 'manuel']) ? $_POST['pompe'] : 'auto';
            $electrovanne = in_array($_POST['electrovanne'], ['auto', 'manuel']) ? $_POST['electrovanne'] : 'auto';
            $updates['pompe'] = $pompe;
            $updates['electrovanne'] = $electrovanne;
        }

        // Notifications
        if (isset($_POST['notifications_form']) && $_POST['notifications_form'] === '1') {
            $updates['alert_email'] = isset($_POST['alert_email']) ? 1 : 0;
            $updates['alert_sms'] = isset($_POST['alert_sms']) ? 1 : 0;
        }

        // Horaires
        if (isset($_POST['heure_debut'])) {
            $heure_debut = preg_match('/^\d{2}:\d{2}$/', $_POST['heure_debut']) ? $_POST['heure_debut'] : '06:00';
            $heure_fin = preg_match('/^\d{2}:\d{2}$/', $_POST['heure_fin']) ? $_POST['heure_fin'] : '18:00';
            $updates['heure_debut'] = $heure_debut;
            $updates['heure_fin'] = $heure_fin;
        }

        if (!empty($updates)) {
            // Construire la requête UPDATE
            $setParts = [];
            $values = [];
            foreach ($updates as $key => $value) {
                $setParts[] = "$key = ?";
                $values[] = $value;

                // Enregistrer dans l'historique
                $oldValue = $current[$key] ?? 'N/A';
                if ($oldValue != $value) {
                    $histStmt = $conn->prepare("INSERT INTO historique_parametres (param, ancienne_valeur, nouvelle_valeur, utilisateur) VALUES (?, ?, ?, ?)");
                    $histStmt->execute([$key, (string)$oldValue, (string)$value, $_SESSION['username']]);
                }
            }

            $sql = "UPDATE table_parametres SET " . implode(', ', $setParts);
            $stmt = $conn->prepare($sql);
            $stmt->execute($values);

            $_SESSION['success'] = "Paramètres enregistrés avec succès.";
        }

        // Régénérer le token CSRF
        regenerateCSRFToken();

    } catch (PDOException $e) {
        error_log("Erreur parametrage: " . $e->getMessage());
        $_SESSION['error'] = "Erreur lors de l'enregistrement.";
    }

    header('Location: parametrage.php');
    exit();
}

// Récupération des paramètres
$stmt = $conn->query("SELECT * FROM table_parametres LIMIT 1");
$parametres = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];

// Historique des modifications
$historyStmt = $conn->query("SELECT * FROM historique_parametres ORDER BY date_modif DESC LIMIT 10");
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<div class="alert alert-success"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if(!empty($_SESSION['error'])): ?>
<div class="alert alert-danger"><?php echo e($_SESSION['error']); unset($_SESSION['error']); ?></div>
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
    <?php echo csrfField(); ?>
    <div class="form-group">
        <label class="col-sm-2 control-label">Température max (°C)</label>
        <div class="col-sm-4"><input type="number" name="temp_max" class="form-control" value="<?php echo e($parametres['temp_max'] ?? 30); ?>" min="0" max="100" step="0.1"></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Humidité min (%)</label>
        <div class="col-sm-4"><input type="number" name="humidity_min" class="form-control" value="<?php echo e($parametres['humidity_min'] ?? 30); ?>" min="0" max="100" step="0.1"></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Niveau d'eau min (L)</label>
        <div class="col-sm-4"><input type="number" name="eau_min" class="form-control" value="<?php echo e($parametres['eau_min'] ?? 20); ?>" min="0" max="1000" step="0.1"></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">CO₂ max (ppm)</label>
        <div class="col-sm-4"><input type="number" name="co2_max" class="form-control" value="<?php echo e($parametres['co2_max'] ?? 800); ?>" min="0" max="5000" step="1"></div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-4"><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Enregistrer</button></div>
    </div>
</form>
</div>

<!-- ARROSAGE / EQUIPEMENTS -->
<div class="tab-pane fade" id="equipements">
<form method="post" action="parametrage.php" class="form-horizontal">
    <?php echo csrfField(); ?>
    <div class="form-group">
        <label class="col-sm-2 control-label">Pompe</label>
        <div class="col-sm-4">
            <select name="pompe" class="form-control">
                <option value="auto" <?php echo ($parametres['pompe']??'auto')=='auto'?'selected':''; ?>>Automatique</option>
                <option value="manuel" <?php echo ($parametres['pompe']??'auto')=='manuel'?'selected':''; ?>>Manuel</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Électrovanne</label>
        <div class="col-sm-4">
            <select name="electrovanne" class="form-control">
                <option value="auto" <?php echo ($parametres['electrovanne']??'auto')=='auto'?'selected':''; ?>>Automatique</option>
                <option value="manuel" <?php echo ($parametres['electrovanne']??'auto')=='manuel'?'selected':''; ?>>Manuel</option>
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
    <?php echo csrfField(); ?>
    <input type="hidden" name="notifications_form" value="1">
    <div class="form-group">
        <label class="col-sm-2 control-label">Alertes Email</label>
        <div class="col-sm-4"><input type="checkbox" name="alert_email" <?php echo !empty($parametres['alert_email'])?'checked':''; ?>></div>
    </div>
    <div class="form-group">
        <label class="col-sm-2 control-label">Alertes SMS</label>
        <div class="col-sm-4"><input type="checkbox" name="alert_sms" <?php echo !empty($parametres['alert_sms'])?'checked':''; ?>></div>
    </div>
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-4"><button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Enregistrer</button></div>
    </div>
</form>
</div>

<!-- CONTRÔLE MANUEL -->
<div class="tab-pane fade" id="actionneur">
<h4>Commandes manuelles</h4>
<p class="text-muted">Contrôlez les équipements de la serre en temps réel.</p>
<div class="btn-group-vertical" style="margin-bottom: 10px;">
    <button class="btn btn-primary" onclick="sendAction('pompe_on')"><i class="fa fa-tint"></i> Pompe ON</button>
    <button class="btn btn-danger" onclick="sendAction('pompe_off')"><i class="fa fa-tint"></i> Pompe OFF</button>
</div>
<div class="btn-group-vertical" style="margin-bottom: 10px;">
    <button class="btn btn-primary" onclick="sendAction('electrovanne_open')"><i class="fa fa-plug"></i> Électrovanne ON</button>
    <button class="btn btn-danger" onclick="sendAction('electrovanne_close')"><i class="fa fa-plug"></i> Électrovanne OFF</button>
</div>
<div class="btn-group-vertical">
    <button class="btn btn-warning" onclick="sendAction('light_on')"><i class="fa fa-lightbulb-o"></i> Lumière ON</button>
    <button class="btn btn-default" onclick="sendAction('light_off')"><i class="fa fa-lightbulb-o"></i> Lumière OFF</button>
</div>
<div id="actionResult" class="alert" style="display:none; margin-top:15px;"></div>
</div>

<!-- PROGRAMMATION HORAIRE -->
<div class="tab-pane fade" id="horaire">
<h4>Définir heures LED / Arrosage</h4>
<form method="post" action="parametrage.php" class="form-inline">
    <?php echo csrfField(); ?>
    <div class="form-group">
        <label>Heure début:</label>
        <input type="time" name="heure_debut" class="form-control" value="<?php echo e($parametres['heure_debut'] ?? '06:00'); ?>">
    </div>
    <div class="form-group">
        <label>Heure fin:</label>
        <input type="time" name="heure_fin" class="form-control" value="<?php echo e($parametres['heure_fin'] ?? '18:00'); ?>">
    </div>
    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Enregistrer</button>
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
            <td><?php echo e($h['date_modif']); ?></td>
            <td><?php echo e($h['param']); ?></td>
            <td><?php echo e($h['ancienne_valeur']); ?></td>
            <td><?php echo e($h['nouvelle_valeur']); ?></td>
            <td><?php echo e($h['utilisateur']); ?></td>
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
if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('show'); });
}

// Commandes manuelles actionneur avec token CSRF
function sendAction(action){
    const csrfToken = '<?php echo generateCSRFToken(); ?>';
    const resultDiv = $('#actionResult');

    $.ajax({
        url: 'actionneur.php',
        type: 'POST',
        data: {
            action: action,
            <?php echo CSRF_TOKEN_NAME; ?>: csrfToken
        },
        dataType: 'json',
        success: function(data) {
            resultDiv.removeClass('alert-success alert-danger');
            if (data.success) {
                resultDiv.addClass('alert-success').text(data.message).show();
            } else {
                resultDiv.addClass('alert-danger').text(data.message || 'Erreur').show();
            }
            setTimeout(() => resultDiv.fadeOut(), 3000);
        },
        error: function() {
            resultDiv.removeClass('alert-success').addClass('alert-danger')
                     .text('Erreur de communication avec le serveur').show();
            setTimeout(() => resultDiv.fadeOut(), 3000);
        }
    });
}
</script>

</body>
</html>
