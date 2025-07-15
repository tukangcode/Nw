document.addEventListener('DOMContentLoaded', function() {
    // --- Globals & Element References ---
    let map;
    let markersLayer;
    let activeRadiusCircle = null;
    let allEventsData = [];
    let activeDetailContainer = null;
    let geojsonLayer;
    let provinceBounds = {};
    let clockInterval = null; // Untuk menyimpan ID interval jam
    let activeTimezone = 'Asia/Jayapura'; // Default ke WIT

    // GANTI SELURUH BLOK ELS ANDA DENGAN INI
const ELS = {
    mapContainer: document.getElementById('map'),
    eventsList: document.getElementById('events-list'),
    eventDateInput: document.getElementById('eventDate'),
    loadingSpinner: document.getElementById('loading-spinner'),
    eventsCount: document.getElementById('events-count'),
    searchInput: document.getElementById('searchInput'),
    tabButtons: document.querySelectorAll('.tab-button'),
    tabPanels: document.querySelectorAll('.tab-panel'),
    clockTime: document.getElementById('clock-time'),
    clockTz: document.getElementById('clock-tz'),
    provinceFocus: document.getElementById('province-focus'),
    toggleRegionsBtn: document.getElementById('toggle-regions-btn'),
    settingsBtn: document.getElementById('settings-btn'),
    regionModal: document.getElementById('region-modal'),
    modalProvinceSelect: document.getElementById('modal-province-select'),
    modalTimezoneSelect: document.getElementById('modal-timezone-select'),
    saveRegionBtn: document.getElementById('save-region-btn'),
    // Properti baru yang kita tambahkan
    rangeFilterButtons: document.querySelectorAll('.period-filter') 
};

    // --- Inisialisasi Aplikasi ---
    function initialize() {
        // Panggilan setup awal
    initMap();
    setInitialDate();
    loadEventsForDate(ELS.eventDateInput.value);
    setupTabs();
        
        // --- Kumpulan Event Listeners ---

    // Listener BARU untuk kalender (menggantikan yang lama)
    ELS.eventDateInput.addEventListener('change', () => {
        // Saat kalender diubah, hapus highlight dari tombol rentang
        removeRangeFilterActiveState();
        loadEventsForDate(ELS.eventDateInput.value);
    });

    // Listener BARU untuk tombol-tombol rentang waktu
    ELS.rangeFilterButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const period = e.currentTarget.dataset.period;
            applyDateFilter(period, e.currentTarget);
        });
    });

    // Listener LAMA yang harus tetap ada
    ELS.searchInput.addEventListener('input', () => filterAndDisplayEvents());
    ELS.provinceFocus.addEventListener('change', (e) => focusOnProvince(e.target.value));
    ELS.toggleRegionsBtn.addEventListener('click', handleRegionToggle);
    ELS.settingsBtn.addEventListener('click', showRegionModal);
    ELS.saveRegionBtn.addEventListener('click', saveRegionPreference);
}

    // --- Inisialisasi Peta ---
    function initMap() {
        const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OSM' });
        const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '© Esri' });
        map = L.map(ELS.mapContainer, { layers: [streetLayer] }).setView([-2.5489, 118.0149], 5);
        L.control.layers({ "Jalan": streetLayer, "Satelit": satelliteLayer }).addTo(map);
        markersLayer = L.markerClusterGroup();
        map.addLayer(markersLayer);
        loadRegionBoundaries();
    }
    
    // --- Fungsi untuk GeoJSON Batas Wilayah ---
    // GANTI SELURUH FUNGSI INI DI js/app.js
