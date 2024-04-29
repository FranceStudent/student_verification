<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérez l'ID du client à partir du formulaire
    $clientId = $_POST['clientId'];

    // Vérifiez si le fichier a été téléchargé
    if (isset($_FILES['studentProof'])) {
        $file = $_FILES['studentProof'];
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            die('Extension de fichier non autorisée.');
        }

        $addonConfig = Capsule::table('tbladdonmodules')
            ->where('module', 'student_verification')
            ->where('setting', 'upload_dir')
            ->first();

        if ($addonConfig) {
            $uploadDir = $addonConfig->value;
        } else {
            die('Répertoire d\'upload non configuré.');
        }

        $uploadDir = '/home/loanf/workspaces/perso-workspace/uploads/';
        $uploadName = $clientId . '_' . date('YmdHis') . '_' . basename($file['name']);
        $uploadFile = $uploadDir . $uploadName;

        if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
            echo "Le fichier est valide et a été téléchargé avec succès.\n";
        } else {
            echo "Erreur lors du téléchargement du fichier.\n";
        }

        Capsule::table('mod_student_verification')->insert([
            'student_id' => $clientId,
            'document' => $uploadFile,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    // Mettez à jour la base de données pour indiquer que l'utilisateur a maintenant vu le contenu.
    Capsule::table('tblclients')->where('id', $clientId)->update(['hasSeenContent' => 1]);
}
