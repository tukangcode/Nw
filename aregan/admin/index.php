<?php
require_once '../includes/auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NW</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- 1. Sertakan library Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
            <div class="flex items-center">
                <span class="text-gray-600 mr-4">Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?>!</strong></span>
                <a href="logout.php" class="text-sm text-red-500 hover:text-red-700 font-semibold"><i class="fas fa-sign-out-alt mr-1"></i>Logout</a>
            </div>
        </div>
    </header>

    <main>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Bagian Statistik Baru -->
            <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
                <div class="flex justify-between items-center border-b pb-3 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Statistik Laporan</h2>
                    <!-- 2. Tombol Filter Waktu -->
                    <div class="flex items-center gap-2" id="period-filters">
                        <button class="period-btn px-3 py-1 text-sm rounded-md" data-period="week">7 Hari</button>
                        <button class="period-btn px-3 py-1 text-sm rounded-md active" data-period="month">30 Hari</button>
                        <button class="period-btn px-3 py-1 text-sm rounded-md" data-period="3_months">3 Bulan</button>
                        <button class="period-btn px-3 py-1 text-sm rounded-md" data-period="year">1 Tahun</button>
                    </div>
                </div>
                <!-- 3. Canvas untuk Grafik -->
                <div class="relative h-96">
                    <canvas id="statsChart"></canvas>
                </div>
            </div>

            <!-- Kartu Navigasi yang Sudah Ada -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="review_submissions.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-xl transition-shadow">
                    <div class="p-5 flex items-center gap-4"><i class="fas fa-inbox fa-3x text-yellow-500"></i><div><dt class="text-sm font-medium text-gray-500">Laporan Masuk</dt><dd class="text-2xl font-bold text-gray-900">Tinjau Laporan</dd></div></div>
                </a>
                <a href="manage_events.php" class="bg-white overflow-hidden shadow rounded-lg hover:shadow-xl transition-shadow">
                    <div class="p-5 flex items-center gap-4"><i class="fas fa-calendar-alt fa-3x text-blue-500"></i><div><dt class="text-sm font-medium text-gray-500">Kelola Laporan Publik</dt><dd class="text-2xl font-bold text-gray-900">Tambah/Ubah Laporan</dd></div></div>
                </a>
            </div>
        </div>
    </main>

    <!-- 4. JavaScript untuk Menggambar Grafik -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const ctx = document.getElementById('statsChart').getContext('2d');
        let myChart; // Variabel untuk menyimpan instance grafik agar bisa di-destroy

        const chartColors = [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
            '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#65A30D'
        ];

        // Fungsi untuk mengambil data dan merender grafik
        async function fetchAndRenderChart(period) {
            try {
                const response = await fetch(`../api/stats.php?period=${period}`);
                if (!response.ok) throw new Error('Network response was not ok.');
                
                const statsData = await response.json();

                // Hancurkan grafik lama sebelum menggambar yang baru
                if (myChart) {
                    myChart.destroy();
                }

                myChart = new Chart(ctx, {
                    type: 'bar', // Tipe grafik: bar, line, doughnut, pie
                    data: {
                        labels: statsData.labels,
                        datasets: [{
                            label: 'Jumlah Laporan',
                            data: statsData.data,
                            backgroundColor: chartColors,
                            borderColor: chartColors.map(c => c + 'B3'), // Tambah transparansi untuk border
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } } // Sumbu Y mulai dari 0
                        },
                        plugins: {
                            legend: { display: false }, // Sembunyikan legenda karena label sudah jelas
                            title: {
                                display: true,
                                text: `Laporan Paling Sering Terjadi (${document.querySelector(`.period-btn[data-period="${period}"]`).textContent})`
                            }
                        }
                    }
                });

            } catch (error) {
                console.error('Failed to fetch stats:', error);
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height); // Bersihkan canvas jika error
                ctx.fillText('Gagal memuat data statistik.', 10, 50);
            }
        }

        // Event listener untuk tombol filter
        const filterButtons = document.querySelectorAll('.period-btn');
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Hapus style 'active' dari semua tombol
                filterButtons.forEach(btn => btn.classList.remove('active', 'bg-blue-500', 'text-white'));
                // Tambahkan style 'active' ke tombol yang diklik
                button.classList.add('active', 'bg-blue-500', 'text-white');
                
                const period = button.dataset.period;
                fetchAndRenderChart(period);
            });
        });

        // Muat grafik untuk periode default (30 hari) saat halaman pertama kali dibuka
        fetchAndRenderChart('month');
        // Style untuk tombol default
        document.querySelector('.period-btn[data-period="month"]').classList.add('active', 'bg-blue-500', 'text-white');
    });
    </script>
    <style>.period-btn.active { background-color: #3B82F6; color: white; } .period-btn { transition: all 0.2s; }</style>

</body>
</html>