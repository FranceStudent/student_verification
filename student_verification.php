<?php

/**
 * WHMCS SDK Sample Addon Module
 *
 * An addon module allows you to add additional functionality to WHMCS. It
 * can provide both client and admin facing user interfaces, as well as
 * utilise hook functionality within WHMCS.
 *
 * This sample file demonstrates how an addon module for WHMCS should be
 * structured and exercises all supported functionality.
 *
 * Addon Modules are stored in the /modules/addons/ directory. The module
 * name you choose must be unique, and should be all lowercase, containing
 * only letters & numbers, always starting with a letter.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "addonmodule" and therefore all functions
 * begin "student_verification_".
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/addon-modules/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

/**
 * Require any libraries needed for the module to function.
 * require_once __DIR__ . '/path/to/library/loader.php';
 *
 * Also, perform any initialization required by the service's library.
 */

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define addon module configuration parameters.
 *
 * Includes a number of required system fields including name, description,
 * author, language and version.
 *
 * Also allows you to define any configuration parameters that should be
 * presented to the user when activating and configuring the module. These
 * values are then made available in all module function calls.
 *
 * Examples of each and their possible configuration parameters are provided in
 * the fields parameter below.
 *
 * @return array
 */
function student_verification_config()
{
    return [
        // Display name for your module
        'name' => 'Student Verification',
        // Description displayed within the admin interface
        'description' => 'Ce module permet de vérifier le statut d\'étudiant des utilisateurs',
        // Module author name
        'author' => '<a href="https://linkedin.com/in/loanfrancois/" target="_blank">LoanF</a>',
        // Default language
        'language' => 'french',
        // Version number
        'version' => '2.2',
        'fields' => [
            'method' => [
                'FriendlyName' => 'Méthode de vérification',
                'Type' => 'radio',
                'Options' => 'Manuelle,SheerID',
                'Description' => 'Méthode de vérification des étudiants',
            ],
            'upload_dir' => [
                'FriendlyName' => 'Manuelle - Répertoire d\'upload',
                'Type' => 'text',
                'Size' => '50',
                'Description' => '<br>Répertoire où les documents des étudiants seront stockés (ne doit pas être accessible publiquement)',
            ],
            'sheerid_client_id' => [
                'FriendlyName' => 'SheerID - Client ID',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Client ID de SheerID',
            ],
            'sheerid_access_token' => [
                'FriendlyName' => 'SheerID - Access Token',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Access Token de SheerID',
            ],
            'website_link' => [
                'FriendlyName' => 'SheerID - Lien du site',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Lien du site web de votre entreprise',
            ],
            'program_id' => [
                'FriendlyName' => 'SheerID - ID du programme',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'ID du programme SheerID',
            ],
            'information' => [
                'FriendlyName' => 'Information',
                'Description' => '<small>Ajoutez {$showStudentVerification} et {$alertStudentVerification} dans clientareahome.tpl & clientareadetails.tpl</small>'
            ],
            'expiration_date' => [
                'FriendlyName' => 'Date d\'expiration',
                'Type' => 'text',
                'Size' => '3',
                'Description' => '<br>Date d\'expiration de la vérification de l\'étudiant (format : DD-MM), l\'année est automatiquement celle de l\'année en cours',
            ],
        ]
    ];
}

/**
 * Activate.
 *
 * Called upon activation of the module for the first time.
 * Use this function to perform any database and schema modifications
 * required by your module.
 *
 * This function is optional.
 *
 * @see https://developers.whmcs.com/advanced/db-interaction/
 *
 * @return array Optional success/failure message
 */
