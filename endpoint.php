<?php

require_once __DIR__ . '/../../../init.php';

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

$id = $_POST['userId'] ?? null;

$student = Capsule::table('mod_student_verification')
    ->where('student_id', $id)
    ->orderBy('id', 'desc')
    ->first();

if ($student) {
    $data = [
        'status' => 'success',
        'user' => get_object_vars($student)
    ];
} else {
    $data = ['status' => 'error'];
}

header('Content-Type: application/json');
echo json_encode($data);