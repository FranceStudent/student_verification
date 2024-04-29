<?php

/**
 * WHMCS SDK Sample Addon Module Hooks File
 *
 * Hooks allow you to tie into events that occur within the WHMCS application.
 *
 * This allows you to execute your own code in addition to, or sometimes even
 * instead of that which WHMCS executes by default.
 *
 * @see https://developers.whmcs.com/hooks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require any libraries needed for the module to function.
// require_once __DIR__ . '/path/to/library/loader.php';
//
// Also, perform any initialization required by the service's library.

use WHMCS\Database\Capsule;

/**
 * Register a hook with WHMCS.
 *
 * This sample demonstrates triggering a service call when a change is made to
 * a client profile within WHMCS.
 *
 * For more information, please refer to https://developers.whmcs.com/hooks/
 *
 * add_hook(string $hookPointName, int $priority, string|array|Closure $function)
 */

add_hook('ClientAreaPage', 1, function ($vars) {
    // Récupérez l'ID du client actuellement connecté.
    $clientId = $_SESSION['uid'];

    // Récupérez l'information de la base de données.
    $result = Capsule::table('tblclients')->where('id', $clientId)->first();

    if ($result && !$result->hasSeenContent) {
        $showStudentVerification = <<<HTML
        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Confirmation étudiante</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <form id="studentProofForm" method="post" enctype="multipart/form-data">
                    <p class="text-center font-weight-bold mb-4">Pour accéder à nos services, nous avons besoin de vérifier que vous êtes étudiant.</p>
                    <div class="row justify-content-center">
                        <label for="studentProof" class="col-form-label">Veuillez télécharger un documents prouvant votre statut d'étudiant :</label>
                        <input type="hidden" name="clientId" value="{$clientId}">
                        <input type="file" class="form-control-file col-md-6 m-4" id="studentProof" name="studentProof" accept=".jpg, .jpeg, .png, .gif, .pdf" required>
                    </div>
                    <p class="text-center">Notre équipe vérifiera votre document et vous informera dès que possible de l'approbation de votre compte.</p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="submit" form="studentProofForm" class="btn btn-primary">Envoyer</button>
            </div>
            </div>
        </div>
        </div>
        <script>
        $(document).ready(function() {
            $('#myModal').modal('show');

            $('#studentProofForm').on('submit', function(e) {
                e.preventDefault();

                var fileInput = $('#studentProof');
                var filePath = fileInput.val();
                var allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif|\.pdf)$/i; // change this to your allowed extensions

                if(!allowedExtensions.exec(filePath)){
                    alert('Veuillez télécharger un fichier avec une extension .jpg, .jpeg, .png, .gif, .pdf seulement.');
                    fileInput.val('');
                    return false;
                }

                $.ajax({
                    url: '/modules/addons/student_verification/handle_form.php',
                    type: 'post',
                    data: new FormData(this), // Envoyer les données du formulaire
                    processData: false,
                    contentType: false,
                    success: function() {
                        $('#myModal').modal('hide');
                    },
                    error: function() {
                        alert('Une erreur s\'est produite lors de l\'envoi du formulaire.');
                    }
                });
            });
        });
        </script>
        HTML;


        return [
            'showStudentVerification' => $showStudentVerification,
        ];
    }
});

add_hook('ClientAreaPageProfile', 1, function ($vars) {
    // Récupérez l'ID du client actuellement connecté.
    $clientId = $_SESSION['uid'];

    // Récupérez l'information de la base de données.
    $result = Capsule::table('tblclients')->where('id', $clientId)->first();

    if ($result && !$result->hasSeenContent) {
        $alertStudentVerification = <<<HTML
        <div id="alertValidation" class="alert alert-warning" role="alert">
            Vous devez valider votre statut étudiant pour accéder à nos services. <a href="#" data-toggle="modal" data-target="#myModal">Cliquez ici</a> pour plus d'informations.
        </div>
        <div id="alert" class="alert" style="display: none;"></div>
        <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Confirmation étudiante</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <form id="studentProofForm" method="post" enctype="multipart/form-data">
                    <p class="text-center font-weight-bold mb-4">Pour accéder à nos services, nous avons besoin de vérifier que vous êtes étudiant.</p>
                    <div class="row justify-content-center">
                        <label for="studentProof" class="col-form-label">Veuillez télécharger un documents prouvant votre statut d'étudiant :</label>
                        <input type="hidden" name="clientId" value="{$clientId}">
                        <input type="file" class="form-control-file col-md-6 m-4" id="studentProof" name="studentProof" accept=".jpg, .jpeg, .png, .gif, .pdf" required>                    
                    </div>
                    <p class="text-center">Notre équipe vérifiera votre document et vous informera dès que possible de l'approbation de votre compte.</p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="submit" form="studentProofForm" class="btn btn-primary">Envoyer</button>
            </div>
            </div>
        </div>
        </div>
        <script>
        $(document).ready(function() {
            $('#studentProofForm').on('submit', function(e) {
                e.preventDefault();

                var fileInput = $('#studentProof');
                var filePath = fileInput.val();
                var allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif|\.pdf)$/i;

                if(!allowedExtensions.exec(filePath)){
                    alert('Veuillez télécharger un fichier avec une extension .jpg, .jpeg, .png, .gif, .pdf seulement.');
                    fileInput.val('');
                    return false;
                }

                $.ajax({
                    url: '/modules/addons/student_verification/handle_form.php',
                    type: 'post',
                    data: new FormData(this), // Envoyer les données du formulaire
                    processData: false,
                    contentType: false,
                    success: function() {
                        $('#myModal').modal('hide');
                        $('#alertValidation').hide();
                        $('#alert').addClass('alert-success').text('Votre demande a été envoyée avec succès.').show();
                    },
                    error: function() {
                        alert('Une erreur s\'est produite lors de l\'envoi du formulaire.');
                    }
                });
            });
        });
        </script>
        HTML;

        return [
            'alertStudentVerification' => $alertStudentVerification,
        ];
    };
});

add_hook('ShoppingCartValidateCheckout', 1, function ($vars) {
    $clientId = $_SESSION['uid'];

    $result = Capsule::table('mod_student_verification')
        ->join('tblclients', 'tblclients.id', '=', 'mod_student_verification.student_id')
        ->where('tblclients.id', $clientId)
        ->where('tblclients.hasSeenContent', true)
        ->where('mod_student_verification.verified', true)
        ->first();

    if (!$result) {
        // L'utilisateur n'a pas de document validé. Vous pouvez rediriger l'utilisateur, afficher un message d'erreur, etc.
        return [
            'abortcheckout' => true,
            'abortmsg' => 'Vous devez valider votre statut étudiant pour accéder à nos services.',
        ];
    }
});