function student_verification_activate()
{
    // Create custom tables and schema required by your module
    try {
        if (!Capsule::schema()->hasTable('mod_student_verification')) {
            Capsule::schema()->create(
                'mod_student_verification',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->string('student_id');
                    $table->string('document');
                    $table->boolean('verified')->nullable();
                    $table->string('reason')->nullable();
                    $table->integer('verificateur_id')->nullable();
                    $table->timestamps();
                }
            );
        }

        if (!Capsule::schema()->hasTable('mod_student_verification_logs')) {
            Capsule::schema()->create(
                'mod_student_verification_logs',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->integer('verification_id');
                    $table->integer('verificateur_id');
                    $table->boolean('verified');
                    $table->string('reason')->nullable();
                    $table->timestamps();
                }
            );
        }

        if (!Capsule::schema()->hasColumn('tblclients', 'hasSeenContent')) {
            Capsule::schema()->table(
                'tblclients',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->boolean('hasSeenContent')->default(false);
                }
            );
        }

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'Student Verification Module Activated Successfully',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            'status' => "error",
            'description' => 'Unable to create tables : ' . $e->getMessage(),
        ];
    }
}

/**
 * Deactivate.
 *
 * Called upon deactivation of the module.
 * Use this function to perform any required cleanup of your module data.
 *
 * This function is optional.
 *
 * @see https://developers.whmcs.com/advanced/db-interaction/
 *
 * @return array Optional success/failure message
 */
function student_verification_deactivate()
{
    // Undo any database and schema modifications made by your module here
    try {
        // Capsule::schema()->dropIfExists('mod_student_verification');

        // Capsule::schema()->table(
        //     'tblclients',
        //     function ($table) {
        //         /** @var \Illuminate\Database\Schema\Blueprint $table */
        //         $table->dropColumn('hasSeenContent');
        //     }
        // );

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'Student Verification Module Deactivated Successfully',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            'status' => "error",
            'description' => 'Unable to drop tables : ' . $e->getMessage(),
        ];
    }
}

