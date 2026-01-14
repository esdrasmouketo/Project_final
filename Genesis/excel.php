<?php
  // Paramètres de connexion à la base de données
  $DB_TBLName = "table_capteurs"; // Nom de la table
  $xls_filename = 'Serre_Rapport_Du_' . date('d-m-Y') . '.csv'; // Nom du fichier CSV

  // Connexion à la base de données
  $conn = new mysqli("localhost", "root", "", "ardbd");

  // Vérification de la connexion
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Définir l'encodage de la base de données pour éviter les problèmes de caractères
  $conn->query("SET NAMES 'utf8'");

  // Récupérer les données de la table
  $sql = "SELECT * FROM $DB_TBLName";
  $result = $conn->query($sql);

  // Vérifier si des résultats sont trouvés
  if ($result && $result->num_rows > 0) {
    // Récupérer les noms des colonnes
    $fields_Name = [];
    $finfo = $result->fetch_fields();
    foreach ($finfo as $field) {
      $fields_Name[] = $field->name;
    }

    // Paramètres pour le téléchargement du fichier CSV
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=$xls_filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Ajouter un BOM pour la compatibilité avec Excel (support UTF-8)
    echo chr(0xEF) . chr(0xBB) . chr(0xBF);

    // Écrire les noms des colonnes
    echo implode(',', $fields_Name) . "\n";

    // Écrire les données des lignes
    while ($row = $result->fetch_assoc()) {
      $csv_row = [];
      foreach ($fields_Name as $field) {
        $value = isset($row[$field]) ? $row[$field] : null;
        $csv_row[] = $value !== null ? '"' . str_replace('"', '""', $value) . '"' : '';
      }
      echo implode(',', $csv_row) . "\n";
    }

    // Libérer la mémoire de la requête
    $result->free();

    // Supprimer le contenu de la table après génération du fichier CSV
    $delete_sql = "DELETE FROM $DB_TBLName";
    if ($conn->query($delete_sql) === TRUE) {
      // Optionnel : vous pouvez ajouter une confirmation de la suppression ici si nécessaire
      // echo "Les données ont été supprimées de la base de données.";
    } else {
      // Optionnel : gérer les erreurs de suppression
      // echo "Erreur de suppression des données: " . $conn->error;
    }

  } else {
    // Gérer le cas où aucune donnée n'est trouvée
    echo "Aucune donnée à exporter.";
  }

  // Fermer la connexion
  $conn->close();
?>
