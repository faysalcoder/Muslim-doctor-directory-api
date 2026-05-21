<?php
declare(strict_types=1);

/**
 * POST /doctors/public-apply.php
 * Public endpoint — no auth required.
 * Doctors submit their own listing; status is always forced to "pending"
 * so an admin must review before it goes live.
 */

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    error_response('Method not allowed', 405);
}

$pdo = db();

// ── Read fields ────────────────────────────────────────────────────────────
$name                      = trim((string)($_POST['name']                      ?? ''));
$title                     = trim((string)($_POST['title']                     ?? ''));
$academic_title            = trim((string)($_POST['academic_title']            ?? ''));
$medical_school_affiliation = trim((string)($_POST['medical_school_affiliation'] ?? ''));
$specialty                 = trim((string)($_POST['specialty']                 ?? ''));
$subspecialty              = trim((string)($_POST['subspecialty']              ?? ''));
$graduation_degree         = trim((string)($_POST['graduation_degree']         ?? ''));
$graduation_year           = (int)($_POST['graduation_year']                   ?? 0) ?: null;
$experience                = trim((string)($_POST['experience']                ?? ''));
$gender                    = trim((string)($_POST['gender']                    ?? ''));
$languages                 = trim((string)($_POST['languages']                 ?? ''));
$email                     = trim((string)($_POST['email']                     ?? ''));
$phone                     = trim((string)($_POST['phone']                     ?? ''));
$location                  = trim((string)($_POST['location']                  ?? ''));
$practice                  = trim((string)($_POST['practice']                  ?? ''));
$address                   = trim((string)($_POST['address']                   ?? ''));
$hospital_affiliations     = trim((string)($_POST['hospital_affiliations']     ?? ''));
$awards                    = trim((string)($_POST['awards']                    ?? ''));
$bio                       = trim((string)($_POST['bio']                       ?? ''));
$education                 = trim((string)($_POST['education']                 ?? ''));
$accepting                 = (int)($_POST['accepting_patients']                ?? 0);

// ── Validation ─────────────────────────────────────────────────────────────
$required = [
    'Full name'           => $name,
    'Specialty'           => $specialty,
    'Gender'              => $gender,
    'Languages'           => $languages,
    'Medical credentials' => $title,
    'Graduation degree'   => $graduation_degree,
    'Years of experience' => $experience,
    'Email address'       => $email,
    'Phone number'        => $phone,
    'Practice name'       => $practice,
    'City / location'     => $location,
    'Hospital 1'          => $hospital_affiliations,
    'About you'           => $bio,
];

foreach ($required as $fieldLabel => $value) {
    if ($value === '') {
        error_response("{$fieldLabel} is required", 422);
    }
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_response('Invalid email address', 422);
}

// Prevent duplicate email submissions
$dup = $pdo->prepare('SELECT id FROM doctors WHERE email = :email LIMIT 1');
$dup->execute([':email' => $email]);
if ($dup->fetch()) {
    error_response('A listing with this email already exists. Contact us if you need to update it.', 409);
}

// ── Generate unique doctor code ────────────────────────────────────────────
$doctorCode = 'NMP-' . strtoupper(bin2hex(random_bytes(3)));

// ── Profile image upload ───────────────────────────────────────────────────
$profileImage = null;
if (!empty($_FILES['profile_image']['name'])) {
    try {
        $profileImage = upload_single_image(
            $_FILES['profile_image'],
            __DIR__ . '/../uploads/doctors'
        );
    } catch (Throwable $e) {
        error_response('Profile image upload failed: ' . $e->getMessage(), 422);
    }
}

// ── Insert ─────────────────────────────────────────────────────────────────
$stmt = $pdo->prepare('
    INSERT INTO doctors
        (doctor_id, name, title, academic_title, medical_school_affiliation,
         specialty, subspecialty, graduation_degree, graduation_year,
         location, practice, address, hospital_affiliations,
         phone, email, bio, education, experience, gender, languages,
         awards, accepting_patients, profile_image, status)
    VALUES
        (:doctor_id, :name, :title, :academic_title, :medical_school_affiliation,
         :specialty, :subspecialty, :graduation_degree, :graduation_year,
         :location, :practice, :address, :hospital_affiliations,
         :phone, :email, :bio, :education, :experience, :gender, :languages,
         :awards, :accepting_patients, :profile_image, \'pending\')
');

try {
    $stmt->execute([
        'doctor_id'                  => $doctorCode,
        'name'                       => $name,
        'title'                      => $title,
        'academic_title'             => $academic_title,
        'medical_school_affiliation' => $medical_school_affiliation,
        'specialty'                  => $specialty,
        'subspecialty'               => $subspecialty,
        'graduation_degree'          => $graduation_degree,
        'graduation_year'            => $graduation_year,
        'location'                   => $location,
        'practice'                   => $practice,
        'address'                    => $address,
        'hospital_affiliations'      => $hospital_affiliations,
        'phone'                      => $phone,
        'email'                      => $email,
        'bio'                        => $bio,
        'education'                  => $education,
        'experience'                 => $experience,
        'gender'                     => $gender,
        'languages'                  => $languages,
        'awards'                     => $awards,
        'accepting_patients'         => $accepting,
        'profile_image'              => $profileImage,
    ]);
} catch (Throwable $e) {
    error_response('Failed to save application: ' . $e->getMessage(), 500);
}

json_response([
    'success' => true,
    'message' => 'Your application has been submitted and is pending review.',
    'data'    => ['doctor_id' => $doctorCode],
], 201);
