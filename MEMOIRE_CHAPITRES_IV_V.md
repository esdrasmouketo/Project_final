# MEMOIRE - Chapitres IV et V

# Systeme GENESIS : Serre Connectee IoT

---

# Chapitre IV : Mecanismes de securisation des echanges de donnees

---

## IV.1 Notions generales sur la securite des systemes IoT

### IV.1.1 Contexte et enjeux

Les systemes IoT (Internet of Things) sont exposes a de nombreuses menaces en raison de leur nature distribuee et de la diversite des equipements connectes. Dans le cadre du projet GENESIS, une serre connectee communique en permanence des donnees sensibles (temperature, humidite, CO2, niveau d'eau) entre des capteurs Arduino, un serveur web PHP et une base de donnees MySQL.

Les principaux enjeux de securite sont :

- **Confidentialite** : empecher l'acces non autorise aux donnees des capteurs et aux identifiants des utilisateurs.
- **Integrite** : garantir que les donnees transmises par les capteurs ne sont pas alterees en transit.
- **Disponibilite** : assurer le fonctionnement continu du systeme de surveillance.
- **Authenticite** : verifier que les donnees proviennent bien des equipements autorises.

### IV.1.2 Surface d'attaque d'un systeme IoT

La surface d'attaque du systeme GENESIS comprend :

| Couche | Composant | Risques |
| --- | --- | --- |
| Perception | Capteurs Arduino (DHT11, niveau d'eau, CO2) | Falsification des donnees, acces physique |
| Reseau | Communication HTTP entre Arduino et serveur | Interception, injection de donnees |
| Application | Interface web PHP (dashboard, login) | XSS, injection SQL, CSRF, brute force |
| Stockage | Base de donnees MySQL (ardbd) | Acces non autorise, exfiltration |

### IV.1.3 Les menaces courantes en IoT

- **Attaques par injection** : un attaquant envoie des donnees malveillantes via les endpoints de reception des capteurs.
- **Attaques par force brute** : tentatives repetees pour deviner les identifiants de connexion au dashboard.
- **Interception des donnees (Man-in-the-Middle)** : ecoute du trafic entre l'Arduino et le serveur.
- **Cross-Site Scripting (XSS)** : injection de scripts malveillants dans les pages web du dashboard.
- **Cross-Site Request Forgery (CSRF)** : execution d'actions non autorisees via des requetes forgees.
- **Fixation de session** : detournement de la session d'un utilisateur authentifie.

---

## IV.2 Chiffrement des donnees en transit et au repos

### IV.2.1 Donnees en transit

Les donnees en transit correspondent aux echanges entre :
- Les capteurs Arduino et le serveur web (endpoint `conexion_arduino.php`)
- Le navigateur de l'utilisateur et le dashboard GENESIS

**Protocoles de chiffrement utilises :**

- **HTTPS (HTTP over TLS)** : le protocole HTTPS chiffre l'ensemble des echanges HTTP entre le client et le serveur grace au protocole TLS (Transport Layer Security). Dans GENESIS, la configuration des cookies de session active automatiquement le flag `secure` lorsque HTTPS est disponible :

```php
// Configuration securisee des cookies de session
session_set_cookie_params([
    'secure' => isset($_SERVER['HTTPS']), // HTTPS uniquement si disponible
    'httponly' => true,                    // Pas accessible via JavaScript
    'samesite' => 'Strict'                // Protection CSRF cookies
]);
```

- **TLS 1.2 / 1.3** : protocole cryptographique sous-jacent a HTTPS qui assure le chiffrement, l'authentification du serveur et l'integrite des messages.

### IV.2.2 Donnees au repos

Les donnees au repos sont stockees dans la base de donnees MySQL `ardbd`. Les mecanismes de protection incluent :

- **Hachage des mots de passe avec bcrypt** : les mots de passe des utilisateurs ne sont jamais stockes en clair. L'algorithme bcrypt (via `password_hash()` de PHP) genere un hash irreversible avec un sel unique :

```php
// Hachage du mot de passe avec bcrypt
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Verification lors de la connexion
if (password_verify($password, $row['mot_de_passe'])) {
    // Mot de passe correct
}
```

- **Migration automatique des anciens mots de passe** : le systeme detecte les mots de passe stockes en clair et les migre automatiquement vers bcrypt lors de la prochaine connexion.

- **Acces a la base de donnees** : la connexion MySQL utilise des identifiants configures dans un fichier de configuration centralise (`config.php`), avec la recommandation d'utiliser des variables d'environnement en production.

---

## IV.3 Mecanismes d'authentification des equipements

### IV.3.1 Authentification des utilisateurs (dashboard)

L'authentification au dashboard GENESIS repose sur plusieurs couches de securite :

**a) Systeme de login securise**

Le processus d'authentification, implemente dans `login.php`, comprend :
- Verification des identifiants (email + mot de passe) via requetes preparees
- Comparaison du mot de passe avec le hash bcrypt stocke en base
- Regeneration de l'identifiant de session apres connexion reussie (protection contre la fixation de session)

**b) Protection contre le brute force**

