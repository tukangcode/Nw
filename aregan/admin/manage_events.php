<?php
// FILE: /aregan/admin/manage_events.php

require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$error = '';

// --- Handle POST Actions (Delete/Add) FIRST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (Logika untuk Add dan Delete tidak berubah, tetap di sini) ...
    if (isset($_POST['delete_event'])) {
        $event_id = $_POST['event_id'];
        try {
            $stmt = $pdo->prepare("SELECT images FROM events WHERE id = ?"); $stmt->execute([$event_id]);
            $event = $stmt->fetch();
            if ($event && $event['images']) {
                $images = json_decode($event['images'], true);
                foreach ($images as $img) { if (strpos($img['src'], 'uploads/') === 0) { $file_path = '../' . $img['src']; if (file_exists($file_path)) { unlink($file_path); } } }
            }
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?"); $stmt->execute([$event_id]);
            $message = "Laporan #$event_id dan file terkait telah berhasil dihapus.";
        } catch (PDOException $e) { $error = "Gagal menghapus laporan: " . $e->getMessage(); }
    } elseif (isset($_POST['add_event'])) {
        try {
            if (empty($_POST['event_name']) || empty($_POST['latitude'])) throw new Exception("Nama Kejadian dan Lokasi di Peta wajib diisi.");
            $images_data = [];
            for ($i = 1; $i <= 3; $i++) {
                $image_url = ''; $upload_file_key = 'image_upload_' . $i; $link_key = 'image_link_' . $i; $size_key = 'image_size_' . $i;
                if (isset($_FILES[$upload_file_key]) && $_FILES[$upload_file_key]['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/'; if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                    $file_name = uniqid() . '-' . basename($_FILES[$upload_file_key]['name']); $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($_FILES[$upload_file_key]['tmp_name'], $target_file)) { $image_url = 'uploads/' . $file_name; }
                } elseif (!empty($_POST[$link_key])) { $image_url = $_POST[$link_key]; }
                if (!empty($image_url)) { $images_data[] = ['src' => $image_url, 'size' => $_POST[$size_key] ?? '640']; }
            }
            $sources_data = [];
            for ($i = 1; $i <= 4; $i++) { if (!empty($_POST['source_link_' . $i])) { $sources_data[] = $_POST['source_link_' . $i]; } }
            $data = [
                'event_name' => trim($_POST['event_name']), 'event_details' => trim($_POST['event_details']), 'event_date' => $_POST['event_date'],
                'category' => $_POST['category'], 'latitude' => $_POST['latitude'], 'longitude' => $_POST['longitude'],
                'radius' => !empty($_POST['radius']) ? (int)$_POST['radius'] : null, 'icon_class' => $_POST['icon_class'],
                'status' => !empty($_POST['status']) ? $_POST['status'] : null, 'images' => !empty($images_data) ? json_encode($images_data) : null,
                'sources' => !empty($sources_data) ? json_encode($sources_data) : null,
            ];
            $sql = "INSERT INTO events (event_name, event_details, event_date, category, latitude, longitude, radius, icon_class, status, images, sources) VALUES (:event_name, :event_details, :event_date, :category, :latitude, :longitude, :radius, :icon_class, :status, :images, :sources)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            $message = "Laporan baru berhasil dipublikasikan.";
        } catch (Exception $e) { $error = "Gagal menambah laporan: " . $e->getMessage(); }
    }
}

// --- LOGIKA BARU: Paginasi dan Pencarian ---
$results_per_page = 15; // Jumlah laporan per halaman
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Bangun query dasar dan parameter
$sql_where = '';
$params = [];
if (!empty($search_term)) {
    $sql_where = "WHERE event_name LIKE :search";
    $params[':search'] = "%$search_term%";
}

// 1. Hitung total hasil untuk paginasi
$total_stmt = $pdo->prepare("SELECT COUNT(id) FROM events $sql_where");
$total_stmt->execute($params);
$total_results = $total_stmt->fetchColumn();
$total_pages = ceil($total_results / $results_per_page);

// Pastikan halaman saat ini tidak melebihi total halaman
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

// 2. Hitung OFFSET untuk query data
$offset = ($current_page - 1) * $results_per_page;

// 3. Ambil data untuk halaman saat ini
$data_sql = "SELECT id, event_name, event_date FROM events $sql_where ORDER BY event_date DESC, id DESC LIMIT :limit OFFSET :offset";
$data_stmt = $pdo->prepare($data_sql);

