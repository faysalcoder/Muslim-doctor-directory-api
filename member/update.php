<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/cors.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/request.php';
require_once __DIR__ . '/../helpers/upload.php';
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../config/database.php';

send_cors_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') error_response('Method not allowed', 405);

$payload = require_member();
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM doctors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $payload['id']]);
$current = $stmt->fetch();
if (!$current) error_response('Profile not found', 404);

// Support both JSON body and multipart/form-data (for profile image upload)
$isMultipart = !empty($_POST);
$data = $isMultipart ? $_POST : read_json_body();

// Handle profile image upload if provided
$profileImage = $current['profile_image'];
if (!empty($_FILES['profile_image']['name'])) {
    try {
        $profileImage = upload_single_image($_FILES['profile_image'], __DIR__ . '/../uploads/doctors');
    } catch (Throwable $e) {
        error_response('Profile image upload failed: ' . $e->getMessage(), 422);
    }
}

$fields = [
    'name'                       => str_value($data['name']                       ?? $current['name']),
    'title'                      => str_value($data['title']                      ?? $current['title']),
    'academic_title'             => str_value($data['academic_title']             ?? $current['academic_title']),
    'academic_affiliation'       => str_value($data['academic_affiliation']       ?? $current['academic_affiliation'] ?? ''),
    'medical_school_affiliation' => str_value($data['medical_school_affiliation'] ?? $current['medical_school_affiliation']),
    'specialty'                  => str_value($data['specialty']                  ?? $current['specialty']),
    'subspecialty'               => str_value($data['subspecialty']               ?? $current['subspecialty']),
    'graduation_degree'          => str_value($data['graduation_degree']          ?? $current['graduation_degree']),
    'graduation_year'            => int_value($data['graduation_year']            ?? $current['graduation_year']),
    'location'                   => str_value($data['location']                   ?? $current['location']),
    'practice'                   => str_value($data['practice']                   ?? $current['practice']),
    'address'                    => str_value($data['address']                    ?? $current['address']),
    'hospital_affiliations'      => str_value($data['hospital_affiliations']      ?? $current['hospital_affiliations']),
    'phone'                      => str_value($data['phone']                      ?? $current['phone']),
    'gender'                     => str_value($data['gender']                     ?? $current['gender']),
    'languages'                  => str_value($data['languages']                  ?? $current['languages']),
    'bio'                        => str_value($data['bio']                        ?? $current['bio']),
    'education'                  => str_value($data['education']                  ?? $current['education']),
    'experience'                 => str_value($data['experience']                 ?? $current['experience']),
    'awards'                     => str_value($data['awards']                     ?? $current['awards']),
    'profile_visibility'         => str_value($data['profile_visibility']         ?? $current['profile_visibility']),
    'accepting_patients'         => bool_value($data['accepting_patients']        ?? $current['accepting_patients']),
    'profile_image'              => $profileImage,
];

// Check if academic_affiliation column exists (for older installs without migration)
$columns = $pdo->query("SHOW COLUMNS FROM doctors")->fetchAll(PDO::FETCH_COLUMN);
$hasAcademicAffiliation = in_array('academic_affiliation', $columns);

$sql = 'UPDATE doctors SET
    name=:name, title=:title, academic_title=:academic_title,
    medical_school_affiliation=:medical_school_affiliation,
    specialty=:specialty, subspecialty=:subspecialty,
    graduation_degree=:graduation_degree, graduation_year=:graduation_year,
    location=:location, practice=:practice, address=:address,
    hospital_affiliations=:hospital_affiliations, phone=:phone,
    gender=:gender, languages=:languages,
    bio=:bio, education=:education, experience=:experience, awards=:awards,
    profile_visibility=:profile_visibility, accepting_patients=:accepting_patients,
    profile_image=:profile_image, updated_at=NOW()';

if ($hasAcademicAffiliation) {
    $sql .= ', academic_affiliation=:academic_affiliation';
} else {
    unset($fields['academic_affiliation']);
}

$sql .= ' WHERE id=:id';

$params = $fields + ['id' => $payload['id']];
$upd = $pdo->prepare($sql);
$upd->execute($params);

json_response(['success' => true, 'message' => 'Profile updated successfully']);