Le systeme limite les tentatives de connexion echouees :
- Maximum de 5 tentatives par adresse IP (`MAX_LOGIN_ATTEMPTS = 5`)
- Blocage de 15 minutes apres depassement du seuil (`LOGIN_LOCKOUT_TIME = 900 secondes`)
- Compteur reinitialise apres une connexion reussie

```php
// Verification du blocage
if ($attempts_data['count'] >= MAX_LOGIN_ATTEMPTS) {
    $time_remaining = ($attempts_data['time'] + LOGIN_LOCKOUT_TIME) - time();
    if ($time_remaining > 0) {
        $error_message = "Trop de tentatives echouees. Reessayez dans "
                       . ceil($time_remaining / 60) . " minute(s).";
    }
}
```

### IV.3.2 Authentification des equipements IoT (Arduino)

L'endpoint de reception des donnees Arduino (`conexion_arduino.php`) prevoit plusieurs mecanismes :

**a) Filtrage par adresse IP**

Une liste blanche d'adresses IP autorisees limite l'acces aux seuls equipements connus :

```php
$ALLOWED_IPS = ['127.0.0.1', '::1', '192.168.1.150']; // localhost + Arduino
```

**b) Cle API**

Un systeme de cle API permet d'authentifier les requetes des equipements :

```php
$API_KEY = 'GENESIS_SECRET_KEY_2024';
// Verification avec hash_equals() pour eviter les timing attacks
if (!hash_equals($API_KEY, $provided_key)) {
    http_response_code(401);
    echo "Cle API invalide";
    exit;
}
```

**c) Validation des donnees recues**

Chaque parametre recu est filtre et valide :
- Validation du type (FILTER_VALIDATE_FLOAT)
- Verification des plages de valeurs realistes (ex: temperature entre -50 et 100 °C)
- Rejet des donnees hors limites avec code d'erreur HTTP 400

---

## IV.4 Gestion des autorisations et des acces

### IV.4.1 Controle d'acces par session

Le systeme de gestion des sessions dans GENESIS comprend :

**a) Middleware d'authentification (`auth_check.php`)**

Chaque page protegee inclut ce fichier au debut, qui verifie :
- L'existence d'une session valide
- Le timeout d'inactivite (30 minutes maximum : `SESSION_TIMEOUT = 1800`)
- La regeneration periodique de l'identifiant de session (toutes les 30 minutes)

```php
// Verification du timeout de session
if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    // Redirection vers la page de login
}
```

**b) Configuration securisee des cookies de session**

| Parametre | Valeur | Role |
| --- | --- | --- |
| `lifetime` | 0 | Cookie expire a la fermeture du navigateur |
| `httponly` | true | Cookie inaccessible via JavaScript (anti-XSS) |
| `samesite` | Strict | Protection contre les requetes cross-site (CSRF) |
| `secure` | auto | Cookie transmis uniquement via HTTPS si disponible |

### IV.4.2 Controle d'acces aux fichiers

- Le fichier `config.php` contient les identifiants de base de donnees et ne doit pas etre accessible publiquement en production.
- Les fichiers uploades sont renommes avec un nom aleatoire securise (`bin2hex(random_bytes(16))`) pour empecher la prediction des URLs.
- La validation des uploads verifie l'extension, le type MIME et la taille du fichier.

---

## IV.5 Presentation des protocoles securises

### IV.5.1 HTTPS (HyperText Transfer Protocol Secure)

**Principe** : HTTPS encapsule le protocole HTTP dans une couche de chiffrement TLS. Toutes les donnees echangees (formulaires, cookies, donnees JSON) sont chiffrees.

**Application dans GENESIS** :
- Communication navigateur <-> dashboard (pages de login, graphiques, parametrage)
- Communication Arduino <-> serveur (envoi des mesures des capteurs)

