<?php
// send_form.php
// Backend handler for the elite registration form.
// - Validates and sanitizes input
// - Protects against basic CSRF by expecting a token (generated client-side or use session)
// - Securely handles file upload (pdf/images) with size/type checks
// - Sends a synchronous email to admisioninstitutiss@gmail.com with applicant data and attachment
// NOTE: For production, run behind HTTPS, use a real mail transport (SMTP with auth) and further hardening.

header('Content-Type: application/json; charset=utf-8');

// Support GET to provide a CSRF token for the static form (client should fetch this token before POST)
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    session_start();
    if(!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    echo json_encode(['success'=>true, 'csrf'=>$_SESSION['csrf_token']]);
    exit;
}

// Allow only POST from here
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'Method not allowed']);
    exit;
}

// Basic helpers
function bad($msg){ http_response_code(400); echo json_encode(['success'=>false,'error'=>$msg]); exit; }
function clean($s){ return trim(htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8')); }

// CSRF: simple token check using session
session_start();
$csrf = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if(!isset($_SESSION['csrf_token'])){
    // Generate a token for future forms; this is defensive and will fail current if not present.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
if(!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)){
    // For compatibility: accept if no token provided but origin looks ok (you can adjust this policy)
    // Here we choose to be strict and reject
    bad('CSRF token missing or invalide.');
}

// Collect and validate fields
$required = ['lastName','firstName','birthDate','birthPlace','address','phoneMobile','email','major','currentLevel','financeOption','appointmentDate','appointmentTime'];
foreach($required as $f){ if(empty($_POST[$f])) bad('Le champ requis "'.$f.'" est manquant.'); }

$data = [];
$data['lastName'] = clean($_POST['lastName']);
$data['firstName'] = clean($_POST['firstName']);
$data['birthDate'] = clean($_POST['birthDate']);
$data['birthPlace'] = clean($_POST['birthPlace']);
$data['gender'] = clean($_POST['gender'] ?? '');
$data['address'] = clean($_POST['address']);
$data['phoneMobile'] = clean($_POST['phoneMobile']);
$data['phoneFix'] = clean($_POST['phoneFix'] ?? '');
$data['email'] = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
if(!$data['email']) bad('Adresse email invalide.');
$data['major'] = clean($_POST['major']);
$data['currentLevel'] = clean($_POST['currentLevel']);
$data['lastDiploma'] = clean($_POST['lastDiploma'] ?? '');
$data['financeOption'] = clean($_POST['financeOption']);
$data['financeDetails'] = clean($_POST['financeDetails'] ?? '');
$data['appointmentDate'] = clean($_POST['appointmentDate']);
$data['appointmentTime'] = clean($_POST['appointmentTime']);
$data['timezone'] = clean($_POST['timezone'] ?? '');
$data['comments'] = clean($_POST['comments'] ?? '');

// Validate date formats (YYYY-MM-DD)
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birthDate'])) bad('Format de date de naissance invalide.');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['appointmentDate'])) bad('Format de date de rendez-vous invalide.');
if(!preg_match('/^\d{2}:\d{2}$/', $data['appointmentTime'])) bad('Format d\'heure invalide.');

// File upload handling
$attachmentPath = null;
if(!empty($_FILES['diplomaFile']) && $_FILES['diplomaFile']['error'] !== UPLOAD_ERR_NO_FILE){
    $f = $_FILES['diplomaFile'];
    if($f['error'] !== UPLOAD_ERR_OK) bad('Erreur lors du téléversement du fichier.');
    // Limit size to 5MB
    if($f['size'] > 5 * 1024 * 1024) bad('Le fichier dépasse la taille maximale autorisée (5MB).');
    // Validate MIME type via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    $allowed = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!array_key_exists($mime, $allowed)) bad('Type de fichier non autorisé. Autorisé: PDF, JPG, PNG, WEBP.');
    // Generate safe filename
    $ext = $allowed[$mime];
    $safeName = sprintf('%s_%s_%s.%s', time(), preg_replace('/[^A-Za-z0-9_-]/','',substr($data['lastName'],0,20)), bin2hex(random_bytes(6)), $ext);
    $uploadDir = sys_get_temp_dir();
    $dest = $uploadDir.DIRECTORY_SEPARATOR.$safeName;
    if(!move_uploaded_file($f['tmp_name'], $dest)) bad('Impossible de stocker le fichier téléchargé.');
    $attachmentPath = $dest;
}

// Compose email body
$to = 'admisioninstitutiss@gmail.com';
$subject = 'Nouvelle candidature - ' . $data['lastName'] . ' ' . $data['firstName'];
$boundary = 'b'.bin2hex(random_bytes(8));

$bodyText = "Nouvelle candidature reçue:\n\n";
foreach($data as $k=>$v) $bodyText .= ucfirst($k).": " . ($v !== '' ? $v : '-') . "\n";

// Build multipart email with optional attachment
$headers = [];
$headers[] = 'From: no-reply@institutiss.example';
$headers[] = 'Reply-To: '. $data['email'];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: multipart/mixed; boundary="'.$boundary.'"';

$message = "--$boundary\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$message .= $bodyText . "\r\n";

if($attachmentPath){
    $fileData = file_get_contents($attachmentPath);
    $fileName = basename($attachmentPath);
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: application/octet-stream; name=\"$fileName\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
    $message .= chunk_split(base64_encode($fileData)) . "\r\n";
}
$message .= "--$boundary--\r\n";

// Send email synchronously (mail()). For higher reliability use SMTP libraries (PHPMailer, Symfony Mailer) with authentication.
$sent = @mail($to, $subject, $message, implode("\r\n", $headers));

// Clean up temporary file
if($attachmentPath && file_exists($attachmentPath)) @unlink($attachmentPath);

if(!$sent){ http_response_code(500); echo json_encode(['success'=>false,'error'=>'Impossible d\'envoyer l\'email.']); exit; }

echo json_encode(['success'=>true,'message'=>'Candidature envoyée']);
exit;
?>