function student_verification_output($vars)
{
    $modulelink = $vars['modulelink'];


    $output = '<div class="row" style="margin-bottom: 20px;">';
    $output .= '<div class="col-md-6">';
    $output .= '<h1>Recherche d\'utilisateurs par identifiant</h1>';
    $output .= '<div class="form-row">';
    $output .= '<div class="col-md-8 col-sm-12 mb-2">';
    $output .= '<input type="text" class="form-control" id="studentId" name="studentId" placeholder="Identifiant de l\'étudiant">';
    $output .= '</div>';
    $output .= '<div class="col-md-4 col-sm-12 w-100">';
    $output .= '<a class="btn btn-success" style="width:100%;" id="searchButton" href="javascript:void(0);"><i class="fas fa-search"></i> Rechercher</a>';
    $output .= '</div>';

    $output .= <<<HTML
    <script>
    var modulelink = "{$modulelink}";
    document.getElementById("searchButton").addEventListener("click", function(event) {
    var studentId = document.getElementById("studentId").value;
    if(studentId) {
        window.location.href = modulelink + "&action=search&studentId=" + studentId;
    }
    });
    </script>
    HTML;

    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = $_GET['id'];

        if ($_GET['action'] == 'verif') {
            Capsule::table('mod_student_verification')->where('student_id', $id)->delete();
            Capsule::table('mod_student_verification')->insert(['student_id' => $id, 'document' => '', 'verified' => true, 'reason' => 'Vérification admin', 'verificateur_id' => $_SESSION['adminid'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            Capsule::table('mod_student_verification_logs')->insert(['verification_id' => $id, 'verificateur_id' => $_SESSION['adminid'], 'verified' => false, 'reason' => 'Vérification manuelle', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            Capsule::table('tblclients')->where('id', $id)->update(['hasSeenContent' => true]);
        } elseif ($_GET['action'] == 'delete') {
            Capsule::table('mod_student_verification')->where('student_id', $id)->delete();
            Capsule::table('mod_student_verification_logs')->insert(['verification_id' => $id, 'verificateur_id' => $_SESSION['adminid'], 'verified' => false, 'reason' => 'Vérification rejetée', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            Capsule::table('tblclients')->where('id', $id)->update(['hasSeenContent' => false]);
        }

        $verification = Capsule::table('mod_student_verification')->where('id', $id)->first();

        if ($verification) {
            if ($_GET['action'] == 'approve') {
                Capsule::table('mod_student_verification')->where('id', $id)->update(['verified' => true, 'verificateur_id' => $_SESSION['adminid'], 'updated_at' => date('Y-m-d H:i:s')]);
                Capsule::table('mod_student_verification_logs')->insert(['verification_id' => $id, 'verificateur_id' => $_SESSION['adminid'], 'verified' => true, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
            } elseif ($_GET['action'] == 'reject') {
                $reason = $_GET['reason'];
                Capsule::table('mod_student_verification')->where('id', $id)->update(['verified' => false, 'reason' => $reason, 'verificateur_id' => $_SESSION['adminid'], 'updated_at' => date('Y-m-d H:i:s')]);
                Capsule::table('mod_student_verification_logs')->insert(['verification_id' => $id, 'verificateur_id' => $_SESSION['adminid'], 'verified' => false, 'reason' => $reason, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                echo json_encode(['success' => true]);
            }

            $documentPath = $verification->document;
            if (file_exists($documentPath)) {
                unlink($documentPath);
            }
        }

        header('Location: ' . $modulelink);
    }

    if (isset($_GET['studentId'])) {
        $studentId = $_GET['studentId'];
        $student = Capsule::table('mod_student_verification')
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($student) {
            if ($student->verified) {
                $output .= "<span class='text-success col-md-8 mb-2'><i class='fas fa-check'></i> L'étudiant avec l'ID $studentId est vérifié.</br><a class='text-danger' href='". $modulelink ."&action=delete&id=$studentId'><i class='fas fa-times'></i> Supprimer</a></span>";
            } else if ($student->verified === 0) {
                $output .= "<span class='text-danger col-md-8 mb-2'><i class='fas fa-times'></i> L'étudiant avec l'ID $studentId a été rejeté pour : " . $student->reason . "</br><a class='text-success' href='". $modulelink ."&action=verif&id=$studentId'><i class='fas fa-check'></i> Vérifier</a></span>";
            } else {
                $output .= "<span class='text-warning col-md-8'><i class='fas fa-exclamation-triangle'></i> L'étudiant avec l'ID $studentId est en attente.</br><a class='text-success' href='". $modulelink ."&action=verif&id=$studentId'><i class='fas fa-check'></i> Vérifier</a> | <a class='text-danger' href='". $modulelink ."&action=delete&id=$studentId'><i class='fas fa-times'></i> Supprimer</a></span>";
            }
        } else {
            $output .= "<span class='text-danger col-md-8 mb-2'><i class='fas fa-times'></i> L'étudiant avec l'ID $studentId n'a pas soumis de document.</br><a class='text-success' href='". $modulelink ."&action=verif&id=$studentId'><i class='fas fa-check'></i> Vérifier</a></span>";
        }
    }

    $output .= '</div></div>';

    $verificationCount = Capsule::table('mod_student_verification')->where('verified', true)->count();

    $output .= '<div class="col-md-6 d-flex justify-content-center">';
    $output .= '<div class="card text-center">';
    $output .= '<p class="card-title text-success" style="font-size: 30px; margin-bottom: 0; margin-top: 20px;">' . $verificationCount . '</p>';
    $output .= '<p class="card-title text-success" style="font-size: 12px;">Vérifications approuvées</p>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    $perPage = 10; // Nombre d'entrées par page
    $totalEntries = Capsule::table('mod_student_verification')->where('verified', null)->count(); // Nombre total d'entrées
    $totalPages = ceil($totalEntries / $perPage); // Nombre total de pages
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Page actuelle
    $start = ($page - 1) * $perPage; // Calculer le point de départ pour la requête

    // Récupérer toutes les vérifications avec les informations des utilisateurs
    $verifications = Capsule::table('mod_student_verification')
        ->join('tblclients', 'mod_student_verification.student_id', '=', 'tblclients.id')
        ->select('mod_student_verification.*', 'tblclients.firstname', 'tblclients.lastname')
        ->where('verified', null)
        ->skip($start)
        ->take($perPage)
        ->get();

    $output .= '<h1 class="mb-4">Approbation des documents des étudiants - <span class="text-muted"> ' . count($verifications) . ' en attente</span></h1>';

    $output .= '<div class="form-group">';
    $output .= '<label for="filterId">Filtrer par ID :</label>';
    $output .= '<input type="text" class="form-control" id="filterId" name="filterId" placeholder="Entrez l\'ID">';
    $output .= '</div>';

    $output .= '<div class="table-responsive">';
    $output .= '<table class="table table-striped">';
    $output .= '<thead class="thead-dark">';
    $output .= '<tr><th>ID utilisateur</th><th>Nom d\'utilisateur</th><th>Document envoyé</th><th>Action</th></tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    $output .= <<<HTML
    <script>
        document.getElementById("filterId").addEventListener("input", function(event) {
            var filterId = event.target.value;
            var rows = document.querySelectorAll("table tbody tr");
            rows.forEach(function(row) {
                var cell = row.querySelector("td:first-child");
                if (cell) {
                var id = cell.textContent;
                if (filterId === "" || id === filterId) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
                }
            });
        });
    </script>
    HTML;

    foreach ($verifications as $verification) {
        $output .= '<tr>';
        $output .= '<td>' . $verification->student_id . '</td>';
        $output .= '<td>' . $verification->firstname . ' ' . $verification->lastname . '</td>';

        $document = pathinfo($verification->document, PATHINFO_BASENAME);
        $output .= '<td><a class="my-auto" target="_blank" href="/modules/addons/student_verification/download.php?file=' . urlencode($verification->id) . '">' . $document . '</a></td>';

        $output .= '<td>';
        $output .= '<a class="btn btn-success my-auto" href="' . $modulelink . '&action=approve&id=' . $verification->id . '"><i class="fas fa-check"></i> Approuver</a>';
        $output .= ' <a class="btn btn-danger my-auto reject-btn" data-id="' . $verification->id . '" href="#"><i class="fas fa-times"></i> Rejeter</a>';
        $output .= '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table></div>';

    $output .= '<nav aria-label="Page navigation example">';
    $output .= '<ul class="pagination">';
    if ($page > 1) {
        $output .= '<li class="page-item"><a class="page-link" href="' . $modulelink . '&page=' . ($page - 1) . '">Précédent</a></li>';
    }
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $page == $i ? 'active' : '';
        $output .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $modulelink . '&page=' . $i . '">' . $i . '</a></li>';
    }
    if ($page < $totalPages) {
        $output .= '<li class="page-item"><a class="page-link" href="' . $modulelink . '&page=' . ($page + 1) . '">Suivant</a></li>';
    }
    $output .= '</ul>';
    $output .= '</nav>';

    $output .= <<<HTML
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script>
        $(document).ready(function() {
            $('.reject-btn').click(function(e) {
                e.preventDefault();

                var id = $(this).data('id');

                Swal.fire({
                    title: 'Rejet de la vérification',
                    input: 'text',
                    inputPlaceholder: 'Veuillez entrer la raison du refus',
                    showCancelButton: true,
                    confirmButtonText: 'Rejeter',
                    cancelButtonText: 'Annuler',
                    showLoaderOnConfirm: true,
                    preConfirm: (reason) => {
                        if (reason) {
                            return $.ajax({
                                url: modulelink,
                                type: 'GET',
                                data: {
                                    module: 'student_verification',
                                    action: 'reject',
                                    id: id,
                                    reason: reason
                                }
                            }).done(function(response) {
                                return location.reload();
                            }).fail(function(jqXHR, textStatus, errorThrown) {
                                console.log(jqXHR.status); // Affiche le statut HTTP
                                console.log(jqXHR.responseText); // Affiche le texte de la réponse
                                Swal.showValidationMessage(`Request failed: ${textStatus}`);
                            }).always(function() {
                                Swal.close();
                            });
                        }
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            });
        });
    </script>
    HTML;

    echo $output;
}
