<?php
// /aregan/api/events.php

// Set header ke JSON
header('Content-Type: application/json');

// Sertakan koneksi database
require_once '../includes/db_connect.php';

try {
    // Siapkan daftar kolom untuk menghindari pengulangan dan typo
    // Pastikan semua kolom yang dibutuhkan app.js ada di sini (termasuk 'status')
    $columns = "id, event_name, event_details, status, category, latitude, longitude, radius, icon_class, images, sources";

    // Pilihan 1: Jika ini adalah permintaan untuk RENTANG TANGGAL
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];

        // Validasi format tanggal (sangat penting untuk keamanan)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            throw new Exception("Format tanggal tidak valid. Gunakan YYYY-MM-DD.");
        }

        $sql = "SELECT {$columns} FROM events WHERE event_date BETWEEN :start_date AND :end_date ORDER BY event_date DESC, id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
    } 
    // Pilihan 2: Jika ini adalah permintaan untuk TANGGAL TUNGGAL (logika lama yang kita pertahankan)
    else {
        // Ambil tanggal dari URL, jika tidak ada, gunakan tanggal hari ini
        $event_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date)) {
            throw new Exception("Format tanggal tidak valid. Gunakan YYYY-MM-DD.");
        }

        $sql = "SELECT {$columns} FROM events WHERE event_date = :event_date ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':event_date', $event_date, PDO::PARAM_STR);
    }

    // --- Bagian ini dieksekusi untuk kedua jenis permintaan ---

    // Eksekusi query yang sudah disiapkan
    $stmt->execute();

    // Ambil semua hasilnya
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Loop untuk mengubah kolom JSON dari teks menjadi array
    foreach ($events as $key => $event) {
        $events[$key]['images'] = json_decode($event['images'], true) ?: [];
        $events[$key]['sources'] = json_decode($event['sources'], true) ?: [];
    }

    // Cetak hasil akhir sebagai JSON
    echo json_encode($events);

} catch (Exception $e) {
    // Jika terjadi kesalahan di mana pun dalam blok try, kirim response error yang jelas
    http_response_code(500); // Kode 500 lebih cocok untuk error server
    echo json_encode(['error' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}

// Tutup koneksi (opsional)
unset($pdo);
?>