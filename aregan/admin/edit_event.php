<?php
// for event editing report

require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$message = '';
$error = '';

// 1. Validasi ID from url
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_events.php");
    exit;
}
$event_id = $_GET['id'];

// 2. part to update logic from db
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    try {
        // Ambil data gambar saat ini untuk perbandingan
        $stmt = $pdo->prepare("SELECT images FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $current_event = $stmt->fetch();
        $current_images = json_decode($current_event['images'], true) ?: [];

        $images_data = [];
        for ($i = 1; $i <= 3; $i++) {
            $image_url = '';
            $current_image_src = $current_images[$i - 1]['src'] ?? null;

            // Prioritas 1: new file upload
            if (isset($_FILES['image_upload_' . $i]) && $_FILES['image_upload_' . $i]['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
                $file_name = uniqid() . '-' . basename($_FILES['image_upload_' . $i]['name']);
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES['image_upload_' . $i]['tmp_name'], $target_file)) {
                    $image_url = 'uploads/' . $file_name;
                    // delete if new file uploaded
                    if ($current_image_src && strpos($current_image_src, 'uploads/') === 0 && file_exists('../' . $current_image_src)) {
                        unlink('../' . $current_image_src);
                    }
                }
            } 
            // Prioritas 2: Url fill can be new or old
            elseif (!empty($_POST['image_link_' . $i])) {
                $image_url = $_POST['image_link_' . $i];
            } 
            // Prioritas 3: if there no input hold old image
            elseif (isset($_POST['keep_image_' . $i]) && $current_image_src) {
                $image_url = $current_image_src;
            }

            if (!empty($image_url)) {
                $images_data[] = ['src' => $image_url, 'size' => $_POST['image_size_' . $i] ?? '640'];
            }
        }

        $sources_data = [];
        for ($i = 1; $i <= 4; $i++) { if (!empty($_POST['source_link_' . $i])) { $sources_data[] = $_POST['source_link_' . $i]; } }

        $data_to_update = [
            'event_name' => trim($_POST['event_name']), 'event_details' => trim($_POST['event_details']),
            'event_date' => $_POST['event_date'], 'category' => $_POST['category'],
            'latitude' => $_POST['latitude'], 'longitude' => $_POST['longitude'],
            'radius' => !empty($_POST['radius']) ? (int)$_POST['radius'] : null,
            'icon_class' => $_POST['icon_class'], 'status' => !empty($_POST['status']) ? $_POST['status'] : null,
            'images' => !empty($images_data) ? json_encode($images_data) : null,
            'sources' => !empty($sources_data) ? json_encode($sources_data) : null,
            'id' => $event_id
        ];

        $sql = "UPDATE events SET event_name = :event_name, event_details = :event_details, event_date = :event_date, category = :category, latitude = :latitude, longitude = :longitude, radius = :radius, icon_class = :icon_class, status = :status, images = :images, sources = :sources WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data_to_update);
        $message = "Laporan berhasil diperbarui.";

    } catch (Exception $e) { $error = "Gagal memperbarui laporan: " . $e->getMessage(); }
}

