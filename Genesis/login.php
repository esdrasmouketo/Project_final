<?php
/**
 * Page de connexion sécurisée - GENESIS
 */

// Configuration sécurisée de la session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

require_once __DIR__ . '/config.php';

// Appliquer les headers de sécurité
setSecurityHeaders();

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';

// Gestion des erreurs de session expirée
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error_message = 'Votre session a expiré. Veuillez vous reconnecter.';
}

// Vérifier si le formulaire de connexion a été soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérifier le token CSRF
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $error_message = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        // Récupérer et nettoyer les données du formulaire
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validation basique
        if (empty($username) || empty($password)) {
            $error_message = "Veuillez remplir tous les champs.";
        } else {
            // Vérifier le blocage pour tentatives multiples
            $lockout_key = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR']);
            if (isset($_SESSION[$lockout_key])) {
                $attempts_data = $_SESSION[$lockout_key];
                if ($attempts_data['count'] >= MAX_LOGIN_ATTEMPTS) {
                    $time_remaining = ($attempts_data['time'] + LOGIN_LOCKOUT_TIME) - time();
                    if ($time_remaining > 0) {
                        $error_message = "Trop de tentatives échouées. Réessayez dans " . ceil($time_remaining / 60) . " minute(s).";
                    } else {
                        // Réinitialiser après le temps de blocage
                        unset($_SESSION[$lockout_key]);
                    }
                }
            }

            if (empty($error_message)) {
                try {
                    $conn = getDBConnection();

                    // Requête préparée pour récupérer l'utilisateur
                    $stmt = $conn->prepare("SELECT id, email, mot_de_passe FROM techniciens WHERE email = ?");
                    $stmt->execute([$username]);
                    $row = $stmt->fetch();

                    $login_success = false;

                    if ($row) {
                        // Vérifier le mot de passe avec password_verify (bcrypt)
                        // Compatibilité: si l'ancien mot de passe n'est pas haché, accepter puis migrer
                        if (password_verify($password, $row['mot_de_passe'])) {
                            $login_success = true;
                        } elseif ($password === $row['mot_de_passe'] && !password_needs_rehash($row['mot_de_passe'], PASSWORD_DEFAULT)) {
                            // Ancien mot de passe en clair - accepter et migrer vers bcrypt
                            $login_success = true;

                            // Migrer automatiquement vers bcrypt
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $conn->prepare("UPDATE techniciens SET mot_de_passe = ? WHERE id = ?");
                            $updateStmt->execute([$hashed, $row['id']]);
                        } elseif ($password === $row['mot_de_passe']) {
                            // Mot de passe en clair (migration nécessaire)
                            $login_success = true;

                            // Migrer automatiquement vers bcrypt
                            $hashed = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $conn->prepare("UPDATE techniciens SET mot_de_passe = ? WHERE id = ?");
                            $updateStmt->execute([$hashed, $row['id']]);
                        }
                    }

                    if ($login_success) {
                        // Régénérer l'ID de session pour éviter la fixation de session
                        session_regenerate_id(true);

                        // Stocker les informations de session
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['email'];
                        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['last_activity'] = time();
                        $_SESSION['created'] = time();

                        // Réinitialiser les tentatives de connexion
                        unset($_SESSION[$lockout_key]);

                        // Régénérer le token CSRF
                        regenerateCSRFToken();

                        // Rediriger vers la page demandée ou l'accueil
                        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                        unset($_SESSION['redirect_after_login']);
                        header("Location: " . $redirect);
                        exit();
                    } else {
                        // Incrémenter le compteur de tentatives
                        if (!isset($_SESSION[$lockout_key])) {
                            $_SESSION[$lockout_key] = ['count' => 0, 'time' => time()];
                        }
                        $_SESSION[$lockout_key]['count']++;
                        $_SESSION[$lockout_key]['time'] = time();

                        $error_message = "Nom d'utilisateur ou mot de passe incorrect.";
                    }
                } catch (PDOException $e) {
                    error_log("Erreur login: " . $e->getMessage());
                    $error_message = "Erreur de connexion. Veuillez réessayer.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Genesis</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 20px;
        }
        .form-container {
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .form-container h2 {
            text-align: center;
            color: #28a745;
            margin-bottom: 30px;
        }
        .form-container h2 i {
            display: block;
            font-size: 50px;
            margin-bottom: 10px;
        }
        .error-message {
            color: #d9534f;
            background: #f8d7da;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        .btn-success {
            width: 100%;
            padding: 12px;
            font-size: 16px;
        }
        .form-group label {
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="form-container">
        <h2>
            <i class="fa fa-leaf"></i>
            Genesis
        </h2>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <i class="fa fa-exclamation-triangle"></i> <?php echo e($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="off">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label for="username"><i class="fa fa-user"></i> Nom d'utilisateur</label>
                <input type="text" class="form-control" id="username" name="username"
                       required autocomplete="username" maxlength="50"
                       value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password"><i class="fa fa-lock"></i> Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password"
                       required autocomplete="current-password" maxlength="255">
            </div>

            <button type="submit" class="btn btn-success">
                <i class="fa fa-sign-in"></i> Se connecter
            </button>
        </form>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

</body>
</html>
