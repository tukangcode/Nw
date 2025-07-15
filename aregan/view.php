<?php
// FILE LENGKAP DAN FINAL: /aregan/view.php (DENGAN KONTROL PETA LENGKAP)

require_once 'includes/db_connect.php';

// ... (Bagian PHP di atas tidak berubah, tetap sama seperti sebelumnya) ...
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) { header("Location: index.html"); exit; }
$event_id = $_GET['id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    if (!$event) { http_response_code(404); die("<h1>404 - Laporan Tidak Ditemukan</h1><p>Maaf, laporan yang Anda cari tidak ada atau telah dihapus.</p><a href='index.html' style='color:blue;'>← Kembali ke Peta Utama</a>"); }
    $event['images'] = json_decode($event['images'], true) ?: [];
    $event['sources'] = json_decode($event['sources'], true) ?: [];
} catch (PDOException $e) { http_response_code(500); die("Error koneksi database: " . $e->getMessage()); }
$page_title = htmlspecialchars($event['event_name']);
$page_description = htmlspecialchars(substr(strip_tags($event['event_details']), 0, 155)) . '...';
$page_url = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$page_image_path = !empty($event['images']) ? $event['images'][0]['src'] : 'https://tukangcode.github.io/images/aregan.png';
if (strpos($page_image_path, 'uploads/') === 0 && !preg_match('/^http/i', $page_image_path)) {
    $page_image = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://$_SERVER[HTTP_HOST]/aregan/" . $page_image_path;
} else {
    $page_image = $page_image_path;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AREGAN</title>

    <!-- Meta Tags -->
    <meta property="og:title" content="<?php echo $page_title; ?>" />
    <meta property="og:description" content="<?php echo $page_description; ?>" />
    <meta property="og:image" content="<?php echo $page_image; ?>" />
    <meta property="og:url" content="<?php echo $page_url; ?>" />
    <meta property="og:type" content="article" />
    
    <!-- Library CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f1f5f9; }
        #map { height: 400px; border-radius: 0.5rem; }
        .leaflet-div-icon { background:transparent; border:none; }
        /* Style untuk tombol pusatkan peta kustom */
        .leaflet-control-recenter {
            background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><path d="M16 3.515A12.5 12.5 0 003.515 16H1.5v-2h2.015a12.5 12.5 0 0022.97 0H28.5v2h-2.015A12.5 12.5 0 0016 3.515zM16 6.5a9.5 9.5 0 11-9.5 9.5H8.5v-2h-2V16A9.5 9.5 0 0116 6.5zM15 11h2v8h-2z"/><path d="M15 18h2v3h-2z" transform="rotate(45 16 16)"/><path fill="none" d="M0 0h32v32H0z"/></svg>');
            background-size: 22px 22px;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body>
    <header class="bg-white shadow-md">
        <!-- ... (Header tidak berubah) ... -->
        <div class="max-w-4xl mx-auto py-4 px-4 flex justify-between items-center"><h1 class="text-2xl font-bold text-blue-600">AREGAN</h1><a href="index.html" class="text-sm text-blue-600 hover:underline">← Kembali ke Peta Utama</a></div>
    </header>

    <main class="max-w-4xl mx-auto my-8 p-6 bg-white rounded-lg shadow-lg">
        <!-- ... (Konten laporan tidak berubah) ... -->
        <div class="border-b pb-4 mb-4"><h1 class="text-3xl md:text-4xl font-bold text-gray-800"><?php echo htmlspecialchars($event['event_name']); ?></h1><p class="mt-2 text-gray-500 text-sm flex flex-wrap gap-x-4 gap-y-2"><span><i class="fas fa-calendar-alt fa-fw mr-1"></i> <?php echo date('d F Y', strtotime($event['event_date'])); ?></span><span><i class="fas fa-tag fa-fw mr-1"></i> <?php echo htmlspecialchars($event['category']); ?></span><span><i class="fas fa-info-circle fa-fw mr-1"></i> Status: <span class="font-semibold"><?php echo htmlspecialchars($event['status']); ?></span></span></p></div>
        <?php if (!empty($event['event_details'])): ?><div class="prose max-w-none text-gray-700 leading-relaxed"><p><?php echo nl2br(htmlspecialchars($event['event_details'])); ?></p></div><?php endif; ?>
        <?php if (!empty($event['images'])): ?><div class="mt-6"><h3 class="text-xl font-semibold mb-2 text-gray-800">Gambar Terkait</h3><div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4"><?php foreach ($event['images'] as $image): ?><a href="<?php echo htmlspecialchars($image['src']); ?>" target="_blank"><img src="<?php echo htmlspecialchars($image['src']); ?>" alt="Gambar kejadian" class="w-full h-48 object-cover rounded-lg shadow-md hover:opacity-80 transition-opacity"></a><?php endforeach; ?></div></div><?php endif; ?>
        <div class="mt-6"><h3 class="text-xl font-semibold mb-2 text-gray-800">Lokasi Kejadian</h3><div id="map"></div><div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-center p-4 bg-gray-50 rounded-lg border"><div><h4 class="font-semibold">Detail Lokasi</h4><p class="text-sm text-gray-700"><strong>Koordinat:</strong> <?php echo "{$event['latitude']}, {$event['longitude']}"; ?></p><p class="text-sm text-gray-700"><strong>Radius:</strong> <?php echo $event['radius'] ? htmlspecialchars($event['radius']).'m' : 'N/A'; ?></p><div class="mt-2"><a href="https://maps.google.com/?q=<?php echo "{$event['latitude']},{$event['longitude']}"; ?>" target="_blank" class="text-blue-600 hover:underline text-sm font-medium">Buka di Google Maps →</a></div></div><div class="text-center"><div id="qr-code-container" class="inline-block p-2 bg-white rounded-md shadow"></div><p class="text-xs mt-1 text-gray-500">Scan untuk membuka lokasi</p></div></div></div>
        <?php if (!empty($event['sources'])): ?><div class="mt-6"><h3 class="text-xl font-semibold mb-2 text-gray-800">Sumber Berita</h3><ul class="list-disc pl-5 space-y-1 text-sm"><?php foreach ($event['sources'] as $source): ?><li><a href="<?php echo htmlspecialchars($source); ?>" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline break-all"><?php echo htmlspecialchars($source); ?></a></li><?php endforeach; ?></ul></div><?php endif; ?>
        <div class="mt-8 pt-6 border-t"><h3 class="text-center text-xl font-bold mb-4 text-gray-800">Bagikan Laporan Ini</h3><div class="bg-gray-50 p-6 rounded-lg border"><p class="text-sm text-gray-600 mb-3 text-center">Bagikan langsung melalui:</p><div id="share-buttons" class="flex justify-center items-center flex-wrap gap-4 mb-6"></div><p class="text-sm text-gray-600 mb-2">Atau salin tautan laporan ini:</p><div class="flex"><input type="text" id="share-link-input" readonly class="w-full p-2 border border-gray-300 rounded-l-md bg-gray-200 text-sm"><button id="copy-link-btn" class="bg-blue-600 text-white px-4 rounded-r-md hover:bg-blue-700 text-sm font-semibold">Salin</button></div><div class="mt-6 text-xs text-gray-500 text-center"><p><i class="fas fa-camera mr-1"></i>Untuk berbagi sebagai gambar, gunakan fitur screenshot panjang.</p></div></div></div>
        <div class="mt-8 text-center text-xs text-gray-400">Dibuat dengan AREGAN by Ryu-sena</div>
    </main>

    <!-- Library JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data dari PHP
        const eventData = <?php echo json_encode($event); ?>;
        const pageUrl = <?php echo json_encode($page_url); ?>;
        const pageTitle = <?php echo json_encode($page_title); ?>;

        // --- SETUP PETA LENGKAP ---
        const initialCoords = [eventData.latitude, eventData.longitude];

        const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OSM' });
        const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '© Esri' });

        const map = L.map('map', {
            layers: [streetLayer],
            zoomControl: true // Aktifkan kontrol zoom default
        }).setView(initialCoords, 15);

        // 1. Tambahkan Kontrol Ganti Layer
        L.control.layers({ "Jalan": streetLayer, "Satelit": satelliteLayer }).addTo(map);

        // 2. Buat Tombol Kustom untuk Pusatkan Peta
        L.Control.Recenter = L.Control.extend({
            onAdd: function(map) {
                const btn = L.DomUtil.create('a', 'leaflet-bar leaflet-control leaflet-control-recenter');
                btn.href = '#';
                btn.role = 'button';
                btn.title = 'Pusatkan ke Lokasi Kejadian';
                L.DomEvent.on(btn, 'click', L.DomEvent.stop).on(btn, 'click', function() {
                    map.flyTo(initialCoords, 15);
                });
                return btn;
            },
            onRemove: function(map) {}
        });
        L.control.recenter = function(opts) { return new L.Control.Recenter(opts); }
        L.control.recenter({ position: 'topleft' }).addTo(map);

        // Tampilkan marker dan radius
        const icon = L.divIcon({ html: `<i class="fas ${eventData.icon_class || 'fa-map-marker-alt'}" style="color: #ef4444; font-size: 28px; text-shadow: 0 0 3px #000;"></i>`, className: 'leaflet-div-icon' });
        L.marker(initialCoords, { icon }).addTo(map);
        if (eventData.radius) {
            L.circle(initialCoords, { radius: eventData.radius, color: '#3b82f6', fillOpacity: 0.15 }).addTo(map);
        }

        // --- AKHIR DARI SETUP PETA LENGKAP ---

        // QR Code & Logika Berbagi (tidak ada perubahan)
        new QRCode(document.getElementById('qr-code-container'), { text: `https://maps.google.com/?q=${eventData.latitude},${eventData.longitude}`, width: 128, height: 128 });
        const shareContainer = document.getElementById('share-buttons');
        const shareLinkInput = document.getElementById('share-link-input');
        const copyLinkBtn = document.getElementById('copy-link-btn');
        shareLinkInput.value = pageUrl;
        const encodedUrl = encodeURIComponent(pageUrl);
        const encodedTitle = encodeURIComponent(pageTitle);
        const platforms = { WhatsApp: { url: `https://api.whatsapp.com/send?text=${encodedTitle}%20${encodedUrl}`, icon: 'fab fa-whatsapp', color: '#25D366' }, Facebook: { url: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`, icon: 'fab fa-facebook-f', color: '#1877F2' }, X: { url: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`, icon: 'fab fa-xing', color: '#000000' }, Reddit: { url: `https://www.reddit.com/submit?url=${encodedUrl}&title=${encodedTitle}`, icon: 'fab fa-reddit-alien', color: '#FF4500' }, Mastodon: { url: '#', icon: 'fab fa-mastodon', color: '#6364FF' }, Instagram:{ url: '#', icon: 'fab fa-instagram', color: '#E4405F' }};
        for (const [name, data] of Object.entries(platforms)) {
            const a = document.createElement('a');
            a.href = data.url; a.className = 'w-12 h-12 flex items-center justify-center text-2xl text-white rounded-full shadow-lg transition-transform transform hover:scale-110';
            a.style.backgroundColor = data.color; a.title = `Bagikan ke ${name}`; a.innerHTML = `<i class="${data.icon}"></i>`;
            if (data.url === '#') { a.addEventListener('click', e => { e.preventDefault(); if (name === 'Mastodon') { const instance = prompt('Masukkan alamat instance Mastodon Anda (contoh: mastodon.social)', 'mastodon.social'); if (instance) { window.open(`https://${instance}/share?text=${encodedTitle}%20${encodedUrl}`, '_blank'); } } else if (name === 'Instagram') { navigator.clipboard.writeText(pageUrl).then(() => { alert('Tautan telah disalin. Buka Instagram dan tempel di bio atau story Anda.'); }); } });
            } else { a.target = '_blank'; a.rel = 'noopener noreferrer'; }
            shareContainer.appendChild(a);
        }
        copyLinkBtn.addEventListener('click', () => { shareLinkInput.select(); navigator.clipboard.writeText(pageUrl).then(() => { alert('Tautan berhasil disalin!'); }); });
    });
    </script>
</body>
</html>