// 3. always retrive new data from db
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: manage_events.php");
    exit;
}
$event['images'] = json_decode($event['images'], true) ?: [];
$event['sources'] = json_decode($event['sources'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Laporan #<?php echo $event_id; ?> - AREGAN Admin</title>
    <!-- all css stuff you need -->
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
            <h1 class="text-2xl font-bold text-gray-900">Edit Laporan: <span class="text-blue-600"><?php echo htmlspecialchars($event['event_name']); ?></span></h1>
            <a href="manage_events.php" class="text-blue-600 hover:text-blue-800">← Kembali ke Daftar</a>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($message) echo "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4' role='alert'><p>$message</p></div>"; ?>
        <?php if ($error) echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'><p>$error</p></div>"; ?>

        <div class="bg-white p-6 rounded-lg shadow">
            <form action="edit_event.php?id=<?php echo $event_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Form diisi dengan data dari $event -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium">Nama Kejadian*</label><input type="text" name="event_name" required class="mt-1 w-full p-2 border rounded-md" value="<?php echo htmlspecialchars($event['event_name']); ?>"></div>
                    <div><label class="block text-sm font-medium">Tanggal*</label><input type="date" name="event_date" required class="mt-1 w-full p-2 border rounded-md" value="<?php echo htmlspecialchars($event['event_date']); ?>"></div>
                </div>
                <div><label class="block text-sm font-medium">Detail Kejadian</label><textarea name="event_details" rows="4" class="mt-1 w-full p-2 border rounded-md"><?php echo htmlspecialchars($event['event_details']); ?></textarea></div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t">
                    <?php $kategori_options = ['Polisi', 'Tentara', 'Pemerintah', 'BPBD', 'Satpol PP', 'Kecelakaan', 'Kriminal', 'Kerusuhan', 'Orang Hilang', 'Banjir', 'Kebakaran', 'Longsor', 'Topan', 'Pasar Tumpah/Malam', 'Diskon', 'Festival/Event', 'Review Tempat']; ?>
                    <div><label class="block text-sm font-medium">Kategori</label><select name="category" class="mt-1 w-full p-2 border rounded-md bg-white"><?php foreach($kategori_options as $opt): ?><option <?php if ($event['category'] === $opt) echo 'selected'; ?>><?php echo $opt; ?></option><?php endforeach; ?></select></div>
                    <?php $ikon_options = ['fa-handcuffs'=>'Polisi', 'fa-helicopter'=>'Tentara', 'fa-building-shield'=>'Pemerintah/SAR', 'fa-car-burst'=>'Kecelakaan', 'fa-skull-crossbones'=>'Kriminal', 'fa-users'=>'Kerusuhan', 'fa-user-secret'=>'Orang Hilang', 'fa-house-flood-water'=>'Banjir', 'fa-fire-flame-curved'=>'Kebakaran', 'fa-hill-rockslide'=>'Longsor', 'fa-wind'=>'Topan', 'fa-store'=>'Pasar', 'fa-tag'=>'Diskon/Event', 'fa-star'=>'Review Tempat', 'fa-map-marker-alt'=>'Default']; ?>
                    <div><label class="block text-sm font-medium">Ikon Peta</label><select name="icon_class" class="mt-1 w-full p-2 border rounded-md bg-white"><?php foreach($ikon_options as $val => $label): ?><option value="<?php echo $val; ?>" <?php if ($event['icon_class'] === $val) echo 'selected'; ?>><?php echo $label; ?></option><?php endforeach; ?></select></div>
                    <?php $radius_options = ['', 50, 100, 250, 500, 1000, 5000, 10000]; ?>
                    <div><label class="block text-sm font-medium">Radius (m)</label><select name="radius" class="mt-1 w-full p-2 border rounded-md bg-white"><?php foreach($radius_options as $opt): ?><option value="<?php echo $opt; ?>" <?php if ($event['radius'] == $opt) echo 'selected'; ?>><?php echo $opt ?: 'Tidak Ada'; ?></option><?php endforeach; ?></select></div>
                    <?php 
$status_options = [
    '' => '-- Tanpa Status --', // value kosong
    'Berlangsung' => 'Berlangsung',
    'Dimulai' => 'Dimulai',
    'Tuntas/Selesai' => 'Tuntas/Selesai',
    'Belum Tuntas/Gagal' => 'Belum Tuntas/Gagal'
]; 
?>
<div>
    <label class="block text-sm font-medium">Status</label>
    <select name="status" class="mt-1 w-full p-2 border rounded-md bg-white">
        <?php foreach($status_options as $value => $label): ?>
            <option value="<?php echo htmlspecialchars($value); ?>" <?php if ($event['status'] == $value) echo 'selected'; ?>>
                <?php echo htmlspecialchars($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                </div>

                <div class="pt-4 border-t"><label class="block text-sm font-medium mb-2">Lokasi*</label><div id="map"></div><input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($event['latitude']); ?>" required><input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($event['longitude']); ?>" required></div>

                <div class="pt-4 border-t space-y-4">
                    <?php for ($i = 1; $i <= 3; $i++): $img = $event['images'][$i-1] ?? null; ?>
                    <div class="p-3 border rounded-md bg-gray-50">
                        <label class="block text-sm font-medium">Gambar <?php echo $i; ?></label>
                        <?php if ($img): ?><div class="text-xs text-gray-500 my-2">Gambar saat ini: <a href="../<?php echo htmlspecialchars($img['src']); ?>" target="_blank" class="text-blue-500 hover:underline"><?php echo htmlspecialchars(basename($img['src'])); ?></a><br><input type="checkbox" name="keep_image_<?php echo $i; ?>" checked class="mr-1"> Biarkan gambar ini? (hapus centang jika ingin mengganti/menghapus)</div><?php endif; ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-1">
                            <div><label class="text-xs">Upload File Baru</label><input type="file" name="image_upload_<?php echo $i; ?>" class="w-full text-sm"></div>
                            <div><label class="text-xs">atau Ganti Link URL</label><input type="url" name="image_link_<?php echo $i; ?>" placeholder="https://..." class="w-full p-2 border rounded-md" value="<?php echo ($img && strpos($img['src'], 'uploads/') !== 0) ? htmlspecialchars($img['src']) : ''; ?>"></div>
                            <div class="md:col-span-2"><label class="text-xs">Ukuran Tampilan</label><select name="image_size_<?php echo $i; ?>" class="w-full p-2 border rounded-md bg-white"><?php $size_opts = [240, 320, 640, 1024]; foreach($size_opts as $s):?><option value="<?php echo $s;?>" <?php if(isset($img['size']) && $img['size']==$s) echo 'selected';?>><?php echo $s;?>px</option><?php endforeach;?></select></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                    <div><label class="block text-sm font-medium">Sumber Berita</label>
                        <?php for ($i=1; $i<=4; $i++): ?>
                        <input type="url" name="source_link_<?php echo $i; ?>" placeholder="Link sumber <?php echo $i;?>" class="mt-1 w-full p-2 border rounded-md" value="<?php echo htmlspecialchars($event['sources'][$i-1] ?? ''); ?>">
                        <?php endfor; ?>
                    </div>
                </div>
                
                <button type="submit" name="update_event" class="w-full bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700 font-bold text-lg">Simpan Perubahan</button>
            </form>
        </div>
    </main>
    
    <!-- Js libray that need -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol/dist/L.Control.Locate.min.js"></script>

    <script>
        // Ambil koordinat awal dari data PHP
        const initialLat = <?php echo json_encode((float)$event['latitude']); ?>;
        const initialLon = <?php echo json_encode((float)$event['longitude']); ?>;

        const latInput = document.getElementById('latitude');
        const lonInput = document.getElementById('longitude');
        let marker;

        // --- SETUP PETA LENGKAP ---
        const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OSM' });
        const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '© Esri' });
        
        const map = L.map('map', { 
            layers: [streetLayer], 
            zoomControl: true 
        }).setView([initialLat, initialLon], 15);

        // --- TAMBAHKAN SEMUA KONTROL PETA ---
        L.control.layers({ "Jalan": streetLayer, "Satelit": satelliteLayer }).addTo(map);
        L.Control.geocoder({ defaultMarkGeocode: false, placeholder: 'Cari alamat...' }).on('markgeocode', e => handleMapInteraction(e.geocode.center)).addTo(map);
        L.control.locate({ strings: { title: "Gunakan lokasi saya" }, flyTo: true }).addTo(map);
        map.on('locationfound', e => handleMapInteraction(e.latlng));
        map.zoomControl.setPosition('topright');

        // Fungsi utama untuk menangani semua interaksi peta
        function handleMapInteraction(latlng) {
            latInput.value = latlng.lat.toFixed(6);
            lonInput.value = latlng.lng.toFixed(6);
            if (!marker) {
                marker = L.marker(latlng, {draggable: true}).addTo(map);
                marker.on('dragend', (e) => {
                    const newLatLng = e.target.getLatLng();
                    latInput.value = newLatLng.lat.toFixed(6);
                    lonInput.value = newLatLng.lng.toFixed(6);
                });
            } else {
                marker.setLatLng(latlng);
            }
            map.setView(latlng, 15);
        }

        map.on('click', e => handleMapInteraction(e.latlng));
        
        // Buat marker awal di lokasi yang tersimpan
        marker = L.marker([initialLat, initialLon], {draggable: true}).addTo(map);
        marker.on('dragend', (e) => {
            const newLatLng = e.target.getLatLng();
            latInput.value = newLatLng.lat.toFixed(6);
            lonInput.value = newLatLng.lng.toFixed(6);
        });
    </script>
</body>
</html>