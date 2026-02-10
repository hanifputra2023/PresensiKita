<!-- Sidebar Combined (Admin, Dosen, Asisten, Mahasiswa) -->
<style>
    /* Hilangkan background putih saat hover */
    .btn-simple-hover:hover {
        background-color: transparent !important;
        color: #fff !important;
        opacity: 0.75; /* Efek redup saat disorot */
    }
</style>
<div class="sidebar d-flex flex-column p-3">
    <?php 
    // Tentukan link dashboard berdasarkan role
    $dashboard_page = isset($_SESSION['role']) ? $_SESSION['role'] . '_dashboard' : 'login';
    ?>
    <a href="index.php?page=<?= $dashboard_page ?>" class="sidebar-brand d-flex align-items-center justify-content-center mb-3">
        <img src="includes/logo-AU.png" alt="Logo Light" class="logo-light" 
             style="height: 45px; width: auto; object-fit: contain;">
        <img src="includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png" alt="Logo Dark" class="logo-dark" 
             style="height: 45px; width: auto; object-fit: contain;">
    </a>
    
    <ul class="nav nav-pills flex-column mb-auto">
        
        <?php if ($_SESSION['role'] == 'admin') : ?>
            <!-- MENU ADMIN -->
            <li class="nav-item"><a href="index.php?page=admin_dashboard" class="nav-link <?= is_active('admin_dashboard') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a href="index.php?page=admin_kelas" class="nav-link <?= is_active('admin_kelas') ?>"><i class="fas fa-users"></i> Kelas</a></li>
            <li class="nav-item"><a href="index.php?page=admin_lab" class="nav-link <?= is_active('admin_lab') ?>"><i class="fas fa-flask"></i> Laboratorium</a></li>
            <li class="nav-item"><a href="index.php?page=admin_matakuliah" class="nav-link <?= is_active('admin_matakuliah') ?>"><i class="fas fa-book"></i> Mata Kuliah</a></li>
            <li class="nav-item"><a href="index.php?page=admin_mahasiswa" class="nav-link <?= is_active('admin_mahasiswa') ?>"><i class="fas fa-user-graduate"></i> Mahasiswa</a></li>
            <li class="nav-item"><a href="index.php?page=admin_asisten" class="nav-link <?= is_active('admin_asisten') ?>"><i class="fas fa-user-tie"></i> Asisten</a></li>
            <li class="nav-item"><a href="index.php?page=admin_jadwal" class="nav-link <?= is_active(['admin_jadwal', 'admin_materi']) ?>"><i class="fas fa-calendar-alt"></i> Jadwal</a></li>
            <li class="nav-item"><a href="index.php?page=admin_izin_asisten" class="nav-link <?= is_active('admin_izin_asisten') ?>"><i class="fas fa-user-clock"></i> Izin Asisten</a></li>
            <li class="nav-item"><a href="index.php?page=admin_pengumuman" class="nav-link <?= is_active('admin_pengumuman') ?>"><i class="fas fa-bullhorn"></i> Pengumuman</a></li>
            <li class="nav-item"><a href="index.php?page=admin_broadcast" class="nav-link <?= is_active('admin_broadcast') ?>"><i class="fab fa-whatsapp"></i> Broadcast WA</a></li>
            <li class="nav-item"><a href="index.php?page=admin_users" class="nav-link <?= is_active('admin_users') ?>"><i class="fas fa-user-cog"></i> Users</a></li>
            <li class="nav-item"><a href="index.php?page=admin_laporan" class="nav-link <?= is_active('admin_laporan') ?>"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            <li class="nav-item"><a href="index.php?page=admin_berita_acara" class="nav-link <?= is_active('admin_berita_acara') ?>"><i class="fas fa-file-signature"></i> Berita Acara</a></li>
            <li class="nav-item"><a href="index.php?page=admin_statistik" class="nav-link <?= is_active('admin_statistik') ?>"><i class="fas fa-chart-pie"></i> Statistik</a></li>
            <li class="nav-item"><a href="index.php?page=admin_log" class="nav-link <?= is_active('admin_log') ?>"><i class="fas fa-history"></i> Log Aktivitas</a></li>
            <li class="nav-item"><a href="index.php?page=admin_bantuan" class="nav-link <?= is_active('admin_bantuan') ?>"><i class="fas fa-headset"></i> Pesan Bantuan</a></li>

        <?php elseif ($_SESSION['role'] == 'mahasiswa') : ?>
            <!-- MENU MAHASISWA -->
            <li class="nav-item"><a href="index.php?page=mahasiswa_dashboard" class="nav-link <?= is_active('mahasiswa_dashboard') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_jadwal" class="nav-link <?= is_active('mahasiswa_jadwal') ?>"><i class="fas fa-calendar-alt"></i> Jadwal Praktikum</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_scanner" class="nav-link <?= is_active('mahasiswa_scanner') ?>"><i class="fas fa-qrcode"></i> Scan QR Presensi</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_riwayat" class="nav-link <?= is_active('mahasiswa_riwayat') ?>"><i class="fas fa-history"></i> Riwayat Presensi</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_leaderboard" class="nav-link <?= is_active('mahasiswa_leaderboard') ?>"><i class="fas fa-trophy"></i> Papan Peringkat</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_jurnal" class="nav-link <?= is_active('mahasiswa_jurnal') ?>"><i class="fas fa-book-open"></i> Jurnal Praktikum</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_kuis" class="nav-link <?= is_active(['mahasiswa_kuis', 'mahasiswa_kuis_kerjakan']) ?>"><i class="fas fa-pencil-alt"></i> Kuis Online</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_izin" class="nav-link <?= is_active('mahasiswa_izin') ?>"><i class="fas fa-envelope"></i> Pengajuan Izin</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_inhall" class="nav-link <?= is_active('mahasiswa_inhall') ?>"><i class="fas fa-redo"></i> Inhall</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_bantuan" class="nav-link <?= is_active('mahasiswa_bantuan') ?>"><i class="fas fa-headset"></i> Pusat Bantuan</a></li>
            <li class="nav-item"><a href="index.php?page=mahasiswa_profil" class="nav-link <?= is_active('mahasiswa_profil') ?>"><i class="fas fa-user-cog"></i> Profil</a></li>

        <?php elseif ($_SESSION['role'] == 'asisten') : ?>
            <!-- MENU ASISTEN -->
            <li class="nav-item"><a href="index.php?page=asisten_dashboard" class="nav-link <?= is_active('asisten_dashboard') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_jadwal" class="nav-link <?= is_active(['asisten_jadwal', 'asisten_materi']) ?>"><i class="fas fa-calendar-alt"></i> Jadwal Saya</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_kuis" class="nav-link <?= is_active(['asisten_kuis', 'asisten_kuis_detail']) ?>"><i class="fas fa-pencil-alt"></i> Manajemen Kuis</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_berita_acara" class="nav-link <?= is_active('asisten_berita_acara') ?>"><i class="fas fa-file-signature"></i> Berita Acara</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_qrcode" class="nav-link <?= is_active('asisten_qrcode') ?>"><i class="fas fa-qrcode"></i> Generate QR</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_monitoring" class="nav-link <?= is_active('asisten_monitoring') ?>"><i class="fas fa-tv"></i> Monitoring</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_presensi_manual" class="nav-link <?= is_active('asisten_presensi_manual') ?>"><i class="fas fa-edit"></i> Presensi Manual</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_izin" class="nav-link <?= is_active('asisten_izin') ?>"><i class="fas fa-file-alt"></i> Izin Mahasiswa</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_pengajuan_izin" class="nav-link <?= is_active('asisten_pengajuan_izin') ?>"><i class="fas fa-user-clock"></i> Pengajuan Izin</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_rekap" class="nav-link <?= is_active('asisten_rekap') ?>"><i class="fas fa-chart-bar"></i> Rekap</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_statistik" class="nav-link <?= is_active('asisten_statistik') ?>"><i class="fas fa-chart-pie"></i> Statistik</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_bantuan" class="nav-link <?= is_active('asisten_bantuan') ?>"><i class="fas fa-headset"></i> Pusat Bantuan</a></li>
            <li class="nav-item"><a href="index.php?page=asisten_profil" class="nav-link <?= is_active('asisten_profil') ?>"><i class="fas fa-user-cog"></i> Profil</a></li>

        <?php endif; ?>

    </ul>
    
    <hr class="bg-light d-none d-md-block">
    <div class="d-none d-md-block">
        <div class="d-flex align-items-center text-white mb-3 px-2">
            <!-- Foto Profil Sidebar (Desktop) -->
            <img src="<?= isset($header_foto) ? $header_foto : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['username'] ?? 'User') . '&background=random&color=fff&rounded=true' ?>" 
                 alt="Profile" 
                 class="rounded-circle me-3" 
                 style="width: 48px; height: 48px; object-fit: cover; border: 2px solid rgba(255,255,255,0.2);">
            <div class="lh-1">
                <div class="fw-bold"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <!-- Menampilkan Role secara otomatis (Huruf pertama kapital) -->
                <small class="text-white-50"><?= ucfirst($_SESSION['role']) ?></small>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <a href="#" class="btn btn-outline-light btn-simple-hover flex-fill theme-toggle" title="Mode Gelap"><i class="fas fa-moon"></i></a>
            <a href="index.php?page=logout" class="btn btn-outline-light btn-simple-hover flex-fill" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
</div>
<script>
// Anti-FOUC + Scroll Restoration: Segera setelah sidebar dirender
(function() {
    var sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.add('fouc-ready');
    }
    
    // Restore scroll position untuk container sidebar
    var sidebarContainer = document.querySelector('.col-md-3.col-lg-2');
    if (sidebarContainer) {
        var savedPos = sessionStorage.getItem('sidebarScrollPos');
        if (savedPos) sidebarContainer.scrollTop = parseInt(savedPos);
    }
})();
</script>