// Bind parameter pencarian (jika ada) dan paginasi
if (!empty($search_term)) {
    $data_stmt->bindParam(':search', $params[':search'], PDO::PARAM_STR);
}
$data_stmt->bindParam(':limit', $results_per_page, PDO::PARAM_INT);
$data_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$data_stmt->execute();
$events = $data_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Laporan - NW Admin</title>
    <!-- ... (Semua link CSS & JS sama seperti sebelumnya) ... -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />
    <style> #map { height: 400px; border-radius: 0.5rem; } .leaflet-div-icon { background:transparent; border:none; } </style>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow-sm">
        <div class="max-w-screen-xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Kelola Laporan Publik</h1>
            <a href="index.php" class="text-blue-600 hover:text-blue-800">← Dashboard</a>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($message) echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>$message</p></div>"; ?>
        <?php if ($error) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>$error</p></div>"; ?>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Kolom Kiri: Form Tambah Laporan (tidak berubah) -->
            <div class="lg:col-span-3 bg-white p-6 rounded-lg shadow">
                <!-- ... (Seluruh isi <form> dari respons sebelumnya tetap ada di sini) ... -->
                <h2 class="text-xl font-semibold mb-4 border-b pb-2">Form Tambah Laporan</h2>
                <form action="manage_events.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- ... semua field form ... -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium">Nama Kejadian*</label><input type="text" name="event_name" required class="mt-1 w-full p-2 border rounded-md"></div>
                        <div><label class="block text-sm font-medium">Tanggal*</label><input type="date" name="event_date" required class="mt-1 w-full p-2 border rounded-md" value="<?php echo date('Y-m-d'); ?>"></div>
                    </div>
                    <div><label class="block text-sm font-medium">Detail Kejadian</label><textarea name="event_details" rows="4" class="mt-1 w-full p-2 border rounded-md"></textarea></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t">
                        <div><label class="block text-sm font-medium">Kategori</label><select name="category" class="mt-1 w-full p-2 border rounded-md bg-white"><optgroup label="Aparat"><option>Polisi</option><option>Tentara</option><option>Pemerintah</option><option>BPBD</option><option>Satpol PP</option></optgroup><optgroup label="Insiden"><option>Kecelakaan</option><option>Kriminal</option><option>Kerusuhan</option><option>Orang Hilang</option></optgroup><optgroup label="Bencana Alam"><option>Banjir</option><option>Kebakaran</option><option>Longsor</option><option>Topan</option></optgroup><optgroup label="Ekonomi/Acara"><option>Pasar Tumpah/Malam</option><option>Diskon</option><option>Festival/Event</option><option>Review Tempat</option></optgroup></select></div>
                        <div><label class="block text-sm font-medium">Ikon Peta</label><select name="icon_class" class="mt-1 w-full p-2 border rounded-md bg-white"><optgroup label="Aparat"><option value="fa-handcuffs">Polisi</option><option value="fa-helicopter">Tentara</option><option value="fa-building-shield">Pemerintah/SAR</option></optgroup><optgroup label="Insiden"><option value="fa-car-burst">Kecelakaan</option><option value="fa-skull-crossbones">Kriminal</option><option value="fa-users">Kerusuhan</option><option value="fa-user-secret">Orang Hilang</option></optgroup><optgroup label="Bencana"><option value="fa-house-flood-water">Banjir</option><option value="fa-fire-flame-curved">Kebakaran</option><option value="fa-hill-rockslide">Longsor</option><option value="fa-wind">Topan</option></optgroup><optgroup label="Ekonomi"><option value="fa-store">Pasar</option><option value="fa-tag">Diskon/Event</option><option value="fa-star">Review Tempat</option></optgroup><option value="fa-map-marker-alt" selected>Default</option></select></div>
                        <div><label class="block text-sm font-medium">Radius (m)</label><select name="radius" class="mt-1 w-full p-2 border rounded-md bg-white"><option value="">Tidak Ada</option><option value="50">50m</option><option value="100">100m</option><option value="250">250m</option><option value="500">500m</option><option value="1000">1km</option><option value="5000">5km</option><option value="10000">10km</option></select></div>
                        <div>
    <label class="block text-sm font-medium">Status</label>
    <select name="status" class="mt-1 w-full p-2 border rounded-md bg-white">
        <option value="">-- Tanpa Status --</option> <!-- Opsi baru dengan value kosong -->
        <option value="Berlangsung">Berlangsung</option>
        <option value="Dimulai">Dimulai</option>
        <option value="Tuntas/Selesai">Tuntas/Selesai</option>
        <option value="Belum Tuntas/Gagal">Belum Tuntas/Gagal</option>
    </select>
