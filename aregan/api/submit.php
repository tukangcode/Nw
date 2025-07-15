<?php
// /aregan/api/submit.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Metode POST diperlukan.']);
    exit;
}

require_once '../includes/db_connect.php';

try {
    $required_fields = ['event_name', 'event_details', 'event_date', 'latitude', 'longitude'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' wajib diisi.");
        }
    }

    $data = [
        'event_name' => trim($_POST['event_name']),
        'event_details' => trim($_POST['event_details']),
        'event_date' => $_POST['event_date'],
        'category' => $_POST['category'] ?? 'Lainnya',
        'latitude' => $_POST['latitude'],
        'longitude' => $_POST['longitude'],
        'radius' => !empty($_POST['radius']) ? (int)$_POST['radius'] : null,
        'icon_class' => $_POST['icon_class'] ?? 'fa-map-marker-alt',
        'submitter_name' => !empty($_POST['submitter_name']) ? trim($_POST['submitter_name']) : null,
        'submitter_email' => !empty($_POST['submitter_email']) ? trim($_POST['submitter_email']) : null,
        'images' => null, 
        'sources' => null,
    ];

    $sql = "INSERT INTO submissions (event_name, event_details, event_date, category, latitude, longitude, radius, icon_class, submitter_name, submitter_email, images, sources, status, submitted_at)
            VALUES (:event_name, :event_details, :event_date, :category, :latitude, :longitude, :radius, :icon_class, :submitter_name, :submitter_email, :images, :sources, 'pending', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    http_response_code(200);
    echo json_encode(['message' => 'Laporan Anda telah berhasil dikirim dan akan segera ditinjau oleh administrator.']);

} catch (Exception $e) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => $e->getMessage()]);
}

unset($pdo);
?>