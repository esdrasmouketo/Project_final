<?php
// Démarrer la session pour stocker les informations de connexion
session_start();

// Informations de connexion à la base de données
$host = 'localhost';  // Hôte (serveur de base de données)
$user = 'root';        // Nom d'utilisateur de la base de données (à ajuster selon ta configuration)
$password = '';        // Mot de passe de l'utilisateur (à ajuster selon ta configuration)
$database = 'ardbd';   // Nom de la base de données

// Création de la connexion à la base de données
$conn = new mysqli($host, $user, $password, $database);

// Vérification de la connexion à la base de données
if ($conn->connect_error) {
    die("La connexion a échoué : " . $conn->connect_error);
}

// Vérifier si le formulaire de connexion a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Préparer et exécuter la requête SQL pour vérifier les informations d'identification
    $stmt = $conn->prepare("SELECT id, mot_de_passe FROM agent WHERE id = ?");
    $stmt->bind_param("s", $username); // l'utilisateur est une chaîne (string)
    $stmt->execute();
    $result = $stmt->get_result();

    // Vérifier si l'utilisateur existe
    if ($result->num_rows > 0) {
        // Récupérer les données de l'utilisateur
        $row = $result->fetch_assoc();
        
        // Vérifier si le mot de passe correspond
        if ($password === $row['mot_de_passe']) {
            // Stocker l'utilisateur dans la session
            $_SESSION['username'] = $username;

            // Rediriger vers la page principale après une connexion réussie
            header("Location: index.php");
            exit();
        } else {
            // Si le mot de passe est incorrect
            $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    } else {
        // Si l'utilisateur n'existe pas
        $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
    }

    // Fermer la requête préparée
    $stmt->close();
}

// Fermer la connexion
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .container {
            max-width: 400px;
            margin-top: 50px;
        }
        .form-container {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .error-message {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2>Connexion</h2>

        <!-- Affichage du message d'erreur en cas de connexion échouée -->
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-success">Se connecter</button>
        </form>
    </div>
</div>

<!-- jQuery et Bootstrap JS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

</body>
</html>
