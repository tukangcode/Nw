<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AREGAN - Advanced Report Generator</title>
    
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🗺️</text></svg>">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in-section { animation: fadeIn 1s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 antialiased">

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="text-2xl font-bold text-blue-600">
                <i class="fas fa-map-marked-alt mr-2"></i>AREGAN
            </div>
            <nav>
                <a href="aregan/index.html" class="bg-blue-600 text-white font-bold py-2 px-5 rounded-full hover:bg-blue-700 transition-colors shadow-lg">
                    Lihat Peta Laporan
                </a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-white">
        <div class="container mx-auto px-6 py-20 text-center">
            <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 leading-tight">
                Visualisasikan Laporan, Pahami Kejadian.
            </h1>
            <p class="mt-4 text-lg md:text-xl text-gray-600 max-w-3xl mx-auto">
                AREGAN (Advanced Report Generator) adalah platform untuk membuat, mengelola, dan membagikan laporan kejadian berbasis lokasi dengan visualisasi peta yang interaktif dan informatif.
            </p>
            <div class="mt-8">
                <a href="aregan/index.html" class="bg-blue-600 text-white font-bold py-4 px-8 rounded-full text-lg hover:bg-blue-700 transition-colors shadow-xl transform hover:scale-105">
                    Mulai Jelajahi Peta <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Fitur Utama -->
    <section id="features" class="py-20">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Fitur Unggulan</h2>
                <p class="mt-2 text-gray-600">Alat canggih untuk laporan yang lebih baik.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <!-- Fitur 1: Peta Interaktif -->
                <div class="bg-white p-8 rounded-xl shadow-lg text-center transform hover:-translate-y-2 transition-transform">
                    <div class="bg-blue-100 text-blue-600 rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-map-location-dot fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Peta Interaktif</h3>
                    <p class="text-gray-600">Visualisasikan laporan pada peta dinamis dengan layer satelit, pencarian lokasi, dan penanda (marker) yang jelas.</p>
                </div>
                <!-- Fitur 2: Manajemen Laporan -->
                <div class="bg-white p-8 rounded-xl shadow-lg text-center transform hover:-translate-y-2 transition-transform">
                    <div class="bg-green-100 text-green-600 rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-edit fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Manajemen Admin</h3>
                    <p class="text-gray-600">Cpanel admin yang kuat untuk menambah, mengedit, menghapus, dan meninjau laporan dengan mudah, lengkap dengan statistik.</p>
                </div>
                <!-- Fitur 3: Berbagi & Ekspor -->
                <div class="bg-white p-8 rounded-xl shadow-lg text-center transform hover:-translate-y-2 transition-transform">
                    <div class="bg-purple-100 text-purple-600 rounded-full h-16 w-16 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-share-alt fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">Berbagi & Ekspor</h3>
                    <p class="text-gray-600">Bagikan laporan tunggal dengan mudah ke media sosial atau ekspor data laporan dalam format CSV dan JSON untuk analisis lebih lanjut.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contoh Tampilan Aplikasi -->
    <section class="bg-white py-20">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Lihat AREGAN Beraksi</h2>
            <p class="mt-2 text-gray-600 mb-8">Antarmuka yang bersih dan fungsional.</p>
            <div class="bg-gray-200 p-4 rounded-xl shadow-2xl">
                <!-- Ganti 'your_screenshot_url.jpg' dengan URL screenshot aplikasi Anda -->
                <img src="https://iili.io/FYaZGzF.png" alt="Aregan UI" class="rounded-lg w-full">
            </div>
        </div>
    </section>

    <!-- Tentang & Kredit -->
    <section id="about" class="py-20">
        <div class="container mx-auto px-6 text-center max-w-4xl">
            <div class="bg-blue-600 text-white p-10 rounded-xl shadow-lg">
                <h2 class="text-3xl font-bold">Tentang Proyek Ini</h2>
                <p class="mt-4 leading-relaxed">
                    AREGAN dibangun untuk memudahkan citizen journalist, conflcit monitoring dan riset area, segala bentuk penyalagunaan buka tanggjung jawab author mauoub kawar author Ami.
                </p>
                <div class="mt-8 border-t border-blue-500 pt-6">
                    <h4 class="font-semibold">Dikembangkan oleh:</h4>
                    <p class="text-2xl font-bold mt-2">Ryu-sena</p>
                    <a href="https://github.com/tukangcode" target="_blank" class="mt-2 inline-block text-blue-200 hover:text-white transition-colors">
                        <i class="fab fa-github"></i> github.com/tukangcode
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-6 text-center text-sm">
            <p>© <?php echo date("Y"); ?> AREGAN.Apache 2 license </p>
        </div>
    </footer>

</body>
</html>