</div>
                    </div>
                    <div class="pt-4 border-t"><label class="block text-sm font-medium mb-2">Lokasi (Gunakan Pencarian, Kompas, atau Klik Peta)*</label><div id="map"></div><input type="hidden" id="latitude" name="latitude" required><input type="hidden" id="longitude" name="longitude" required></div>
                    <div class="pt-4 border-t space-y-4">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="p-3 border rounded-md"><label class="block text-sm font-medium">Gambar <?php echo $i; ?></label><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2"><div><label class="text-xs">Upload File / Ambil Gambar (Prioritas)</label><input type="file" name="image_upload_<?php echo $i; ?>" accept="image/*" capture="environment" class="w-full text-sm"></div><div><label class="text-xs">atau Link URL</label><input type="url" name="image_link_<?php echo $i; ?>" placeholder="https://..." class="w-full p-2 border rounded-md"></div><div class="md:col-span-2"><label class="text-xs">Ukuran Tampilan</label><select name="image_size_<?php echo $i; ?>" class="w-full p-2 border rounded-md bg-white"><option value="240">Kecil (240px)</option><option value="320">Sedang (320px)</option><option value="640" selected>Besar (640px)</option><option value="1024">Sangat Besar (1024px)</option></select></div></div></div>
                        <?php endfor; ?>
                        <div><label class="block text-sm font-medium">Sumber Berita</label><input type="url" name="source_link_1" placeholder="Link sumber 1" class="mt-1 w-full p-2 border rounded-md"><input type="url" name="source_link_2" placeholder="Link sumber 2 (opsional)" class="mt-1 w-full p-2 border rounded-md"><input type="url" name="source_link_3" placeholder="Link sumber 3 (opsional)" class="mt-1 w-full p-2 border rounded-md"><input type="url" name="source_link_4" placeholder="Link sumber 4 (opsional)" class="mt-1 w-full p-2 border rounded-md"></div>
                    </div>
                    <button type="submit" name="add_event" class="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 font-bold text-lg">Publikasikan Laporan</button>
                </form>
            </div>

            <!-- Kolom Kanan: Daftar Laporan dengan Fitur Baru -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <div class="p-4 border-b">
                        <h2 class="text-xl font-semibold">Daftar Laporan Terpublikasi</h2>
                        <!-- FORM PENCARIAN BARU -->
                        <form action="manage_events.php" method="GET" class="mt-4">
                            <div class="flex">
                                <input type="text" name="search" placeholder="Cari nama laporan..." class="w-full p-2 border rounded-l-md" value="<?php echo htmlspecialchars($search_term); ?>">
                                <button type="submit" class="bg-gray-600 text-white p-2 rounded-r-md hover:bg-gray-700">Cari</button>
                            </div>
                        </form>
                    </div>
                    
                    <ul class="divide-y divide-gray-200">
                        <?php if (empty($events)): ?>
                            <li class="p-4 text-center text-gray-500">Tidak ada laporan yang ditemukan.</li>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <li class="p-4 flex items-center justify-between hover:bg-gray-50">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-600"><?php echo htmlspecialchars($event['event_name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="text-green-600 hover:text-green-900 text-sm font-medium">Edit</a>
                                        <form action="manage_events.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus laporan ini?');">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" name="delete_event" class="text-red-600 hover:text-red-900 text-sm font-medium">Hapus</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>

                    <!-- NAVIGASI PAGINASI BARU -->
                    <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-t flex justify-between items-center text-sm">
                        <div>
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search_term); ?>" class="px-3 py-1 border rounded-md hover:bg-gray-100">← Sebelumnya</a>
                            <?php endif; ?>
                        </div>
                        <div class="font-semibold">
                            Halaman <?php echo $current_page; ?> dari <?php echo $total_pages; ?>
                        </div>
                        <div>
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search_term); ?>" class="px-3 py-1 border rounded-md hover:bg-gray-100">Berikutnya →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- ... (Script peta sama seperti sebelumnya, tidak ada perubahan) ... -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>
    <script>
        const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
        const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');
        const map = L.map('map', { layers: [streetLayer], zoomControl: true }).setView([-2.5489, 118.0149], 5);
        L.control.layers({ "Jalan": streetLayer, "Satelit": satelliteLayer }).addTo(map);
        L.Control.geocoder({ defaultMarkGeocode: false, placeholder: 'Cari alamat...' }).on('markgeocode', e => handleMapInteraction(e.geocode.center)).addTo(map);
        L.control.locate({ strings: { title: "Lacak lokasi saya" } }).addTo(map);
        map.on('locationfound', e => handleMapInteraction(e.latlng));
        map.zoomControl.setPosition('topright');
        let marker; const latInput = document.getElementById('latitude'); const lonInput = document.getElementById('longitude');
        function handleMapInteraction(latlng) {
            latInput.value = latlng.lat.toFixed(6); lonInput.value = latlng.lng.toFixed(6);
            if (!marker) {
                marker = L.marker(latlng, {draggable: true}).addTo(map);
                marker.on('dragend', (e) => { const newLatLng = e.target.getLatLng(); latInput.value = newLatLng.lat.toFixed(6); lonInput.value = newLatLng.lng.toFixed(6); });
            } else { marker.setLatLng(latlng); }
            map.setView(latlng, 15);
        }
        map.on('click', e => handleMapInteraction(e.latlng));
    </script>
</body>
</html>