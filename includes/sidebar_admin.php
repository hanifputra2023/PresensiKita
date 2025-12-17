<!-- Sidebar Admin -->
<div class="sidebar d-flex flex-column p-3">
    <a href="index.php?page=admin_dashboard" class="sidebar-brand d-flex align-items-center justify-content-center mb-3">
        <img src="includes/logo-AU.png" alt="Logo Light" class="logo-light" 
             style="height: 45px; width: auto; object-fit: contain;">
        <img src="includes/Gemini_Generated_Image_ykixgyykixgyykix-removebg-preview (1).png" alt="Logo Dark" class="logo-dark" 
             style="height: 45px; width: auto; object-fit: contain;">
    </a>


    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php?page=admin_dashboard" class="nav-link <?= is_active('admin_dashboard') ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_kelas" class="nav-link <?= is_active('admin_kelas') ?>">
                <i class="fas fa-users"></i> Kelas
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_lab" class="nav-link <?= is_active('admin_lab') ?>">
                <i class="fas fa-flask"></i> Laboratorium
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_matakuliah" class="nav-link <?= is_active('admin_matakuliah') ?>">
                <i class="fas fa-book"></i> Mata Kuliah
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_mahasiswa" class="nav-link <?= is_active('admin_mahasiswa') ?>">
                <i class="fas fa-user-graduate"></i> Mahasiswa
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_asisten" class="nav-link <?= is_active('admin_asisten') ?>">
                <i class="fas fa-user-tie"></i> Asisten
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_jadwal" class="nav-link <?= is_active(['admin_jadwal', 'admin_materi']) ?>">
                <i class="fas fa-calendar-alt"></i> Jadwal
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_users" class="nav-link <?= is_active('admin_users') ?>">
                <i class="fas fa-user-cog"></i> Users
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_laporan" class="nav-link <?= is_active('admin_laporan') ?>">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_statistik" class="nav-link <?= is_active('admin_statistik') ?>">
                <i class="fas fa-chart-pie"></i> Statistik
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_log" class="nav-link <?= is_active('admin_log') ?>">
                <i class="fas fa-history"></i> Log Aktivitas
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
            <li><span class="dropdown-item-text text-muted"><?= sapaan_waktu() ?>, Admin</span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item theme-toggle" href="#"><i class="fas fa-moon me-2"></i><span>Mode Gelap</span></a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>
</div>