async function loadRegionBoundaries() {
    try {
        const response = await fetch('data/indonesia-provinsi.geojson');
        if (!response.ok) throw new Error('Gagal memuat file data/indonesia-provinsi.geojson.');
        const data = await response.json();
        
        data.features.sort((a, b) => {
            const nameA = a.properties.Propinsi || '';
            const nameB = b.properties.Propinsi || '';
            return nameA.localeCompare(nameB);
        });

        data.features.forEach(feature => {
            const provinceName = feature.properties.Propinsi;
            if (provinceName) {
                const option = document.createElement('option');
                option.value = provinceName;
                option.textContent = provinceName;
                ELS.provinceFocus.appendChild(option.cloneNode(true));
                ELS.modalProvinceSelect.appendChild(option);
                provinceBounds[provinceName] = L.geoJSON(feature).getBounds();
            }
        });

        geojsonLayer = L.geoJson(data, {
            style: styleRegion,
            onEachFeature: onEachRegion
        });

        map.addLayer(geojsonLayer);
        geojsonLayer.bringToBack();
        
        // === INI ADALAH PERBAIKANNYA ===
        // Memanggil fungsi untuk memeriksa pengaturan dan memulai jam.
        checkInitialSettings(); 
// GANTI SELURUH FUNGSI startClock() ANDA DENGAN YANG INI

function startClock() {
    // Hentikan interval jam yang lama jika ada, untuk mencegah duplikasi
    if (clockInterval) {
        clearInterval(clockInterval);
    }
    
    // Fungsi untuk memperbarui jam
    function updateClock() {
        const now = new Date();
        const timeZoneName = activeTimezone.split('/')[1]; // Jakarta, Makassar, Jayapura
        let tzLabel = 'WIB'; // Default label
        if (timeZoneName === 'Makassar') tzLabel = 'WITA';
        if (timeZoneName === 'Jayapura') tzLabel = 'WIT';

        // Ini adalah blok yang menyebabkan error
        try {
            // Dapatkan waktu lokal sesuai zona waktu yang aktif
            const localTime = new Date(now.toLocaleString('en-US', { timeZone: activeTimezone }));
            const hours = String(localTime.getHours()).padStart(2, '0');
            const minutes = String(localTime.getMinutes()).padStart(2, '0');
            const seconds = String(localTime.getSeconds()).padStart(2, '0');
            
            if (ELS.clockTime) ELS.clockTime.textContent = `${hours}:${minutes}:${seconds}`;
            if (ELS.clockTz) ELS.clockTz.textContent = tzLabel;

        } catch (error) { // <-- BLOK CATCH YANG HILANG SEBELUMNYA
            console.error("Invalid timezone:", activeTimezone, error);
            // Jika error, tampilkan waktu lokal browser saja sebagai fallback
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            if (ELS.clockTime) ELS.clockTime.textContent = `${hours}:${minutes}:${seconds}`;
            if (ELS.clockTz) ELS.clockTz.textContent = 'Lokal';
            
            // Hentikan update untuk mencegah error berulang
            if (clockInterval) {
                clearInterval(clockInterval);
            }
        }
    }

    updateClock(); // Jalankan sekali segera
    clockInterval = setInterval(updateClock, 1000); // Set interval baru
}
        

    } catch (error) { console.error("Error dalam loadProvinceBoundaries:", error); }
}

    // --- PERBAIKAN: FUNGSI YANG HILANG DIKEMBALIKAN ---
    function styleRegion(feature) {
        const name = feature.properties.Propinsi;
        if (!name) return { fillColor: '#cccccc' };
        let hash = 0;
        for (let i = 0; i < name.length; i++) { hash = name.charCodeAt(i) + ((hash << 5) - hash); }
        let color = '#';
        for (let i = 0; i < 3; i++) { let value = (hash >> (i * 8)) & 0xFF; value = Math.floor((value + 255) / 2); color += ('00' + value.toString(16)).substr(-2); }
        return { fillColor: color, weight: 1, opacity: 1, color: 'white', fillOpacity: 0.6 };
    }
    
    function onEachRegion(feature, layer) {
        const provinceName = feature.properties.Propinsi;
        if (provinceName) { layer.bindTooltip(provinceName, { permanent: false, direction: 'center' }); }
        layer.on('mouseover', (e) => e.target.setStyle({ weight: 3, color: '#666', fillOpacity: 0.7 }));
        layer.on('mouseout', (e) => geojsonLayer.resetStyle(e.target));
    }
    // --- AKHIR PERBAIKAN ---

    function focusOnProvince(provinceName) {
        const button = ELS.toggleRegionsBtn;

        // Jika kembali ke "Seluruh Indonesia"
        if (provinceName === 'indonesia') {
            // Pastikan layer nasional Tampil
            if (!map.hasLayer(geojsonLayer)) {
                map.addLayer(geojsonLayer);
                geojsonLayer.bringToBack();
            }
            // Pastikan tombol dalam keadaan ON secara visual
            button.textContent = 'ON';
            button.classList.remove('bg-gray-200', 'text-gray-700');
            button.classList.add('bg-blue-600', 'text-white');
            
            map.flyTo([-2.5489, 118.0149], 5);
        } 
        // Jika memilih provinsi spesifik
        else if (provinceBounds[provinceName]) {
            // Pastikan layer nasional Mati
            if (map.hasLayer(geojsonLayer)) {
                map.removeLayer(geojsonLayer);
            }
            // Pastikan tombol dalam keadaan OFF secara visual
            button.textContent = 'OFF';
            button.classList.remove('bg-blue-600', 'text-white');
            button.classList.add('bg-gray-200', 'text-gray-700');
            
            map.flyToBounds(provinceBounds[provinceName], { padding: [20, 20] });
        }
    }
    
    function handleRegionToggle() {
        if (!geojsonLayer) return; // Keluar jika layer belum dimuat

        const button = ELS.toggleRegionsBtn;
        const isLayerVisible = map.hasLayer(geojsonLayer);

        if (isLayerVisible) {
            // Jika sedang tampil, matikan
            map.removeLayer(geojsonLayer);
            button.textContent = 'OFF';
            button.classList.remove('bg-blue-600', 'text-white');
            button.classList.add('bg-gray-200', 'text-gray-700');
        } else {
            // Jika sedang mati, nyalakan
            map.addLayer(geojsonLayer);
            geojsonLayer.bringToBack();
            button.textContent = 'ON';
            button.classList.remove('bg-gray-200', 'text-gray-700');
            button.classList.add('bg-blue-600', 'text-white');
        }
    }
    
    function checkInitialRegionPreference() { 
        const savedRegion = localStorage.getItem('aregan_preferred_region'); 
        if (savedRegion) { 
            ELS.provinceFocus.value = savedRegion; 
            focusOnProvince(savedRegion); 
        } else { 
            showRegionModal(); 
        } 
    }

    function showRegionModal() {
        // Isi dropdown sesuai pengaturan yang tersimpan, atau default
        const savedRegion = localStorage.getItem('aregan_preferred_region') || 'indonesia';
        const savedTimezone = localStorage.getItem('aregan_timezone') || 'Asia/Jayapura';
        
        ELS.modalProvinceSelect.value = savedRegion;
        ELS.modalTimezoneSelect.value = savedTimezone;

        ELS.regionModal.classList.add('active');
    }

    function saveRegionPreference() {
        // Ambil nilai dari KEDUA dropdown
        const selectedRegion = ELS.modalProvinceSelect.value;
        const selectedTimezone = ELS.modalTimezoneSelect.value;

        // Simpan KEDUA nilai ke localStorage
        localStorage.setItem('aregan_preferred_region', selectedRegion);
        localStorage.setItem('aregan_timezone', selectedTimezone);

        // Perbarui variabel global dan UI secara langsung
        activeTimezone = selectedTimezone;
        startClock(); // Mulai ulang jam dengan zona waktu baru
        focusOnProvince(selectedRegion); // Fokuskan peta

        ELS.regionModal.classList.remove('active');
    }

    // Fungsi baru untuk memeriksa semua pengaturan saat halaman dimuat
    function checkInitialSettings() {
        const savedRegion = localStorage.getItem('aregan_preferred_region');
        const savedTimezone = localStorage.getItem('aregan_timezone');

        if (savedTimezone) {
            activeTimezone = savedTimezone;
        }
        // Jika tidak ada, biarkan default 'Asia/Jayapura' yang sudah di-set di awal

        // Jalankan jamnya
        startClock(); 
        
        // Periksa wilayah
        if (savedRegion) {
            ELS.provinceFocus.value = savedRegion;
            focusOnProvince(savedRegion);
        } else {
            // Jika tidak ada preferensi wilayah TERSIMPAN, tampilkan modal
            showRegionModal();
        }
    }

    function startClock() {
        // Hentikan interval jam yang lama jika ada, untuk mencegah duplikasi
        if (clockInterval) {
            clearInterval(clockInterval);
        }
        
        // Fungsi untuk memperbarui jam
        function updateClock() {
            const now = new Date();
            const timeZoneName = activeTimezone.split('/')[1]; // Jakarta, Makassar, Jayapura
            let tzLabel = 'WIB'; // Default label
            if (timeZoneName === 'Makassar') tzLabel = 'WITA';
            if (timeZoneName === 'Jayapura') tzLabel = 'WIT';

            try {
                // Dapatkan waktu lokal sesuai zona waktu yang aktif
                const localTime = new Date(now.toLocaleString('en-US', { timeZone: activeTimezone }));
                const hours = String(localTime.getHours()).padStart(2, '0');
                const minutes = String(localTime.getMinutes()).padStart(2, '0');
                const seconds = String(localTime.getSeconds()).padStart(2, '0');
                
                if (ELS.clockTime) ELS.clockTime.textContent = `${hours}:${minutes}:${seconds}`;
                if (ELS.clockTz) ELS.clockTz.textContent = tzLabel;

            } catch (error) {
                console.error("Invalid timezone:", activeTimezone);
                // Jika error, tampilkan waktu lokal browser saja
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                ELS.clockTime.textContent = `${hours}:${minutes}`;
                ELS.clockTz.textContent = 'Lokal';
                clearInterval(clockInterval); // Hentikan update jika timezone salah
            }
        }

        updateClock(); // Jalankan sekali segera
        clockInterval = setInterval(updateClock, 1000); // Set interval baru
    }

    function setInitialDate() {
        // Gunakan 'activeTimezone' yang sudah di-set oleh checkInitialSettings()
        const nowInUserTz = new Date(new Date().toLocaleString('en-US', { timeZone: activeTimezone }));
        const year = nowInUserTz.getFullYear();
        const month = String(nowInUserTz.getMonth() + 1).padStart(2, '0');
        const day = String(nowInUserTz.getDate()).padStart(2, '0');
        ELS.eventDateInput.value = `${year}-${month}-${day}`;
    
    }

    // FUNGSI HELPER BARU YANG ANDAL
