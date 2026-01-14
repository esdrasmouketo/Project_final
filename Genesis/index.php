<!DOCTYPE html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>Genesis - Tableau de Bord</title>

	<!-- Bootstrap et FontAwesome -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

	<style>
		body {
			background-color: #f8f9fa;
			font-family: 'Helvetica Neue', Arial, sans-serif;
			margin: 0;
			padding: 0;
			overflow-x: hidden;
		}

		/* ===== NAVBAR TOP ===== */
		.navbar-top {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 60px;
			background-color: #ffffff;
			border-bottom: 1px solid #ddd;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			z-index: 2000;
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 0 20px;
		}
		.navbar-top .brand {
			font-weight: bold;
			color: #28a745;
			font-size: 20px;
		}
		.navbar-top .buttons a,
		.navbar-top .buttons button {
			margin-left: 10px;
		}

		/* Hamburger pour mobile */
		.navbar-toggle {
			display: none;
			cursor: pointer;
		}
		.navbar-toggle .icon-bar {
			width: 22px;
			height: 2px;
			background-color: #28a745;
			margin: 4px 0;
			transition: 0.4s;
		}

		/* ===== SIDEBAR ===== */
		.sidebar {
			position: fixed;
			top: 60px;
			left: 0;
			height: 100%;
			width: 220px;
			background-color: #ffffff;
			border-right: 1px solid #ddd;
			box-shadow: 2px 0 5px rgba(0,0,0,0.05);
			padding-top: 20px;
			z-index: 1000;
			transition: transform 0.3s ease;
		}
		.sidebar h2 {
			text-align: center;
			font-weight: bold;
			color: #28a745;
			margin-bottom: 30px;
			font-size: 22px;
		}
		.sidebar ul {
			list-style-type: none;
			padding: 0;
		}
		.sidebar ul li {
			padding: 12px 20px;
			border-bottom: 1px solid #eee;
		}
		.sidebar ul li a {
			color: #333;
			text-decoration: none;
			display: flex;
			align-items: center;
			font-size: 15px;
		}
		.sidebar ul li.active a {
			background-color: #28a745;
			color: #fff;
			border-radius: 5px;
		}

		/* ===== CONTENU PRINCIPAL ===== */
		.main-content {
			margin-left: 240px;
			padding: 80px 30px 30px 30px;
			transition: margin-left 0.3s ease;
		}
		iframe { 
			width: 100%; 
			height: 450px; 
			margin-bottom: 20px; 
			border: none;
			border-radius: 10px;
			box-shadow: 0 2px 6px rgba(0,0,0,0.1);
		}

		/* ===== RESPONSIVE ===== */
		@media (max-width: 768px) {
			.navbar-toggle {
				display: block;
			}
			.sidebar {
				transform: translateX(-100%);
				width: 200px;
				position: fixed;
				height: 100%;
				top: 60px;
			}
			.sidebar.show {
				transform: translateX(0);
			}
			.main-content {
				margin-left: 0;
				padding: 100px 15px 15px 15px;
			}
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
			<form class="form-inline" method="post" action="excel.php" style="display:inline;" id="downloadForm">
				<button type="submit" name="generate_pdf" class="btn btn-success">
					<i class="fa fa-file--o">Reinitialisation de la base</i> 
				</button>
				<span id="loadingSpinner" class="glyphicon glyphicon-refresh glyphicon-spin"></span>
			</form>
			<a href="logout.php" class="btn btn-danger"><i class="fa fa-sign-out"></i> Déconnexion</a>
		</div>
	</div>

	<!-- ===== SIDEBAR ===== -->
	<div class="sidebar" id="sidebar">
		<h2>Menu</h2>
		<ul>
			<li class="active"><a href="#"><i class="fa fa-home"></i> Accueil</a></li>
			<li><a href="parametrage.php"><i class="fa fa-cog"></i> Paramétrage</a></li>
			<li><a href="historique.php"><i class="fa fa-history"></i> Historique</a></li>
			<li><a href="ia.php"><i class="fa fa-android"></i> Assistant IA</a></li>
		</ul>
	</div>

	<!-- ===== CONTENU PRINCIPAL ===== -->
	<div class="main-content">
		<h3 class="text-success"><i class="fa fa-line-chart"></i> Données en temps réel</h3>
		<hr>
		<iframe src="grafique_temperature.php"></iframe>
		<iframe src="grafique_humidite.php"></iframe>
		<iframe src="grafique_lumiere.php"></iframe>
		<iframe src="grafique_eau.php"></iframe>
		<iframe src="grafique_co2.php"></iframe>
		<iframe src="grafique_arrosage.php"></iframe>
	</div>

	<!-- ===== JS ===== -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
	<script>
		// Toggle sidebar sur mobile
		const toggleBtn = document.getElementById('toggleSidebar');
		const sidebar = document.getElementById('sidebar');
		toggleBtn.addEventListener('click', () => {
			sidebar.classList.toggle('show');
		});

		// Affiche spinner téléchargement
		$('#downloadForm').on('submit', function() {
			$('#loadingSpinner').show();
			setTimeout(() => $('#loadingSpinner').hide(), 3000);
		});
	</script>

</body>
</html>
