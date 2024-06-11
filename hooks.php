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

// Page d'accueil => Manuelle
add_hook('ClientAreaPage', 1, function ($vars) {

    if (Capsule::table('tbladdonmodules')->where('module', 'student_verification')->where('setting', 'method')->first()->value === 'Manuelle') {

        // Récupérez l'ID du client actuellement connecté.
        $clientId = $_SESSION['uid'];

        // Récupérez l'information de la base de données.
        $result = Capsule::table('tblclients')->where('id', $clientId)->first();

        if ($result) {
            if (!$result->hasSeenContent) {
                // L'utilisateur n'a pas encore vu le contenu.
                $showStudentVerification = <<<HTML
            <div id="alert" class="alert" style="display: none;"></div>
            <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-center">
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
                        <p class="text-center" style="font-size: 0.8rem;">Nous vous rappelons que l'usage de faux documents est passible de poursuites judiciaires.</p>
                        <p class="text-center" style="font-size: 0.9rem;">Notre équipe vérifiera votre document et vous informera dès que possible de l'approbation de votre compte.</p>
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
                            $('#alert').addClass('alert-success').text('Votre demande de vérification a été envoyée avec succès.').show();
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
            } else {
                $verification = Capsule::table('mod_student_verification')
                    ->select('*')
                    ->where('student_id', $clientId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($verification) {
                    if ($verification->verified === null) {
                        $alertStudentVerification = <<<HTML
                    <div class="alert alert-warning" role="alert">
                        Votre demande est actuellement en cours de vérification. 
                    </div>
                    HTML;

                        return [
                            'showStudentVerification' => $alertStudentVerification,
                        ];
                    } else if ($verification->verified === 0) {
                        $reason = $verification->reason;
                        $alertStudentVerification = <<<HTML
                    <div class="alert alert-danger" id="alertValidation" role="alert">
                        Votre demande de vérification a été refusée.
                        <a href="#" data-toggle="modal" data-target="#myModal">Cliquez ici</a> pour soumettre un nouveau document.
                        </br>Raison : <strong>{$reason}</strong>
                    </div>
                    <div id="alert" class="alert" style="display: none;"></div>
                    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                            <div class="modal-header d-flex justify-content-between align-items-center">
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
                                    <p class="text-center" style="font-size: 0.8rem;">Nous vous rappelons que l'usage de faux documents est passible de poursuites judiciaires.</p>
                                    <p class="text-center" style="font-size: 0.9rem;">Notre équipe vérifiera votre document et vous informera dès que possible de l'approbation de votre compte.</p>
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
                            'showStudentVerification' => $alertStudentVerification,
                        ];
                    }
                }
            }
        }
    }
});