function formatDateInActiveTimezone(date) {
    // Trik ini "memaksa" komponen tanggal (tahun, bulan, hari) agar sesuai dengan zona waktu aktif
    const userTzDate = new Date(date.toLocaleString('en-US', { timeZone: activeTimezone }));
    
    const year = userTzDate.getFullYear();
    const month = String(userTzDate.getMonth() + 1).padStart(2, '0');
    const day = String(userTzDate.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

    function setupTabs() {
        const tabContent = {
            legend: `<h3 class="text-lg font-bold mb-4">Legenda Ikon Peta</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-4 text-sm">
                    
                    <div class="font-bold text-blue-600 col-span-full mt-2 border-b">Aparat</div>
                    <div class="flex items-center gap-3"><i class="fas fa-handcuffs fa-fw text-xl text-blue-700"></i><span>Polisi</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-helicopter fa-fw text-xl text-green-800"></i><span>Tentara</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-building-shield fa-fw text-xl text-yellow-500"></i><span>Pemerintah/SAR</span></div>

                    <div class="font-bold text-blue-600 col-span-full mt-3 border-b">Insiden</div>
                    <div class="flex items-center gap-3"><i class="fas fa-car-burst fa-fw text-xl text-red-600"></i><span>Kecelakaan</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-skull-crossbones fa-fw text-xl text-gray-800"></i><span>Kriminal</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-users fa-fw text-xl text-purple-600"></i><span>Kerusuhan</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-user-secret fa-fw text-xl text-indigo-500"></i><span>Orang Hilang</span></div>

                    <div class="font-bold text-blue-600 col-span-full mt-3 border-b">Bencana</div>
                    <div class="flex items-center gap-3"><i class="fas fa-house-flood-water fa-fw text-xl text-blue-500"></i><span>Banjir</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-fire-flame-curved fa-fw text-xl text-orange-500"></i><span>Kebakaran</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-hill-rockslide fa-fw text-xl text-yellow-900"></i><span>Longsor</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-wind fa-fw text-xl text-gray-500"></i><span>Topan</span></div>

                    <div class="font-bold text-blue-600 col-span-full mt-3 border-b">Ekonomi & Acara</div>
                    <div class="flex items-center gap-3"><i class="fas fa-store fa-fw text-xl text-green-600"></i><span>Pasar</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-tag fa-fw text-xl text-pink-500"></i><span>Diskon/Event</span></div>
                    <div class="flex items-center gap-3"><i class="fas fa-star fa-fw text-xl text-yellow-400"></i><span>Review Tempat</span></div>
                    
                    <div class="font-bold text-blue-600 col-span-full mt-3 border-b">Lainnya</div>
                    <div class="flex items-center gap-3"><i class="fas fa-map-marker-alt fa-fw text-xl text-gray-400"></i><span>Default</span></div>
                </div>`,
            about: `<h3 class="text-lg font-bold mb-4">Tentang Nusantara Watch (Project Aregan)</h3><p class="text-gray-700 text-sm">Project AREGAN atau Nusantara Watch adalah platform pemetaan kejadian dinamis dan real time terinspirasi dari Liveuamap.</p><div class="mt-4"><h4 class="font-semibold text-sm">Dibuat oleh:</h4><p class="text-gray-700 text-sm">Proyek ini dikembangkan oleh Ryu-sena (coder).</p></div><div class="mt-4"><h4 class="font-semibold text-sm">Lisensi dan Credits:</h4><p class="text-gray-700 text-sm"> Special thanks kepada developer Leafleat.js.Dirilis di bawah Lisensi MIT.</p><a href="https://opensource.org/licenses/MIT" target="_blank" class="text-blue-600 hover:underline text-xs">Baca selengkapnya </a></div>`
        };
        ELS.tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const targetTab = button.dataset.tab;
                ELS.tabButtons.forEach(btn => btn.classList.remove('active'));
                ELS.tabPanels.forEach(panel => panel.classList.remove('active'));
                button.classList.add('active');
                const targetPanel = document.getElementById(`${targetTab}-panel`);
                targetPanel.classList.add('active');
                if (targetTab !== 'reports' && targetPanel.innerHTML.trim() === '') {
                    targetPanel.innerHTML = tabContent[targetTab];
                }
            });
        });
    }

    async function loadEventsForDate(date) { 
        showLoading(true); 
        clearMapAndList(true); 
        try { 
            const response = await fetch(`api/events.php?date=${date}`); 
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`); 
            allEventsData = await response.json(); 
            filterAndDisplayEvents(); 
        } catch (error) { 
            console.error("Failed to fetch events:", error); 
            ELS.eventsCount.textContent = 'Gagal memuat data.'; 
            displayErrorMessage(); 
        } finally { 
            showLoading(false); 
        } 
    }

    /// GANTI KEDUA FUNGSI LAMA ANDA DENGAN BLOK KODE BARU INI

// FUNGSI HELPER BARU YANG ANDAL
function formatDateInActiveTimezone(date) {
    // Trik ini "memaksa" komponen tanggal (tahun, bulan, hari) agar sesuai dengan zona waktu aktif
    const userTzDate = new Date(date.toLocaleString('en-US', { timeZone: activeTimezone }));
    
    const year = userTzDate.getFullYear();
    const month = String(userTzDate.getMonth() + 1).padStart(2, '0');
    const day = String(userTzDate.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

// FUNGSI applyDateFilter YANG SUDAH DIPERBAIKI
// (Ini adalah versi untuk UI MODAL yang mobile-friendly)
function applyDateFilter(period) {
    // 1. Hitung tanggal mulai dan selesai
    const endDate = new Date();
    const startDate = new Date();

    switch (period) {
        case '7d':
            startDate.setDate(endDate.getDate() - 7);
            break;
        case '14d':
            startDate.setDate(endDate.getDate() - 14);
            break;
        case '1m':
            startDate.setMonth(endDate.getMonth() - 1);
            break;
        case '3m':
            startDate.setMonth(endDate.getMonth() - 3);
            break;
        case '6m':
            startDate.setMonth(endDate.getMonth() - 6);
            break;
        case '1y':
            startDate.setFullYear(endDate.getFullYear() - 1);
            break;
    }

    // 2. Format tanggal MENGGUNAKAN FUNGSI HELPER BARU
    const startFormatted = formatDateInActiveTimezone(startDate);
    const endFormatted = formatDateInActiveTimezone(endDate);
    
    // 3. Panggil API dengan rentang tanggal
    fetchEventsForRange(startFormatted, endFormatted);
}

// Fungsi baru untuk mengambil data berdasarkan rentang
async function fetchEventsForRange(start, end) {
    showLoading(true);
    clearMapAndList(true);
    try {
        const response = await fetch(`api/events.php?start_date=${start}&end_date=${end}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        allEventsData = await response.json();
        filterAndDisplayEvents();
    } catch (error) {
        console.error("Failed to fetch events for range:", error);
        ELS.eventsCount.textContent = 'Gagal memuat data.';
        displayErrorMessage(); // Pastikan Anda punya fungsi ini atau ganti dengan pesan lain
    } finally {
        showLoading(false);
    }
}

