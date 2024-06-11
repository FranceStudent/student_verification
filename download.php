<?php
require_once '../../../init.php';

use WHMCS\Database\Capsule;

// Vérifiez si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['adminid'])) {
    die('Vous devez être connecté en tant qu\'administrateur pour accéder à ce fichier.');
}

// Vérifiez si le paramètre 'file' est présent dans l'URL
if (!isset($_GET['file'])) {
    die('Aucun fichier spécifié.');
}

// Récupérez le chemin du fichier à partir de la base de données
$fileId = $_GET['file'];
$file = Capsule::table('mod_student_verification')->where('id', $fileId)->first();

if (!$file) {
    die('Fichier non trouvé.');
}

$filePath = $file->document;

// Vérifiez si le fichier existe
if (!file_exists($filePath)) {
    die('Fichier non trouvé.');
}

// Récupérez le type de fichier pour le header Content-Type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Définissez les headers et servez le fichier
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
readfile($filePath);