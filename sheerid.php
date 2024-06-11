<?php

require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

$jsonInput = file_get_contents('php://input');

$data = json_decode($jsonInput);

if ($data && isset($data->verificationId)) {
    $verificationId = $data->verificationId;

    $url = "https://services.sheerid.com/rest/v2/verification/$verificationId/details";

    // Traiter le verificationId ici
    $sheerIdClient = Capsule::table('tbladdonmodules')
        ->where('module', 'student_verification')
        ->where('setting', 'sheerid_client_id')
        ->first()->value;

    $accessToken = Capsule::table('tbladdonmodules')
        ->where('module', 'student_verification')
        ->where('setting', 'sheerid_access_token')
        ->first()->value;

    $link = Capsule::table('tbladdonmodules')
        ->where('module', 'student_verification')
        ->where('setting', 'website_link')
        ->first()->value;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_REFERER, $link);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $accessToken,
    ]);

    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        echo 'Erreur cURL: ' . curl_error($ch);
    } else {
        $verif = json_decode($response);
        $state = $verif->lastResponse->currentStep;
        $clientId = $verif->personInfo->metadata->clientId;

        if ($state == 'success') 
        {
            Capsule::table('mod_student_verification')
                ->where('student_id', $clientId)
                ->delete();

            Capsule::table('mod_student_verification')->insert([
                'student_id' => $clientId,
                'document' => $verificationId,
                'verified' => true,
                'verificateur_id' => '0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        else if ($state == 'error')
        {
            Capsule::table('mod_student_verification')
                ->where('student_id', $clientId)
                ->delete();

            Capsule::table('mod_student_verification')->insert([
                'student_id' => $clientId,
                'document' => $verificationId,
                'verified' => false,
                'verificateur_id' => '0',
                'reason' => $verif->lastResponse->systemErrorMessage,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        Capsule::table('tblclients')->where('id', $clientId)->update(['hasSeenContent' => 1]);
    }

    curl_close($ch);

    // Réponse de succès
    http_response_code(200);
    echo json_encode(['message' => 'Verification ID received successfully']);
} else {
    // Réponse d'erreur si le JSON est invalide ou ne contient pas verificationId
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON or missing verificationId']);
}