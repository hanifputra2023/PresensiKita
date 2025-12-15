<!-- Sidebar Mahasiswa -->
<div class="sidebar d-flex flex-column p-3">
    <a href="index.php?page=mahasiswa_dashboard" class="sidebar-brand d-flex align-items-center justify-content-center mb-3">
    <img src="includes/logo-AU.png" alt="Logo" 
         style="height: 45px; width: auto; object-fit: contain;">
</a>
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php?page=mahasiswa_dashboard" class="nav-link <?= $page == 'mahasiswa_dashboard' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=mahasiswa_jadwal" class="nav-link <?= $page == 'mahasiswa_jadwal' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Jadwal Praktikum
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=mahasiswa_scanner" class="nav-link <?= $page == 'mahasiswa_scanner' ? 'active' : '' ?>">
                <i class="fas fa-qrcode"></i> Scan QR Presensi
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=mahasiswa_riwayat" class="nav-link <?= $page == 'mahasiswa_riwayat' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> Riwayat Presensi
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=mahasiswa_izin" class="nav-link <?= $page == 'mahasiswa_izin' ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Pengajuan Izin
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=mahasiswa_inhall" class="nav-link <?= $page == 'mahasiswa_inhall' ? 'active' : '' ?>">
                <i class="fas fa-redo"></i> Inhall
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=mahasiswa_profil" class="nav-link <?= $page == 'mahasiswa_profil' ? 'active' : '' ?>">
                <i class="fas fa-user-cog"></i> Profil
            </a>
        </li>
    </ul>
    
    <!-- Hidden di mobile karena sudah ada logout di navbar mobile -->
    <hr class="bg-light d-none d-md-block">
    <div class="dropdown d-none d-md-block">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
            <strong><?= $_SESSION['username'] ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li><span class="dropdown-item-text text-muted">Mahasiswa</span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>
</div>
