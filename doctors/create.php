<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$pdo = db();

$name                      = trim((string)($_POST['name']                      ?? ''));
$specialty                 = trim((string)($_POST['specialty']                 ?? ''));
$email                     = trim((string)($_POST['email']                     ?? ''));
$phone                     = trim((string)($_POST['phone']                     ?? ''));
$location                  = trim((string)($_POST['location']                  ?? ''));
$title                     = trim((string)($_POST['title']                     ?? ''));
$academic_title            = trim((string)($_POST['academic_title']            ?? ''));
$medical_school_affiliation= trim((string)($_POST['medical_school_affiliation']?? ''));
$subspecialty              = trim((string)($_POST['subspecialty']              ?? ''));
$graduation_degree         = trim((string)($_POST['graduation_degree']         ?? ''));
$graduation_year           = (int)($_POST['graduation_year'] ?? 0) ?: null;
$practice                  = trim((string)($_POST['practice']                  ?? ''));
$address                   = trim((string)($_POST['address']                   ?? ''));
$hospital_affiliations     = trim((string)($_POST['hospital_affiliations']     ?? ''));
$awards                    = trim((string)($_POST['awards']                    ?? ''));
$bio                       = trim((string)($_POST['bio']                       ?? ''));
$education                 = trim((string)($_POST['education']                 ?? ''));
$experience                = trim((string)($_POST['experience']                ?? ''));
$gender                    = trim((string)($_POST['gender']                    ?? ''));
$languages                 = trim((string)($_POST['languages']                 ?? ''));
$status                    = trim((string)($_POST['status']                    ?? 'pending'));
$accepting                 = (int)($_POST['accepting_patients']                ?? 0);

if ($name === '' || $specialty === '') error_response('Name and specialty are required', 422);

// Generate unique NMP doctor code
$doctorCode = 'NMP-' . strtoupper(bin2hex(random_bytes(3)));

// Profile image
$profileImage = null;
if (!empty($_FILES['profile_image']['name'])) {
    try {
        $profileImage = upload_single_image($_FILES['profile_image'], __DIR__ . '/../uploads/doctors');
    } catch (Throwable $e) {
        error_response('Profile image upload failed: ' . $e->getMessage(), 422);
    }
}

// Gallery images — accept 'gallery[]' (primary), 'images[]', or 'gallery_images'
$galleryFiles = [];
$galleryKey   = null;
if (!empty($_FILES['gallery']['name'][0]))        $galleryKey = 'gallery';
elseif (!empty($_FILES['images']['name'][0]))     $galleryKey = 'images';
elseif (!empty($_FILES['gallery_images']['name'][0])) $galleryKey = 'gallery_images';

if ($galleryKey) {
    try {
        $galleryFiles = upload_multiple_images($_FILES[$galleryKey], __DIR__ . '/../uploads/doctors/gallery');
    } catch (Throwable $e) {
        // Non-fatal: continue without gallery
    }
}

$stmt = $pdo->prepare('
    INSERT INTO doctors
    (doctor_id, name, title, academic_title, medical_school_affiliation,
     specialty, subspecialty, graduation_degree, graduation_year,
     location, practice, address, hospital_affiliations, phone, email,
     bio, education, experience, gender, languages, awards,
     accepting_patients, profile_image, status)
    VALUES
    (:doctor_id, :name, :title, :academic_title, :medical_school_affiliation,
     :specialty, :subspecialty, :graduation_degree, :graduation_year,
     :location, :practice, :address, :hospital_affiliations, :phone, :email,
     :bio, :education, :experience, :gender, :languages, :awards,
     :accepting_patients, :profile_image, :status)
');

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
    'status'                     => $status,
]);

$insertedId = (int)$pdo->lastInsertId();

if ($galleryFiles) {
    $imgStmt = $pdo->prepare('INSERT INTO doctor_images (doctor_id, image) VALUES (:doctor_id, :image)');
    foreach ($galleryFiles as $image) {
        $imgStmt->execute(['doctor_id' => $insertedId, 'image' => $image]);
    }
}

json_response([
    'success' => true,
    'message' => 'Doctor created successfully',
    'data'    => ['id' => $insertedId, 'doctor_id' => $doctorCode],
]);
