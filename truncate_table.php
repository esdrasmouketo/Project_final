<?php
/**
 * Script temporaire pour vider table_capteurs
 * À SUPPRIMER après utilisation
 */
$conn = new mysqli("localhost", "root", "", "ardbd");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

$result = $conn->query("TRUNCATE TABLE table_capteurs");

if ($result) {
    echo "<h2 style='color:green;font-family:Arial;'>✅ Table 'table_capteurs' vidée avec succès !</h2>";
    echo "<p style='font-family:Arial;'>L'auto-increment a été remis à 1.</p>";
    echo "<p style='font-family:Arial;color:red;font-weight:bold;'>⚠️ SUPPRIMEZ ce fichier (truncate_table.php) maintenant !</p>";
} else {
    echo "<h2 style='color:red;font-family:Arial;'>❌ Erreur : " . $conn->error . "</h2>";
}

$conn->close();
?>