**Avantages** :
- Chiffrement des donnees en transit
- Authentification du serveur via certificat SSL/TLS
- Integrite des donnees (detection des modifications)

### IV.5.2 TLS (Transport Layer Security)

**Principe** : TLS est le protocole cryptographique utilise par HTTPS. Il assure :
- L'echange securise de cles via un handshake asymetrique (RSA / ECDHE)
- Le chiffrement symetrique des donnees (AES-128 ou AES-256)
- L'integrite des messages (HMAC-SHA256)

**Versions** :
- TLS 1.2 : version largement supportee, consideree comme securisee
- TLS 1.3 : version la plus recente, handshake simplifie, plus rapide

### IV.5.3 MQTT securise (MQTTS)

**Principe** : MQTT (Message Queuing Telemetry Transport) est un protocole leger de messagerie publie/souscrit, tres utilise en IoT. Sa version securisee (MQTTS) utilise TLS pour chiffrer les echanges.

**Caracteristiques** :
- Protocole leger adapte aux equipements a ressources limitees
- Architecture publie/souscrit avec un broker central
- QoS (Quality of Service) configurable (0, 1 ou 2)
- Port standard : 8883 (MQTTS) contre 1883 (MQTT non securise)

**Comparaison** : dans le cadre de GENESIS, le protocole HTTP/HTTPS a ete prefere a MQTT car l'Arduino communique directement avec le serveur web PHP via des requetes GET, sans necessite d'un broker intermediaire.

### IV.5.4 Tableau comparatif des protocoles

| Critere | HTTP/HTTPS | MQTT/MQTTS | CoAP/CoAPS |
| --- | --- | --- | --- |
| Architecture | Client-Serveur | Publie/Souscrit | Client-Serveur |
| Transport | TCP | TCP | UDP |
| Chiffrement | TLS | TLS | DTLS |
| Overhead | Eleve | Faible | Tres faible |
| Port securise | 443 | 8883 | 5684 |
| Adapte a GENESIS | Oui (choisi) | Possible | Non retenu |

---

## IV.6 Choix des solutions de securite adaptees au contexte etudie

### IV.6.1 Synthese des solutions retenues

En fonction du contexte du projet GENESIS (serre connectee avec Arduino, serveur XAMPP, dashboard PHP), les solutions suivantes ont ete retenues :

| Menace | Solution retenue | Implementation |
| --- | --- | --- |
| Interception des donnees | HTTPS / TLS | Configuration du serveur Apache avec certificat SSL |
| Injection SQL | Requetes preparees (PDO/mysqli) | `$stmt = $conn->prepare("... ?")` dans tous les endpoints |
| XSS (Cross-Site Scripting) | Echappement HTML + Headers CSP | Fonction `e()` + `Content-Security-Policy` |
| CSRF (Cross-Site Request Forgery) | Tokens CSRF + SameSite cookies | `generateCSRFToken()` + `samesite: Strict` |
| Brute force | Limitation des tentatives | 5 tentatives max, blocage 15 min |
| Vol de mot de passe | Hachage bcrypt | `password_hash()` + `password_verify()` |
| Fixation de session | Regeneration d'ID | `session_regenerate_id(true)` |
| Donnees capteurs falsifiees | Validation + filtrage IP + cle API | `FILTER_VALIDATE_FLOAT` + plages realistes |
| Upload malveillant | Validation MIME + extension + taille | `validateUpload()` avec triple verification |

### IV.6.2 Justification des choix

- **HTTPS plutot que MQTT securise** : le systeme utilise deja une architecture web PHP classique. HTTPS s'integre naturellement avec Apache/XAMPP sans ajout d'un broker MQTT.
- **Bcrypt plutot que SHA-256** : bcrypt integre un sel automatique et un facteur de cout ajustable, le rendant resistant aux attaques par tables arc-en-ciel et par GPU.
- **PDO avec requetes preparees** : separation stricte entre le code SQL et les donnees utilisateur, empechant toute injection SQL.
- **Tokens CSRF generes par `random_bytes(32)`** : 256 bits d'entropie, rendant les tokens imprevisibles.

---
---

# Chapitre V : Mise en oeuvre des solutions et resultats

---

## V.1 Presentation des solutions de securisation proposees

### V.1.1 Architecture de securite globale

Le systeme GENESIS implemente une architecture de securite en couches (defense en profondeur) :

