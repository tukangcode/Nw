<?php
// /aregan/api/stats.php

// Amankan endpoint ini, hanya untuk admin yang sudah login
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Tentukan periode waktu dari parameter GET, defaultnya 1 bulan
    $period = $_GET['period'] ?? 'month';
    $start_date = '';

    switch ($period) {
        case 'week':
            $start_date = date('Y-m-d', strtotime('-7 days'));
            break;
        case '3_months':
            $start_date = date('Y-m-d', strtotime('-3 months'));
            break;
        case '6_months':
            $start_date = date('Y-m-d', strtotime('-6 months'));
            break;
        case 'year':
            $start_date = date('Y-m-d', strtotime('-1 year'));
            break;
        case 'month':
        default:
            $start_date = date('Y-m-d', strtotime('-30 days'));
            break;
    }

    // Query untuk menghitung jumlah kejadian per kategori dalam rentang waktu yang ditentukan
    $sql = "SELECT category, COUNT(*) as count 
            FROM events 
            WHERE event_date >= :start_date
            GROUP BY category 
            ORDER BY count DESC
            LIMIT 10"; // Batasi 10 kategori teratas agar grafik tidak terlalu ramai
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start_date' => $start_date]);
    $results = $stmt->fetchAll();

    // Olah data agar siap digunakan oleh Chart.js
    $labels = [];
    $data_points = [];

    foreach ($results as $row) {
        $labels[] = $row['category'];
        $data_points[] = (int)$row['count'];
    }

    // Kirim response dalam format JSON yang rapi
    echo json_encode([
        'labels' => $labels,
        'data' => $data_points,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}
?>