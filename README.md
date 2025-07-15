# Nusantara Watch 🗺️

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![Technology](https://img.shields.io/badge/Tech-PHP%20%7C%20JS%20%7C%20MySQL-lightgrey)](https://github.com/)

**[English Version](#english) | [Versi Bahasa Indonesia](#bahasa-indonesia)**

Sebuah sistem pelaporan kejadian berbasis peta yang dinamis, interaktif, dan berskala nasional untuk memantau berbagai peristiwa di seluruh Indonesia.

![Nusantara Watch Screenshot](nwlogo.png) 


---

## <a name="bahasa-indonesia"></a>Bahasa Indonesia

### ⚠️ Status Proyek: Dihentikan Sementara (On Hold)
**Pengembangan proyek ini dihentikan sementara. Kenapa? Sejujurnya, saya lelah dengan proses debugging yang tiada henti dan ingin mencoba proyek lain dulu untuk menyegarkan pikiran. Wkwkwk.**

Mungkin suatu saat proyek ini akan dilanjutkan... mungkin juga tidak. Terima kasih telah mampir!

---

### 🎯 Tujuan Proyek
Tujuan utama Nusantara Watch adalah iseng dan untuk pembelajaran: membangun sebuah "Liveuamap buatan rumah" yang gratis, open-source, dan berfokus di Indonesia. Proyek ini bertujuan untuk menyediakan platform terpusat untuk memvisualisasikan berbagai macam kejadian—mulai dari insiden, bencana alam, hingga acara publik—dalam format peta yang mudah dipahami dan diakses oleh siapa saja.

### ✨ Fitur Utama
- **Peta Interaktif Nasional:** Menggunakan Leaflet.js dengan batas wilayah provinsi dan cluster marker untuk performa tinggi.
- **Filter Data Dinamis:**
  - Filter berdasarkan tanggal spesifik.
  - Filter rentang waktu (7 hari, 1 bulan, 1 tahun, dll).
  - Pencarian laporan secara real-time.
- **Manajemen Pengguna & Pengaturan:**
  - Pengaturan fokus wilayah (nasional atau per provinsi).
  - Pengaturan zona waktu (WIB/WITA/WIT) yang disimpan di sisi pengguna.
- **Detail Laporan Informatif:**
  - Mode "dropdown" untuk detail cepat di daftar laporan.
  - Halaman detail terpisah yang bisa dibagikan (`view.php`).
- **C-Panel Admin yang Powerfull:**
  - Dashboard statistik dengan grafik (Chart.js) yang dinamis.
  - Manajemen Laporan penuh (CRUD - Create, Read, Update, Delete).
  - Sistem upload gambar dan manajemen link sumber.
  - Fitur "Input Cepat" untuk memasukkan data dari teks terformat.
- **Desain Responsif:** UI yang beradaptasi untuk pengalaman yang baik di desktop maupun perangkat mobile (menggunakan modal filter).

### 🛠️ Berbagai hal yang belum terselesaikan
- [ ] **(Prioritas Utama) Perbaiki Bug Tampilan Jam:** Mendiagnosis dan memperbaiki mengapa jam digital tidak muncul di antarmuka utama.
- [ ] **Perbaikan & Penyempurnaan:**
    - [ ] Menyempurnakan sistem paginasi di halaman admin.
    - [ ] Melakukan audit dan perbaikan UI/UX di halaman admin agar lebih intuitif.
    - [ ] Menambahkan validasi di sisi klien (JavaScript) pada form admin untuk feedback instan.
- [ ] **Fitur Baru:**
    - [ ] Mengimplementasikan fitur "Highlight of the Day" atau kejadian penting.
    - [ ] Menambahkan opsi filter lanjutan di peta (misalnya, berdasarkan kategori laporan).
    - [ ] Membangun sistem submisi laporan dari publik dengan alur moderasi oleh admin.
    - [ ] Menambahkan sistem komentar pada halaman detail laporan.
- [ ] **Jangka Panjang:**
    - [ ] API publik untuk pengembang lain.
    - [ ] Sistem notifikasi (Email/Push Notification) untuk kejadian penting.

### 📜 Lisensi
Proyek ini dilisensikan di bawah Lisensi MIT. Lihat file `LICENSE` untuk detail lebih lanjut.

### ⚖️ Penafian (Disclaimer)
Data dan informasi yang disajikan di platform Nusantara Watch adalah untuk tujuan informasi umum saja. Informasi ini mungkin dikumpulkan dari berbagai sumber publik dan tidak dijamin akurat, lengkap, atau terkini.

Para pengembang tidak bertanggung jawab atas:
- Ketidakakuratan, kesalahan, atau kelalaian dalam konten yang disajikan.
- Kerugian atau kerusakan apa pun yang timbul dari penggunaan atau ketergantungan pada informasi di situs ini.
- Penyalahgunaan informasi oleh pihak ketiga.

Pengguna bertanggung jawab penuh atas tindakan apa pun yang mereka ambil berdasarkan informasi yang ditemukan di platform ini.

---
<br>

## <a name="english"></a>English

### ⚠️ Project Status: On Hold
**Development for this project is currently on hold. Why? To be honest, I'm tired of the relentless debugging and want to try another project for a while to refresh my mind. Lol.**

Maybe I'll come back to it someday... or maybe not. Thanks for checking it out!

---

### 🎯 Project Goal
The main goal of Nusantara Watch is a personal learning project: to build a free, open-source, "homemade Liveuamap" with a focus on Indonesia. This project aims to provide a centralized platform to visualize various events—from incidents and natural disasters to public events—on an easy-to-understand and accessible map format.

### ✨ Core Features
- **Nationwide Interactive Map:** Powered by Leaflet.js with provincial boundaries and marker clustering for high performance.
- **Dynamic Data Filtering:**
  - Filter by a specific date.
  - Time-range filters (7 days, 1 month, 1 year, etc.).
  - Real-time event search.
- **User Management & Settings:**
  - User-configurable region focus (nationwide or per-province).
  - User-configurable timezone settings (WIB/WITA/WIT) saved locally.
- **Informative Event Details:**
  - "Dropdown" mode for quick details in the event list.
  - Separate, shareable detail pages (`view.php`).
- **Powerful Admin C-Panel:**
  - Statistical dashboard with dynamic charts (Chart.js).
  - Full Report Management (CRUD - Create, Read, Update, Delete).
  - Image upload system and source link management.
  - "Quick Input" feature to parse and populate the form from formatted text.
- **Responsive Design:** A UI that adapts for a good experience on both desktop and mobile devices (using a filter modal).

### 🛠️ To-Do stuff i not manage finish it
- [ ] **(Top Priority) Fix Clock Display Bug:** Diagnose and fix the issue where the digital clock fails to appear on the main interface.
- [ ] **Fixes & Refinements:**
    - [ ] Refine the pagination system on the admin page.
    - [ ] Conduct a UI/UX audit and improve the admin pages for better intuition.
    - [ ] Add client-side validation to admin forms for instant feedback.
- [ ] **New Features:**
    - [ ] Implement a "Highlight of the Day" or major event feature.
    - [ ] Add advanced filtering options on the map (e.g., by report category).
    - [ ] Build a public submission system with an admin moderation workflow.
    - [ ] Add a comment system to the event detail pages.
- [ ] **Long-Term:**
    - [ ] Public API for other developers.
    - [ ] Notification system (Email/Push Notification) for important events.

### 📜 License
This project is licensed under the MIT License. See the `LICENSE` file for more details.

### ⚖️ Disclaimer
The data and information presented on the Nusantara Watch platform are for general informational purposes only. This information may be gathered from various public sources and is not guaranteed to be accurate, complete, or up-to-date.

The developers are not liable for:
- Any inaccuracies, errors, or omissions in the presented content.
- Any loss or damage arising from the use of or reliance on the information on this site.
- Misuse of information by any third party.

Users are solely responsible for any actions they take based on the information found on this platform.