```
[Arduino/Capteurs]
        |
        | (HTTPS + Cle API + Filtrage IP)
        v
[Serveur Apache/PHP - XAMPP]
        |
        | (Requetes preparees PDO)
        v
[Base de donnees MySQL - ardbd]
        |
        | (Mots de passe haches bcrypt)
        v
[Dashboard Web GENESIS]
        |
        | (Sessions securisees + CSRF + XSS protection + Headers HTTP)
        v
[Navigateur Utilisateur]
```

### V.1.2 Liste des solutions implementees

| # | Solution | Fichier(s) concerne(s) |
| --- | --- | --- |
| 1 | Hachage bcrypt des mots de passe | `login.php`, `reset_password.php`, `migrate_passwords.php` |
| 2 | Protection CSRF par tokens | `config.php`, `login.php`, `parametrage.php`, `actionneur.php` |
| 3 | Prevention des injections SQL | `login.php`, `insert.php`, `conexion_arduino.php`, `auto.php` |
| 4 | Protection XSS | `config.php` (fonction `e()`), tous les fichiers d'affichage |
| 5 | Headers de securite HTTP | `config.php` (fonction `setSecurityHeaders()`) |
| 6 | Gestion securisee des sessions | `auth_check.php`, `login.php` |
| 7 | Protection brute force | `login.php` |
| 8 | Validation des donnees capteurs | `conexion_arduino.php`, `insert.php` |
| 9 | Upload securise des fichiers | `config.php` (fonctions `validateUpload()`, `generateSecureFilename()`) |
| 10 | Authentification des equipements IoT | `conexion_arduino.php` (cle API + filtrage IP) |

---

## V.2 Mise en oeuvre pratique des mecanismes retenus

### V.2.1 Mise en oeuvre du hachage bcrypt

**Fichier** : `login.php`

**Processus** :
1. Lors de l'inscription ou de la reinitialisation du mot de passe, le mot de passe est hache avec `password_hash($password, PASSWORD_DEFAULT)` qui utilise bcrypt avec un cout de 10 par defaut.
2. Lors de la connexion, `password_verify($password, $hash)` compare le mot de passe saisi avec le hash stocke.
3. Si un ancien mot de passe en clair est detecte, il est automatiquement migre vers bcrypt.

**Code implemente** :
```php
// Verification du mot de passe
if (password_verify($password, $row['mot_de_passe'])) {
    $login_success = true;
} elseif ($password === $row['mot_de_passe']) {
    // Migration automatique vers bcrypt
    $login_success = true;
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE techniciens SET mot_de_passe = ? WHERE id = ?");
    $updateStmt->execute([$hashed, $row['id']]);
}
```

### V.2.2 Mise en oeuvre de la protection CSRF

**Fichier** : `config.php`

**Processus** :
1. Un token aleatoire de 256 bits est genere par session via `bin2hex(random_bytes(32))`.
2. Ce token est insere dans chaque formulaire HTML sous forme de champ cache.
3. A la soumission du formulaire, le token est verifie avec `hash_equals()` (comparaison a temps constant pour eviter les timing attacks).
4. Le token est regenere apres chaque action reussie.

**Code implemente** :
```php
// Generation du token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verification du token
function verifyCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Insertion dans le formulaire HTML
echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
```

### V.2.3 Mise en oeuvre de la prevention des injections SQL

**Fichiers** : `insert.php`, `conexion_arduino.php`, `login.php`, `auto.php`

**Methode** : utilisation systematique des requetes preparees avec des placeholders `?` :

```php
// Exemple dans insert.php
$stmt = $conn->prepare("INSERT INTO table_capteurs
    (niveau_eau, niveau_lumiere, arrosage, co2_level, temperature, humidity)
    VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("dddddd", $niveau_eau, $lumiere, $arrosage, $co2, $temperature, $humidity);
$stmt->execute();
```

La separation entre la structure SQL et les donnees rend impossible l'injection de code SQL malveillant.

### V.2.4 Mise en oeuvre des headers de securite HTTP

**Fichier** : `config.php` (fonction `setSecurityHeaders()`)

**Headers implementes** :

| Header | Valeur | Protection |
| --- | --- | --- |
| `X-Frame-Options` | SAMEORIGIN | Empeche le clickjacking (inclusion dans une iframe) |
| `X-XSS-Protection` | 1; mode=block | Active le filtre XSS du navigateur |
| `X-Content-Type-Options` | nosniff | Empeche le sniffing MIME |
| `Referrer-Policy` | strict-origin-when-cross-origin | Limite les informations de referer |
| `Content-Security-Policy` | default-src 'self'; ... | Controle les sources de contenu autorisees |

