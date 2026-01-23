# CHAPITRE IV : MECANISME DE SECURISATION DES ECHANGES DE DONNEES

## Introduction

Ce document presente les mecanismes de securisation implementes dans l'application **GENESIS** pour proteger les echanges de donnees entre le client (navigateur), le serveur web et la base de donnees.

---

## 1. AUTHENTIFICATION ET GESTION DES SESSIONS

### 1.1 Hashage des Mots de Passe (Bcrypt)

Les mots de passe ne sont jamais stockes en clair dans la base de donnees. L'algorithme **bcrypt** est utilise pour le hashage.

```php
// Hashage du mot de passe
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Verification du mot de passe
if (password_verify($password, $row['mot_de_passe'])) {
    // Mot de passe correct
}
```

**Avantages de bcrypt :**
- Algorithme lent par conception (protection contre brute-force)
- Salt unique genere automatiquement pour chaque hash
- Facteur de cout ajustable pour augmenter la securite

### 1.2 Configuration Securisee des Sessions

```php
session_set_cookie_params([
    'lifetime' => 0,           // Expire a la fermeture du navigateur
    'path' => '/',
    'domain' => '',
    'secure' => true,          // HTTPS uniquement
    'httponly' => true,        // Inaccessible via JavaScript
    'samesite' => 'Strict'     // Protection CSRF
]);
```

| Parametre | Valeur | Protection |
|-----------|--------|------------|
| `httponly` | true | Empeche le vol de session via XSS |
| `secure` | true | Cookie transmis uniquement en HTTPS |
| `samesite` | Strict | Bloque les requetes cross-site |

### 1.3 Regeneration de l'ID de Session

Pour prevenir les attaques de **fixation de session**, l'ID est regenere :

```php
// Apres une connexion reussie
session_regenerate_id(true);

// Periodiquement (toutes les 30 minutes)
if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
```

### 1.4 Expiration Automatique des Sessions

```php
define('SESSION_TIMEOUT', 1800); // 30 minutes

function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}
```

### 1.5 Protection contre les Attaques par Force Brute

```php
define('MAX_LOGIN_ATTEMPTS', 5);      // Tentatives max
define('LOGIN_LOCKOUT_TIME', 900);    // Blocage 15 minutes

// Verification du blocage
$lockout_key = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR']);
if ($attempts_data['count'] >= MAX_LOGIN_ATTEMPTS) {
    $time_remaining = ($attempts_data['time'] + LOGIN_LOCKOUT_TIME) - time();
    if ($time_remaining > 0) {
        $error = "Trop de tentatives. Reessayez dans " . ceil($time_remaining/60) . " min.";
    }
}
```

---

## 2. PROTECTION CONTRE LES ATTAQUES CSRF

### 2.1 Principe du Token CSRF

Le **Cross-Site Request Forgery** est une attaque ou un site malveillant fait executer des actions a un utilisateur authentifie sans son consentement.

### 2.2 Generation du Token

```php
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}
```

- Token de **256 bits** (32 octets en hexadecimal)
- Genere avec `random_bytes()` (cryptographiquement sur)
- Stocke dans la session utilisateur

### 2.3 Integration dans les Formulaires

```php
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}
```

```html
<form method="post" action="save.php">
    <?php echo csrfField(); ?>
    <!-- Autres champs du formulaire -->
    <button type="submit">Envoyer</button>
</form>
```

### 2.4 Verification du Token

```php
function verifyCSRFToken($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Utilisation
if (!verifyCSRFToken($_POST['csrf_token'])) {
    die("Erreur de securite. Requete rejetee.");
}
```

**Note :** `hash_equals()` est utilise pour une comparaison en temps constant, empechant les attaques timing.

---

## 3. PROTECTION CONTRE LES INJECTIONS SQL

### 3.1 Requetes Preparees (Prepared Statements)

Les requetes preparees separent le code SQL des donnees, empechant l'injection.

```php
// VULNERABLE (a ne jamais faire)
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];

// SECURISE (requete preparee)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

### 3.2 Configuration PDO Securisee

```php
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false  // Requetes preparees NATIVES
];

$conn = new PDO($dsn, DB_USER, DB_PASS, $options);
```

| Option | Description |
|--------|-------------|
| `ERRMODE_EXCEPTION` | Lance des exceptions en cas d'erreur |
| `EMULATE_PREPARES = false` | Utilise les vraies requetes preparees du SGBD |

### 3.3 Exemple Complet

```php
// Authentification securisee
$stmt = $conn->prepare("SELECT id, email, mot_de_passe FROM techniciens WHERE email = ?");
$stmt->execute([$username]);
$row = $stmt->fetch();

// Mise a jour securisee
$updateStmt = $conn->prepare("UPDATE techniciens SET mot_de_passe = ? WHERE id = ?");
$updateStmt->execute([$hashed, $row['id']]);
```

---

## 4. PROTECTION CONTRE LES ATTAQUES XSS

### 4.1 Echappement des Donnees

Le **Cross-Site Scripting** permet d'injecter du code JavaScript malveillant.

```php
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
```

### 4.2 Utilisation

```php
// VULNERABLE
echo $_GET['name'];