// Page de profil => Manuelle
add_hook('ClientAreaPageProfile', 1, function ($vars) {

    if (Capsule::table('tbladdonmodules')->where('module', 'student_verification')->where('setting', 'method')->first()->value === 'Manuelle') {

        // Récupérez l'ID du client actuellement connecté.
        $clientId = $_SESSION['uid'];

        // Récupérez l'information de la base de données.
        $result = Capsule::table('tblclients')->where('id', $clientId)->first();

        if ($result) {
            if (!$result->hasSeenContent) {
                // L'utilisateur n'a pas encore vu le contenu.
                $alertStudentVerification = <<<HTML
            <div id="alertValidation" class="alert alert-warning" role="alert">
                Vous devez valider votre statut étudiant pour accéder à nos services. <a href="#" data-toggle="modal" data-target="#myModal">Cliquez ici</a> pour plus d'informations.
            </div>
            <div id="alert" class="alert" style="display: none;"></div>
            <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-center">
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
                        <p class="text-center" style="font-size: 0.8rem;">Nous vous rappelons que l'usage de faux documents est passible de poursuites judiciaires.</p>
                        <p class="text-center" style="font-size: 0.9rem;">Notre équipe vérifiera votre document et vous informera dès que possible de l'approbation de votre compte.</p>
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
            } else {
                $verification = Capsule::table('mod_student_verification')
                    ->select('*')
                    ->where('student_id', $clientId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($verification) {
                    if ($verification->verified === null) {
                        $alertStudentVerification = <<<HTML
                    <div class="alert alert-warning" role="alert">
                        Votre demande est actuellement en cours de vérification. 
                    </div>
                    HTML;

                        return [
                            'alertStudentVerification' => $alertStudentVerification,
                        ];
                    } else if ($verification->verified === 0) {
                        $reason = $verification->reason;
                        $alertStudentVerification = <<<HTML
                    <div class="alert alert-danger" role="alert">
                        Votre demande de vérification a été refusée.
                        <a href="#" data-toggle="modal" data-target="#myModal">Cliquez ici</a> pour soumettre un nouveau document.
                        </br>Raison : <strong>{$reason}</strong>
                    </div>
                    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                            <div class="modal-header d-flex justify-content-between align-items-center">
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
                                    <p class="text-center" style="font-size: 0.8rem;">Nous vous rappelons que l'usage de faux documents est passible de poursuites judiciaires.</p>
                                    <p class="text-center" style="font-size: 0.9rem;">Notre équipe vérifiera votre document et vous informera dès que possible de l'approbation de votre compte.</p>
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
                    } else if ($verification->verified === 1) {
                        $expirationDate = Capsule::table('tbladdonmodules')
                            ->where('module', 'student_verification')
                            ->where('setting', 'expiration_date')
                            ->first();
                        $alertStudentVerification = <<<HTML
                    <div class="alert alert-success" role="alert">
                        Votre compte étudiant est vérifié jusqu'au {$expirationDate->value}.
                    </div>
                    HTML;

                        return [
                            'alertStudentVerification' => $alertStudentVerification,
                        ];
                    } else {
                        $alertStudentVerification = <<<HTML
                    <div class="alert alert-warning" role="alert">
                        Il y a eu une erreur lors de la vérification de votre statut étudiant. Veuillez contacter le support.
                    </div>
                    HTML;

                        return [
                            'alertStudentVerification' => $alertStudentVerification,
                        ];
                    }
                }
            }
        };
    };
});

// Page d'accueil => SheerID
add_hook('ClientAreaPage', 1, function ($vars) {

    if (Capsule::table('tbladdonmodules')->where('module', 'student_verification')->where('setting', 'method')->first()->value === 'SheerID') {

        // Récupérez l'ID du client actuellement connecté.
        $clientId = $_SESSION['uid'];

        // Récupérez l'information de la base de données.
        $result = Capsule::table('tblclients')->where('id', $clientId)->first();
        $programId = Capsule::table('tbladdonmodules')->where('module', 'student_verification')->where('setting', 'program_id')->first()->value;

        if ($result) {
            if (!$result->hasSeenContent) {
                // L'utilisateur n'a pas encore vu le contenu.
                $showStudentVerification = <<<HTML
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.css" type="text/css"/>
                    <script src="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.js"></script>
                    <script>
                    $(document).ready(function() {
                        Capsule::table('tblclients')->where('id', $clientId)->update(['hasSeenContent' => 1]);
                        const verificationUrl = `https://services.sheerid.com/verify/${programId}/?clientId=${clientId}`;
                        sheerId.loadInModal(
                            verificationUrl, {
                            mobileRedirect: true,
                        });
                    });
                    </script>
                HTML;

                return [
                    'showStudentVerification' => $showStudentVerification,
                ];
            } else {
                $verification = Capsule::table('mod_student_verification')
                    ->select('*')
                    ->where('student_id', $clientId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($verification) {
                    if ($verification->verified === null) {
                        $alertStudentVerification = <<<HTML
                        <div class="alert alert-warning" role="alert">
                            Votre demande est actuellement en cours de vérification par SheerID.
                        </div>
                        HTML;

                        return [
                            'showStudentVerification' => $alertStudentVerification,
                        ];
                    } else if ($verification->verified === 0) {
                        $alertStudentVerification = <<<HTML
                        <div class="alert alert-danger" id="alertValidation" role="alert">
                            Votre demande de vérification a été refusée par SheerID.
                            <a onclick="displayVerification()" class="alert-link">Cliquez ici</a> pour soumettre à nouveau votre demande.
                            </br>Raison : <strong>{$verification->reason}</strong>
                            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.css" type="text/css"/>
                            <script src="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.js"></script>
                            <script>
                            function displayVerification() {
                                const verificationUrl = `https://services.sheerid.com/verify/${programId}/?clientId=${clientId}`;
                                sheerId.loadInModal(
                                    verificationUrl, {
                                    mobileRedirect: true
                                });
                            };
                            </script>
                        </div>
                        HTML;

                        return [
                            'showStudentVerification' => $alertStudentVerification,
                        ];
                    }
                }
            }
        }
    }
});

// Page de profil => SheerID
add_hook('ClientAreaPageProfile', 1, function ($vars) {

    if (Capsule::table('tbladdonmodules')->where('module', 'student_verification')->where('setting', 'method')->first()->value === 'Manuelle') {

        // Récupérez l'ID du client actuellement connecté.
        $clientId = $_SESSION['uid'];

        // Récupérez l'information de la base de données.
        $result = Capsule::table('tblclients')->where('id', $clientId)->first();

        if ($result) {
            if (!$result->hasSeenContent) {
                // L'utilisateur n'a pas encore vu le contenu.
                $showStudentVerification = <<<HTML
                    <div id="alertValidation" class="alert alert-warning" role="alert">
                        Vous devez valider votre statut étudiant pour accéder à nos services. <a onclick="displayVerification()" class="alert-link">Cliquez ici</a> pour plus d'informations.
                    </div>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.css" type="text/css"/>
                    <script src="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.js"></script>
                    <script>
                    function displayVerification() {
                        const verificationUrl = `https://services.sheerid.com/verify/${programId}/?clientId=${clientId}`;
                        sheerId.loadInModal(
                            verificationUrl, {
                            mobileRedirect: true
                        });
                    };
                    </script>
                HTML;

                return [
                    'alertStudentVerification' => $alertStudentVerification,
                ];
            } else {
                $verification = Capsule::table('mod_student_verification')
                    ->select('*')
                    ->where('student_id', $clientId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($verification) {
                    if ($verification->verified === null) {
                        $alertStudentVerification = <<<HTML
                        <div class="alert alert-warning" role="alert">
                            Votre demande est actuellement en cours de vérification par SheerID.
                        </div>
                        HTML;

                        return [
                            'showStudentVerification' => $alertStudentVerification,
                        ];
                    } else if ($verification->verified === 0) {
                        $alertStudentVerification = <<<HTML
                        <div class="alert alert-danger" id="alertValidation" role="alert">
                            Votre demande de vérification a été refusée par SheerID.
                            <a onclick="displayVerification()" class="alert-link">Cliquez ici</a> pour soumettre à nouveau votre demande.
                            </br>Raison : <strong>{$verification->reason}</strong>
                            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.css" type="text/css"/>
                            <script src="https://cdn.jsdelivr.net/npm/@sheerid/jslib@1/sheerid.js"></script>
                            <script>
                            function displayVerification() {
                                const verificationUrl = `https://services.sheerid.com/verify/${programId}/?clientId=${clientId}`;
                                sheerId.loadInModal(
                                    verificationUrl, {
                                    mobileRedirect: true
                                });
                            };
                            </script>
                        </div>
                        HTML;

                        return [
                            'showStudentVerification' => $alertStudentVerification,
                        ];
                    }
                }
            }
        };
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
            'abortmsg' => 'Vous devez valider votre statut étudiant pour accéder à nos services. <a href="clientarea.php?action=details">Cliquez ici</a> pour plus d\'informations.'
        ];
    }
});