### V.2.5 Mise en oeuvre de la validation des donnees capteurs

**Fichier** : `conexion_arduino.php`

**Processus en 3 etapes** :
1. **Filtrage du type** : `filter_var($_GET["temp_php"], FILTER_VALIDATE_FLOAT)`
2. **Verification de presence** : rejet si un parametre est manquant ou invalide (HTTP 400)
3. **Verification des plages** : temperature entre -50 et 100 °C, humidite entre 0 et 100 %, etc.

```php
// Validation des plages realistes
if ($temp_php < -50 || $temp_php > 100) {
    $errors[] = "Temperature hors limites (-50 a 100°C)";
}
if ($hum_php < 0 || $hum_php > 100) {
    $errors[] = "Humidite hors limites (0 a 100%)";
}
```

---

## V.3 Scenarios de tests de securite

### V.3.1 Test 1 : Resistance a l'injection SQL

| Element | Detail |
| --- | --- |
| **Objectif** | Verifier que les requetes preparees empechent l'injection SQL |
| **Methode** | Envoi de payloads malveillants via les parametres GET des endpoints |
| **Payload teste** | `temperature=25; DROP TABLE table_capteurs;--` |
| **Endpoint** | `insert.php?temperature=25; DROP TABLE table_capteurs;--` |
| **Resultat attendu** | Rejet de la valeur (FILTER_VALIDATE_FLOAT retourne false) |

### V.3.2 Test 2 : Resistance au brute force

| Element | Detail |
| --- | --- |
| **Objectif** | Verifier le blocage apres 5 tentatives echouees |
| **Methode** | Envoi de 6 requetes POST consecutives avec un mauvais mot de passe |
| **Resultat attendu** | Blocage de 15 minutes apres la 5eme tentative |
| **Verification** | Message "Trop de tentatives echouees. Reessayez dans X minute(s)." |

### V.3.3 Test 3 : Protection CSRF

| Element | Detail |
| --- | --- |
| **Objectif** | Verifier qu'un formulaire sans token CSRF est rejete |
| **Methode** | Soumission d'un formulaire POST sans le champ `csrf_token` |
| **Resultat attendu** | Message "Erreur de securite. Veuillez reessayer." |

### V.3.4 Test 4 : Protection XSS

| Element | Detail |
| --- | --- |
| **Objectif** | Verifier que les scripts injectes sont neutralises |
| **Methode** | Saisie de `<script>alert('XSS')</script>` dans un champ de formulaire |
| **Resultat attendu** | Le script est affiche en texte brut grace a `htmlspecialchars()` |

### V.3.5 Test 5 : Validation des donnees capteurs

| Element | Detail |
| --- | --- |
| **Objectif** | Verifier le rejet des donnees hors limites |
| **Methode** | Envoi de `temp_php=999` via l'endpoint Arduino |
| **Resultat attendu** | Reponse HTTP 400 : "Temperature hors limites (-50 a 100°C)" |

### V.3.6 Test 6 : Expiration de session

| Element | Detail |
| --- | --- |
| **Objectif** | Verifier la deconnexion automatique apres 30 minutes d'inactivite |
| **Methode** | Se connecter, attendre 30 minutes, puis acceder a une page protegee |
| **Resultat attendu** | Redirection vers `login.php?error=session_expired` |

---

## V.4 Resultats obtenus

### V.4.1 Tableau recapitulatif des tests

| Test | Menace testee | Resultat | Statut |
| --- | --- | --- | --- |
| Test 1 | Injection SQL | Payload rejete, base intacte | REUSSI |
| Test 2 | Brute force | Blocage effectif apres 5 tentatives | REUSSI |
| Test 3 | CSRF | Formulaire sans token rejete | REUSSI |
| Test 4 | XSS | Script neutralise et affiche en texte | REUSSI |
| Test 5 | Donnees capteurs falsifiees | Valeur hors plage rejetee (HTTP 400) | REUSSI |
| Test 6 | Vol de session | Deconnexion automatique apres timeout | REUSSI |

### V.4.2 Resultats detailles

**Injection SQL** : les requetes preparees avec `PDO` et `mysqli` separent systematiquement le code SQL des donnees. Aucune injection n'a pu etre effectuee lors des tests.

**Brute force** : le systeme de comptage des tentatives par adresse IP fonctionne correctement. Apres 5 tentatives echouees, le compte est bloque pendant 15 minutes.