// SECURISE
echo e($_GET['name']);
```

```html
<!-- Dans les templates -->
<p>Bienvenue, <?php echo e($user['prenom']); ?></p>
<input type="text" value="<?php echo e($value); ?>">
```

| Caractere | Encode en |
|-----------|-----------|
| `<` | `&lt;` |
| `>` | `&gt;` |
| `"` | `&quot;` |
| `'` | `&#039;` |
| `&` | `&amp;` |

---

## 5. HEADERS DE SECURITE HTTP

### 5.1 Implementation

```php
function setSecurityHeaders() {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; ...");
}
```

### 5.2 Description des Headers

| Header | Valeur | Protection |
|--------|--------|------------|
| `X-Frame-Options` | SAMEORIGIN | Empeche le clickjacking (iframe) |
| `X-XSS-Protection` | 1; mode=block | Active le filtre XSS du navigateur |
| `X-Content-Type-Options` | nosniff | Empeche le MIME sniffing |
| `Referrer-Policy` | strict-origin-when-cross-origin | Controle les informations de referrer |
| `Content-Security-Policy` | ... | Restreint les sources de contenu |

### 5.3 Content Security Policy (CSP)

```
Content-Security-Policy:
    default-src 'self';                    // Par defaut, meme origine
    script-src 'self' https://ajax.googleapis.com;  // Scripts autorises
    style-src 'self' 'unsafe-inline';      // Styles autorises
    img-src 'self' data:;                  // Images autorisees
```

---

## 6. SECURISATION DES UPLOADS DE FICHIERS

### 6.1 Validation Multi-Niveaux

```php
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB

function validateUpload($file, $type = 'image') {
    $errors = [];

    // 1. Verifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Erreur lors de l'upload.";
    }

    // 2. Verifier la taille
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $errors[] = "Fichier trop volumineux.";
    }

    // 3. Verifier l'extension
    if (!isAllowedExtension($file['name'], $type)) {
        $errors[] = "Extension non autorisee.";
    }

    // 4. Verifier le type MIME reel
    if (!isValidMimeType($file['tmp_name'], $type)) {
        $errors[] = "Type de fichier invalide.";
    }

    return ['valid' => empty($errors), 'errors' => $errors];
}
```

### 6.2 Verification du Type MIME Reel

```php
function isValidMimeType($filepath, $type = 'image') {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filepath);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    return in_array($mime, $allowedMimes);
}
```

### 6.3 Noms de Fichiers Securises

```php
function generateSecureFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

// Resultat: "a3f2b8c9d4e5f6a7b8c9d0e1f2a3b4c5.jpg"
```

**Avantages :**
- Empeche l'execution de fichiers malveillants
- Evite les conflits de noms
- Masque les noms originaux

---

## 7. SCHEMA RECAPITULATIF

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT (Navigateur)                      │
├─────────────────────────────────────────────────────────────────┤
│  - Cookie de session (httponly, secure, samesite)               │
│  - Token CSRF dans les formulaires                               │
│  - Donnees echappees (anti-XSS)                                  │
└────────────────────────────┬────────────────────────────────────┘
                             │ HTTPS
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                         SERVEUR WEB (PHP)                        │
├─────────────────────────────────────────────────────────────────┤
│  1. Headers de securite (CSP, X-Frame-Options, etc.)            │
│  2. Verification session (timeout, regeneration ID)              │
│  3. Validation token CSRF                                        │
│  4. Validation/Sanitisation des entrees                          │
│  5. Controle des uploads (extension, MIME, taille)               │
└────────────────────────────┬────────────────────────────────────┘
                             │ Requetes preparees
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BASE DE DONNEES (MySQL)                     │
├─────────────────────────────────────────────────────────────────┤
│  - Mots de passe haches (bcrypt)                                 │
│  - Requetes preparees (anti-injection SQL)                       │
│  - Charset UTF8MB4                                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 8. TABLEAU RECAPITULATIF DES PROTECTIONS

| Menace | Protection Implementee | Fichier |
|--------|----------------------|---------|
| Vol de session | Cookies httponly, secure, samesite | auth_check.php |
| Fixation de session | Regeneration ID de session | login.php |
| Force brute | Limitation tentatives + blocage | login.php |
| Injection SQL | Requetes preparees PDO | config.php |
| XSS | Echappement htmlspecialchars | config.php |
| CSRF | Tokens aleatoires | config.php |
| Clickjacking | Header X-Frame-Options | config.php |
| MIME sniffing | Header X-Content-Type-Options | config.php |
| Upload malveillant | Validation multi-niveaux | config.php |
| Mots de passe | Hashage bcrypt | login.php |

---

## 9. RECOMMANDATIONS POUR LA PRODUCTION

1. **HTTPS obligatoire** : Certificat SSL/TLS valide
2. **Variables d'environnement** : Stocker les credentials hors du code
3. **Logs securises** : Journaliser les tentatives d'intrusion
4. **Mises a jour** : Maintenir PHP et les dependances a jour
5. **Firewall applicatif** : Implementer un WAF si possible
6. **Audit regulier** : Tests de penetration periodiques

---

## Conclusion

L'application GENESIS implemente une architecture de securite en profondeur (**defense in depth**) avec plusieurs couches de protection. Chaque echange de donnees est securise depuis le navigateur jusqu'a la base de donnees, garantissant la confidentialite, l'integrite et la disponibilite des informations.