// Fungsi utilitas untuk menghapus highlight dari tombol
function removeRangeFilterActiveState() {
    ELS.eventDateInput.disabled = false; // Aktifkan kembali kalender secara default
    ELS.rangeFilterButtons.forEach(btn => btn.classList.remove('active-filter'));
}

// Pastikan Anda punya fungsi ini (dari kode sebelumnya, atau tambahkan)
function displayErrorMessage() {
    ELS.eventsList.innerHTML = `<div class="p-8 text-center text-red-500"><i class="fas fa-exclamation-triangle fa-2x"></i><p class="mt-2">Terjadi kesalahan saat memuat data.</p></div>`;
}

    function filterAndDisplayEvents() { 
        const searchTerm = ELS.searchInput.value.toLowerCase().trim(); 
        const filteredEvents = allEventsData.filter(event => event.event_name.toLowerCase().includes(searchTerm)); 
        clearMapAndList(false); 
        ELS.eventsCount.textContent = `Menampilkan ${filteredEvents.length} dari ${allEventsData.length} laporan.`; 
        if (filteredEvents.length === 0) { 
            displayNoEventsMessage(true); 
        } else { 
            displayEvents(filteredEvents); 
        } 
    }

    function displayEvents(events) { 
        events.forEach(event => { 
            const latLng = [event.latitude, event.longitude]; 
            const wrapper = document.createElement('li'); 
            wrapper.className = 'border-b last:border-b-0'; 
            const summaryItem = document.createElement('div'); 
            summaryItem.className = 'p-4 hover:bg-gray-100 cursor-pointer transition-colors';
            let statusBadge = '';
            // Normalisasi: jika event.status null atau undefined, jadikan string kosong
            const status = event.status || ''; 
            switch (status.toLowerCase()) {
                case 'berlangsung':
                    statusBadge = `<span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-red-600 bg-red-200">${status}</span>`;
                    break;
                case 'tuntas/selesai':
                    statusBadge = `<span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-green-600 bg-green-200">${status}</span>`;
                    break;
                case '': // Jika status kosong
                    statusBadge = ''; // Jangan tampilkan badge apapun
                    break;
                default: // Untuk status lain seperti 'Dimulai', 'Gagal', dll.
                    statusBadge = `<span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-gray-600 bg-gray-200">${status}</span>`;
                    break;
            } 
            summaryItem.innerHTML = `<div class="flex justify-between items-start"><div class="pointer-events-none pr-2"><h3 class="font-bold text-lg">${event.event_name}</h3><p class="text-sm text-gray-600 mt-1"><i class="fas fa-tag mr-2"></i>${event.category}</p></div><div class="flex-shrink-0 ml-2 pointer-events-none">${statusBadge}</div></div>`; 
            const detailContainer = document.createElement('div'); 
            detailContainer.className = 'detail-content bg-white'; 
            wrapper.appendChild(summaryItem); 
            wrapper.appendChild(detailContainer); 
            ELS.eventsList.appendChild(wrapper); 
            const onReportClick = () => { 
                map.flyTo(latLng, 15, { duration: 1 }); 
                toggleDetailView(detailContainer, event); 
                showRadiusOnMap(event.latitude, event.longitude, event.radius); 
            }; 
            summaryItem.addEventListener('click', onReportClick); 
            summaryItem.addEventListener('mouseenter', () => showRadiusOnMap(event.latitude, event.longitude, event.radius)); 
            summaryItem.addEventListener('mouseleave', hideRadiusOnMap); 
            const icon = createIcon(event.icon_class || 'fa-map-marker-alt', '#3b82f6'); 
            const marker = L.marker(latLng, { icon }); 
            marker.bindPopup(`<b>${event.event_name}</b>`).on('click', onReportClick); 
            markersLayer.addLayer(marker); 
        }); 
    }

    function toggleDetailView(container, eventData) { 
        const isCurrentlyOpen = container.classList.contains('open'); 
        if (activeDetailContainer && activeDetailContainer !== container) { 
            activeDetailContainer.classList.remove('open'); 
            activeDetailContainer.innerHTML = ''; 
            activeDetailContainer.classList.remove('p-4', 'border-t'); 
        } 
        if (isCurrentlyOpen) { 
            container.classList.remove('open'); 
            container.innerHTML = ''; 
            container.classList.remove('p-4', 'border-t'); 
            activeDetailContainer = null; 
        } else { 
            container.innerHTML = generateDetailHTML(eventData); 
            container.classList.add('open', 'p-4', 'border-t'); 
            activeDetailContainer = container; 
        } 
    }

    function showRadiusOnMap(lat, lon, radius) { 
        hideRadiusOnMap(); 
        if (radius > 0) { 
            activeRadiusCircle = L.circle([lat, lon], { 
                radius: radius, 
                color: '#ef4444', 
                weight: 2, 
                fill: false, 
                dashArray: '5, 5' 
            }).addTo(map); 
        } 
    }

    function hideRadiusOnMap() { 
        if (activeRadiusCircle) { 
            map.removeLayer(activeRadiusCircle); 
            activeRadiusCircle = null; 
        } 
    }

    function generateDetailHTML(data) { 
        const detailsText = data.details || ''; 
        const short_details = detailsText.substring(0, 200) + (detailsText.length > 200 ? '...' : ''); 
        const gmapsLink = `https://www.google.com/maps?q=${data.latitude},${data.longitude}`; 
        const locationHtml = `<a href="${gmapsLink}" target="_blank" class="text-blue-600 hover:underline text-xs block mt-2"><i class="fas fa-map-marker-alt fa-fw"></i> Lihat di Google Maps</a>`; 
        const sources = data.sources || []; 
        const sourceHtml = sources.length > 0 ? `<div class="mt-3 pt-2 border-t border-gray-200"><h5 class="text-xs font-bold text-gray-500 mb-1">Sumber:</h5><ul class="list-none space-y-1">${sources.slice(0, 2).map(src => `<li><a href="${src}" target="_blank" class="text-blue-600 hover:underline text-xs truncate block"><i class="fas fa-link fa-fw"></i> ${src}</a></li>`).join('')}</ul></div>` : ''; 
        return `<p class="text-sm text-gray-800 leading-relaxed">${short_details}</p>${locationHtml}${sourceHtml}<div class="mt-4 text-right"><a href="view.php?id=${data.id}" target="_blank" class="inline-block bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition-transform transform hover:scale-105 text-sm shadow-md">Laporan Lengkap & Bagikan <i class="fas fa-arrow-right ml-2"></i></a></div>`; 
    }

    function clearMapAndList(resetFullData = true) { 
        ELS.eventsList.innerHTML = ''; 
        markersLayer.clearLayers(); 
        activeDetailContainer = null; 
        hideRadiusOnMap(); 
        if (resetFullData) { 
            allEventsData = []; 
            ELS.searchInput.value = ''; 
        } 
    }

    function showLoading(isLoading) { 
        if (isLoading) { 
            ELS.loadingSpinner.style.display = 'block'; 
            ELS.eventsList.innerHTML = ''; 
            ELS.eventsList.appendChild(ELS.loadingSpinner); 
        } else { 
            ELS.loadingSpinner.style.display = 'none'; 
        } 
    }

    function displayNoEventsMessage(isFilterResult = false) { 
        const message = isFilterResult ? 'Tidak ada laporan yang cocok dengan pencarian Anda.' : 'Tidak ada laporan pada tanggal ini.'; 
        ELS.eventsList.innerHTML = `<div class="p-8 text-center text-gray-500"><i class="fas fa-search-minus fa-2x"></i><p class="mt-2">${message}</p></div>`; 
    }

    function createIcon(iconClass, color) { 
        return L.divIcon({ 
            html: `<i class="fas ${iconClass}" style="color: ${color}; font-size: 24px; text-shadow: 0 0 3px rgba(0,0,0,0.7);"></i>`, 
            className: 'leaflet-div-icon', 
            iconSize: [24, 24], 
            iconAnchor: [12, 24] 
        }); 
    }

    // Memulai aplikasi
    initialize();
});