**CSRF** : les tokens generes par `random_bytes(32)` (256 bits d'entropie) sont imprevisibles. La comparaison par `hash_equals()` empeche les timing attacks.

**XSS** : la fonction `e()` (alias de `htmlspecialchars()` avec `ENT_QUOTES` et `UTF-8`) neutralise toutes les tentatives d'injection de scripts. Les headers `Content-Security-Policy` ajoutent une couche de protection supplementaire.

**Validation des capteurs** : le triple filtrage (type + presence + plage) rejette efficacement les donnees aberrantes.

**Sessions** : la combinaison du timeout (30 min), de la regeneration periodique de l'ID de session, et des cookies `httponly` + `samesite: Strict` protege contre la fixation et le vol de session.

---

## V.5 Analyse et interpretation des resultats

### V.5.1 Efficacite des mecanismes

Les resultats montrent que l'ensemble des mecanismes de securite implementes dans GENESIS fonctionnent comme prevu :

- **Couche reseau** : HTTPS protege les donnees en transit.
- **Couche application** : les protections CSRF, XSS et injection SQL couvrent les principales vulnerabilites du Top 10 OWASP.
- **Couche authentification** : bcrypt + brute force protection + gestion des sessions forment une defense solide.
- **Couche IoT** : la validation des donnees capteurs et l'authentification par cle API/IP protegent les endpoints de reception.

### V.5.2 Conformite avec les standards

| Standard/Reference | Elements couverts |
| --- | --- |
| OWASP Top 10 (2021) | A01 (Broken Access Control), A02 (Cryptographic Failures), A03 (Injection), A05 (Security Misconfiguration), A07 (XSS) |
| Bonnes pratiques PHP | Requetes preparees, `password_hash()`, `htmlspecialchars()`, `random_bytes()` |
| Securite des sessions | Regeneration d'ID, timeout, cookies securises |

### V.5.3 Impact sur les performances

Les mecanismes de securite implementes ont un impact minimal sur les performances :
- Le hachage bcrypt ajoute un delai d'environ 100ms par verification (acceptable pour une action de login)
- Les requetes preparees n'ajoutent pas de surcharge significative par rapport aux requetes classiques
- Les headers de securite HTTP sont envoyes une seule fois par requete

---

## V.6 Limites de la solution mise en place

### V.6.1 Limites techniques

| Limite | Description | Amelioration possible |
| --- | --- | --- |
| Communication Arduino en HTTP | L'Arduino communique actuellement en HTTP non chiffre sur le reseau local | Passage a HTTPS avec un module WiFi supportant TLS (ex: ESP32) |
| Cle API en dur dans le code | La cle API est definie directement dans `conexion_arduino.php` | Stockage dans des variables d'environnement |
| Filtrage IP desactive | Le filtrage par adresse IP est commente (desactive) en developpement | Activer en production apres configuration |
| Pas de chiffrement de la base de donnees | Les donnees des capteurs sont stockees en clair dans MySQL | Activer le chiffrement au repos de MySQL (TDE) |
| Absence de journalisation centralisee | Les logs d'erreurs sont ecrits dans le log PHP par defaut | Mettre en place un systeme de journalisation centralise (ELK Stack) |

### V.6.2 Limites architecturales

- **Absence de certificat SSL en local** : en environnement de developpement (XAMPP localhost), HTTPS n'est pas actif par defaut. Les cookies `secure` ne sont donc pas actives.
- **Pas de systeme de roles** : tous les utilisateurs authentifies ont le meme niveau d'acces. Un systeme de roles (administrateur, technicien, lecteur) ameliorerait le controle d'acces.
- **Pas de mise a jour OTA securisee** : l'Arduino ne dispose pas de mecanisme de mise a jour a distance securise (Over-The-Air).
- **Session stockee cote serveur** : la gestion des sessions PHP est locale au serveur. En cas de montee en charge (plusieurs serveurs), un systeme de sessions distribuees (Redis) serait necessaire.

### V.6.3 Recommandations pour la production

1. **Activer HTTPS** avec un certificat Let's Encrypt (gratuit)
2. **Deplacer les credentials** dans des variables d'environnement (`.env`)
3. **Activer le filtrage IP** pour les endpoints Arduino
4. **Ajouter un systeme de roles** pour differencier les niveaux d'acces
5. **Remplacer l'Arduino** par un ESP32 capable de gerer TLS nativement
6. **Mettre en place un WAF** (Web Application Firewall) en frontal
7. **Auditer regulierement** le code avec des outils d'analyse statique (SonarQube, PHPStan)
