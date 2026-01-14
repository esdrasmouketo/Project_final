<?php
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$qrCode = new QrCode('http://yourserver.com/path_to_csv/Serre_Rapport_Du.csv');
$writer = new PngWriter();
$result = $writer->write($qrCode);

// Enregistrer le fichier QR Code sur le serveur
$result->saveToFile('qrcodes/Serre_Rapport_Du.png');

// Afficher le QR code dans le navigateur
header('Content-Type: '.$result->getMimeType());
echo $result->getString();
