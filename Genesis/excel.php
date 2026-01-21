<?php
/**
 * Export CSV sécurisé - GENESIS
 * ATTENTION: Cette opération supprime les données après export
 */
require_once __DIR__ . '/auth_check.php';

$conn = getDBConnection();

$DB_TBLName = "table_capteurs";
$xls_filename = 'Serre_Rapport_Du_' . date('d-m-Y') . '.csv';

try {
    // Définir l'encodage
    $conn->exec("SET NAMES 'utf8'");

    // Récupérer les données de la table
    $stmt = $conn->query("SELECT * FROM $DB_TBLName");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérifier si des résultats sont trouvés
    if (count($result) > 0) {
        // Récupérer les noms des colonnes
        $fields_Name = array_keys($result[0]);

        // Paramètres pour le téléchargement du fichier CSV
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=\"" . $xls_filename . "\"");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

        // Ajouter un BOM pour la compatibilité avec Excel (support UTF-8)
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);

        // Ouvrir le flux de sortie
        $output = fopen('php://output', 'w');

        // Écrire les noms des colonnes
        fputcsv($output, $fields_Name);

        // Écrire les données des lignes
        foreach ($result as $row) {
            fputcsv($output, $row);
        }

        fclose($output);

        // Supprimer le contenu de la table après génération du fichier CSV
        // Note: En production, vous pourriez vouloir archiver plutôt que supprimer
        $conn->exec("DELETE FROM $DB_TBLName");

        // Logger l'action
        error_log("Export CSV effectué par " . ($_SESSION['username'] ?? 'unknown') . " - " . count($result) . " lignes exportées et supprimées");

        exit();

    } else {
        $_SESSION['error'] = "Aucune donnée à exporter.";
        header('Location: index.php');
        exit();
    }

} catch (PDOException $e) {
    error_log("Erreur export CSV: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de l'export des données.";
    header('Location: index.php');
    exit();
}
?>
