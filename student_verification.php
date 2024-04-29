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
        'name' => 'FranceStudent - Student Verification',
        // Description displayed within the admin interface
        'description' => 'Ce module permet de vérifier le statut d\'étudiant des utilisateurs',
        // Module author name
        'author' => '<a href="https://linkedin.com/in/loanfrancois/" target="_blank">LoanF</a>',
        // Default language
        'language' => 'french',
        // Version number
        'version' => '1.0',
        'fields' => [
            'upload_dir' => [
                'FriendlyName' => 'Répertoire d\'upload',
                'Type' => 'text',
                'Size' => '50',
                'Description' => '<br>Répertoire où les documents des étudiants seront stockés (ne doit pas être accessible publiquement)',
            ],
            'information' => [
                'FriendlyName' => 'Information',
                'Description' => '<small>Ajoutez {$showStudentVerification} et {$alertStudentVerification} dans clientareahome.tpl & clientareadetails.tpl</small>'
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
        Capsule::schema()->create(
            'mod_student_verification',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->increments('id');
                $table->string('student_id');
                $table->string('document');
                $table->boolean('verified')->nullable();
                $table->timestamps();
            }
        );

        Capsule::schema()->table(
            'tblclients',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->boolean('hasSeenContent')->default(false);
            }
        );

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'Student Verification Module Activated Successfully',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            'status' => "error",
            'description' => 'Unable to create mod_student_verification_verification : ' . $e->getMessage(),
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
        Capsule::schema()->dropIfExists('mod_student_verification');

        Capsule::schema()->table(
            'tblclients',
            function ($table) {
                /** @var \Illuminate\Database\Schema\Blueprint $table */
                $table->dropColumn('hasSeenContent');
            }
        );

        return [
            // Supported values here include: success, error or info
            'status' => 'success',
            'description' => 'Student Verification Module Deactivated Successfully',
        ];
    } catch (\Exception $e) {
        return [
            // Supported values here include: success, error or info
            'status' => "error",
            'description' => 'Unable to drop mod_student_verification_verification : ' . $e->getMessage(),
        ];
    }
}

function student_verification_output($vars)
{
    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $LANG = $vars['_lang'];

    // Récupérer toutes les vérifications avec les informations des utilisateurs
    $verifications = Capsule::table('mod_student_verification')
        ->join('tblclients', 'mod_student_verification.student_id', '=', 'tblclients.id')
        ->select('mod_student_verification.*', 'tblclients.firstname', 'tblclients.lastname')
        ->where('verified', null)
        ->get();

    if (isset($_GET['action']) && isset($_GET['id'])) {
        $id = $_GET['id'];
        $verification = Capsule::table('mod_student_verification')->where('id', $id)->first();

        if ($verification) {
            if ($_GET['action'] == 'approve') {
                Capsule::table('mod_student_verification')->where('id', $id)->update(['verified' => true]);

                // localAPI('SendEmail', [
                //     'messagename' => 'Approval Email Template Name',
                //     'id' => $verification->student_id
                // ], 'adminusername');
            } elseif ($_GET['action'] == 'reject') {
                Capsule::table('mod_student_verification')->where('id', $id)->update(['verified' => false]);
                Capsule::table('tblclients')->where('id', $verification->student_id)->update(['hasSeenContent' => 0]);

                // localAPI('SendEmail', [
                //     'messagename' => 'Rejection Email Template Name',
                //     'id' => $verification->student_id
                // ], 'adminusername');
            }
        }

        header('Location: ' . $modulelink);
    }

    $output = '<h1 class="mb-4">Approbation des documents des étudiants</h1>';
    $output .= '<table class="table table-striped">';
    $output .= '<thead class="thead-dark">';
    $output .= '<tr><th>Nom d\'utilisateur</th><th>Document envoyé</th><th>Action</th></tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    foreach ($verifications as $verification) {
        $output .= '<tr>';
        $output .= '<td>' . $verification->firstname . ' ' . $verification->lastname . '</td>';

        $document = pathinfo($verification->document, PATHINFO_BASENAME);
        $output .= '<td><a class="my-auto" href="/modules/addons/student_verification/download.php?file=' . urlencode($verification->id) . '">' . $document . '</a></td>';

        $output .= '<td>';
        $output .= '<a class="btn btn-success my-auto" href="' . $modulelink . '&action=approve&id=' . $verification->id . '">Approuver</a>';
        $output .= ' <a class="btn btn-danger my-auto" href="' . $modulelink . '&action=reject&id=' . $verification->id . '">Rejeter</a>';
        $output .= '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';

    echo $output;
}
