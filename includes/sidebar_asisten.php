<!-- Sidebar Asisten -->
<div class="sidebar d-flex flex-column p-3">
    <a href="index.php?page=asisten_dashboard" class="sidebar-brand d-flex align-items-center justify-content-center mb-3">
        <img src="includes/logo-AU.png" alt="Logo Light" class="logo-light" 
             style="height: 45px; width: auto; object-fit: contain;">
        <img src="includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png" alt="Logo Dark" class="logo-dark" 
             style="height: 45px; width: auto; object-fit: contain;">
    </a>
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php?page=asisten_dashboard" class="nav-link <?= is_active('asisten_dashboard') ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_jadwal" class="nav-link <?= is_active(['asisten_jadwal', 'asisten_materi']) ?>">
                <i class="fas fa-calendar-alt"></i> Jadwal Saya
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_qrcode" class="nav-link <?= is_active('asisten_qrcode') ?>">
                <i class="fas fa-qrcode"></i> Generate QR
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_monitoring" class="nav-link <?= is_active('asisten_monitoring') ?>">
                <i class="fas fa-tv"></i> Monitoring
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_presensi_manual" class="nav-link <?= is_active('asisten_presensi_manual') ?>">
                <i class="fas fa-edit"></i> Presensi Manual
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_izin" class="nav-link <?= is_active('asisten_izin') ?>">
                <i class="fas fa-file-alt"></i> Izin Mahasiswa
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_pengajuan_izin" class="nav-link <?= is_active('asisten_pengajuan_izin') ?>">
                <i class="fas fa-user-clock"></i> Pengajuan Izin
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_rekap" class="nav-link <?= is_active('asisten_rekap') ?>">
                <i class="fas fa-chart-bar"></i> Rekap
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_statistik" class="nav-link <?= is_active('asisten_statistik') ?>">
                <i class="fas fa-chart-pie"></i> Statistik
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=asisten_profil" class="nav-link <?= is_active('asisten_profil') ?>">
                <i class="fas fa-user-cog"></i> Profil
            </a>
        </li>
    </ul>
    
    <!-- Hidden di mobile karena sudah ada logout di navbar mobile -->
    <hr class="bg-light d-none d-md-block">
    <div class="dropdown d-none d-md-block">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
            <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li><span class="dropdown-item-text text-muted"><?= sapaan_waktu() ?>, Asisten</span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item theme-toggle" href="#"><i class="fas fa-moon me-2"></i><span>Mode Gelap</span></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>
</div>
