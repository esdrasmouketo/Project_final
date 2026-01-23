<?php
/**
 * Script de migration des mots de passe - GENESIS
 *
 * Ce script convertit les mots de passe en clair vers bcrypt.
 * À exécuter UNE SEULE FOIS après la mise à jour du code.
 *
 * IMPORTANT:
 * 1. Faites une sauvegarde de la base de données avant d'exécuter ce script
 * 2. Supprimez ce fichier après utilisation pour des raisons de sécurité
 *
 * Utilisation: php migrate_passwords.php
 * Ou accédez via navigateur (puis supprimez le fichier)
 */

// Sécurité: Ce script ne doit être exécuté qu'une fois
// Décommentez la ligne suivante si vous accédez via navigateur
// et commentez-la après utilisation
/*
if (php_sapi_name() !== 'cli') {
    die('Ce script doit être exécuté en ligne de commande ou décommentez la protection.');
}
*/

require_once __DIR__ . '/config.php';

echo "=== Migration des mots de passe GENESIS ===\n\n";

try {
    $conn = getDBConnection();

    // Récupérer tous les utilisateurs
    $stmt = $conn->query("SELECT id, mot_de_passe FROM techniciens");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "Aucun utilisateur trouvé dans la table 'agent'.\n";
        exit(0);
    }

    echo "Nombre d'utilisateurs trouvés: " . count($users) . "\n\n";

    $migrated = 0;
    $already_hashed = 0;
    $errors = 0;

    foreach ($users as $user) {
        $id = $user['id'];
        $password = $user['mot_de_passe'];

        // Vérifier si le mot de passe est déjà haché (bcrypt commence par $2y$)
        if (preg_match('/^\$2[ayb]\$.{56}$/', $password)) {
            echo "[$id] Déjà haché - ignoré\n";
            $already_hashed++;
            continue;
        }

        // Le mot de passe est en clair, le hacher
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        if ($hashed === false) {
            echo "[$id] ERREUR: Impossible de hacher le mot de passe\n";
            $errors++;
            continue;
        }

        // Mettre à jour le mot de passe
        $updateStmt = $conn->prepare("UPDATE techniciens SET mot_de_passe = ? WHERE id = ?");

        if ($updateStmt->execute([$hashed, $id])) {
            echo "[$id] Migré avec succès\n";
            $migrated++;
        } else {
            echo "[$id] ERREUR: Impossible de mettre à jour\n";
            $errors++;
        }
    }

    echo "\n=== Résumé ===\n";
    echo "Mots de passe migrés: $migrated\n";
    echo "Déjà hachés (ignorés): $already_hashed\n";
    echo "Erreurs: $errors\n";
    echo "\nMigration terminée.\n";

    if ($migrated > 0) {
        echo "\n*** IMPORTANT: Supprimez ce fichier (migrate_passwords.php) pour des raisons de sécurité! ***\n";
    }

} catch (PDOException $e) {
    echo "ERREUR de base de données: " . $e->getMessage() . "\n";
    exit(1);
}
?>
