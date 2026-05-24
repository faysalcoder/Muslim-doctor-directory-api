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
$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) error_response('Valid doctor id is required', 422);

$stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$current = $stmt->fetch();
if (!$current) error_response('Doctor not found', 404);

$name                       = trim((string)($_POST['name']                       ?? $current['name']));
$specialty                  = trim((string)($_POST['specialty']                  ?? $current['specialty']));
$email                      = trim((string)($_POST['email']                      ?? $current['email']));
$phone                      = trim((string)($_POST['phone']                      ?? $current['phone']));
$location                   = trim((string)($_POST['location']                   ?? $current['location']));
$title                      = trim((string)($_POST['title']                      ?? $current['title']));
$academic_title             = trim((string)($_POST['academic_title']             ?? $current['academic_title']));
$academic_affiliation       = trim((string)($_POST['academic_affiliation']       ?? $current['academic_affiliation'] ?? ''));
$medical_school_affiliation = trim((string)($_POST['medical_school_affiliation'] ?? $current['medical_school_affiliation']));
$subspecialty               = trim((string)($_POST['subspecialty']               ?? $current['subspecialty']));
$graduation_degree          = trim((string)($_POST['graduation_degree']          ?? $current['graduation_degree']));
$graduation_year            = isset($_POST['graduation_year']) && $_POST['graduation_year'] !== ''
                               ? (int)$_POST['graduation_year'] : ($current['graduation_year'] ?: null);
$practice                   = trim((string)($_POST['practice']                   ?? $current['practice']));
$address                    = trim((string)($_POST['address']                    ?? $current['address']));
$hospital_affiliations      = trim((string)($_POST['hospital_affiliations']      ?? $current['hospital_affiliations']));
$awards                     = trim((string)($_POST['awards']                     ?? $current['awards']));
$bio                        = trim((string)($_POST['bio']                        ?? $current['bio']));
$education                  = trim((string)($_POST['education']                  ?? $current['education']));
$experience                 = trim((string)($_POST['experience']                 ?? $current['experience']));
$gender                     = trim((string)($_POST['gender']                     ?? $current['gender']));
$languages                  = trim((string)($_POST['languages']                  ?? $current['languages']));
$status                     = trim((string)($_POST['status']                     ?? $current['status']));
$accountType                = trim((string)($_POST['account_type']                ?? $current['account_type']));
$profileVisibility          = trim((string)($_POST['profile_visibility']          ?? $current['profile_visibility']));
$password                   = trim((string)($_POST['password']                   ?? ''));
$accepting                  = isset($_POST['accepting_patients'])
                               ? (int)$_POST['accepting_patients']
                               : (int)$current['accepting_patients'];

// Profile image — replace if new file provided
$profileImage = $current['profile_image'];
if (!empty($_FILES['profile_image']['name'])) {
    try {
        $profileImage = upload_single_image($_FILES['profile_image'], __DIR__ . '/../uploads/doctors');
    } catch (Throwable $e) {
        error_response('Profile image upload failed: ' . $e->getMessage(), 422);
    }
}

// Gallery images
$galleryKey = null;
if (!empty($_FILES['gallery']['name'][0]))            $galleryKey = 'gallery';
elseif (!empty($_FILES['images']['name'][0]))         $galleryKey = 'images';
elseif (!empty($_FILES['gallery_images']['name'][0])) $galleryKey = 'gallery_images';

if ($galleryKey) {
    try {
        $newImages = upload_multiple_images($_FILES[$galleryKey], __DIR__ . '/../uploads/doctors/gallery');
        if ($newImages) {
            $imgStmt = $pdo->prepare('INSERT INTO doctor_images (doctor_id, image) VALUES (:doctor_id, :image)');
            foreach ($newImages as $image) {
                $imgStmt->execute(['doctor_id' => $id, 'image' => $image]);
            }
        }
    } catch (Throwable $e) {
        // Non-fatal
    }
}

// Check if academic_affiliation column exists; if not, skip it
$columns = $pdo->query("SHOW COLUMNS FROM doctors")->fetchAll(PDO::FETCH_COLUMN);
$hasAcademicAffiliation = in_array('academic_affiliation', $columns);

$sql = '
    UPDATE doctors SET
        name                       = :name,
        specialty                  = :specialty,
        email                      = :email,
        phone                      = :phone,
        location                   = :location,
        title                      = :title,
        academic_title             = :academic_title,
        medical_school_affiliation = :medical_school_affiliation,
        subspecialty               = :subspecialty,
        graduation_degree          = :graduation_degree,
        graduation_year            = :graduation_year,
        practice                   = :practice,
        address                    = :address,
        hospital_affiliations      = :hospital_affiliations,
        awards                     = :awards,
        bio                        = :bio,
        education                  = :education,
        experience                 = :experience,
        gender                     = :gender,
        languages                  = :languages,
        accepting_patients         = :accepting_patients,
        profile_image              = :profile_image,
        status                     = :status,
        account_type               = :account_type,
        profile_visibility         = :profile_visibility
';

if ($hasAcademicAffiliation) {
    $sql .= ', academic_affiliation = :academic_affiliation';
}

$sql .= ' WHERE id = :id';

$params = [
    'name'                       => $name,
    'specialty'                  => $specialty,
    'email'                      => $email,
    'phone'                      => $phone,
    'location'                   => $location,
    'title'                      => $title,
    'academic_title'             => $academic_title,
    'medical_school_affiliation' => $medical_school_affiliation,
    'subspecialty'               => $subspecialty,
    'graduation_degree'          => $graduation_degree,
    'graduation_year'            => $graduation_year,
    'practice'                   => $practice,
    'address'                    => $address,
    'hospital_affiliations'      => $hospital_affiliations,
    'awards'                     => $awards,
    'bio'                        => $bio,
    'education'                  => $education,
    'experience'                 => $experience,
    'gender'                     => $gender,
    'languages'                  => $languages,
    'accepting_patients'         => $accepting,
    'profile_image'              => $profileImage,
    'status'                     => $status,
    'account_type'               => $accountType ?: ($current['account_type'] ?? 'listing'),
    'profile_visibility'         => $profileVisibility ?: ($current['profile_visibility'] ?? 'public'),
    'id'                         => $id,
];

if ($hasAcademicAffiliation) {
    $params['academic_affiliation'] = $academic_affiliation;
}

if ($password !== '') {
    $sql = str_replace('status = :status', 'status = :status, password_hash = :password_hash', $sql);
    $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
}

$update = $pdo->prepare($sql);
$update->execute($params);

json_response([
    'success' => true,
    'message' => 'Doctor updated successfully',
    'data'    => ['id' => $id],
]);
