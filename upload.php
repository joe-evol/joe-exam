<?php
// upload.php - S3 file upload handler (direct API, no SDK)
require_once 'conf.inc';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'No file uploaded']));
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit(json_encode(['error' => 'Upload error: ' . $file['error']]));
}


// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, WEBP allowed.']));
}

// Max 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    exit(json_encode(['error' => 'File too large. Max 5MB.']));
}

// S3 Configuration
$bucket = 'exam1021';
$region = 'ap-northeast-1';
$accessKey = AWS_ACCESS_KEY;
$secretKey = AWS_SECRET_KEY;

// Generate unique filename with sanitization
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'joe_' . uniqid() . '_' . time() . '.' . $extension;
$s3Key = 'uploads/' . $filename;

try {
    $url = uploadToS3(
        $file['tmp_name'],
        $bucket,
        $s3Key,
        $region,
        $accessKey,
        $secretKey,
        $mimeType
    );
    
    echo json_encode(['success' => true, 'url' => $url]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed: ' . $e->getMessage()]);
}

function uploadToS3($filePath, $bucket, $key, $region, $accessKey, $secretKey, $contentType) {
    $fileContent = file_get_contents($filePath);
    $contentLength = strlen($fileContent);
    
    // S3 endpoint
    $host = "{$bucket}.s3.{$region}.amazonaws.com";
    $url = "https://{$host}/{$key}";
    
    // Date
    $date = gmdate('D, d M Y H:i:s T');
    $amzDate = gmdate('Ymd\THis\Z');
    $dateStamp = gmdate('Ymd');
    
    // Canonical request components
    $method = 'PUT';
    $canonicalUri = '/' . $key;
    $canonicalQuerystring = '';
    $canonicalHeaders = "host:{$host}\nx-amz-acl:public-read\nx-amz-content-sha256:UNSIGNED-PAYLOAD\nx-amz-date:{$amzDate}\n";
    $signedHeaders = 'host;x-amz-acl;x-amz-content-sha256;x-amz-date';
    $payloadHash = 'UNSIGNED-PAYLOAD';
    
    // Create canonical request
    $canonicalRequest = "{$method}\n{$canonicalUri}\n{$canonicalQuerystring}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
    
    // Create string to sign
    $algorithm = 'AWS4-HMAC-SHA256';
    $credentialScope = "{$dateStamp}/{$region}/s3/aws4_request";
    $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);
    
    // Calculate signature
    $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', 's3', $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
    $signature = hash_hmac('sha256', $stringToSign, $kSigning);
    
    // Authorization header
    $authorization = "{$algorithm} Credential={$accessKey}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    
    // Make request using cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Host: {$host}",
        "Content-Type: {$contentType}",
        "Content-Length: {$contentLength}",
        "x-amz-content-sha256: {$payloadHash}",
        "x-amz-date: {$amzDate}",
        "x-amz-acl: public-read",
        "Authorization: {$authorization}"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("S3 upload failed with code {$httpCode}. Response: {$response}. Error: {$error}");
    }
    
    curl_close($ch);
    
    return $url;
}