<!-- Sidebar Admin -->
<div class="sidebar d-flex flex-column p-3">
    <a href="index.php?page=admin_dashboard" class="sidebar-brand d-flex align-items-center justify-content-center mb-3">
    <img src="includes/logo-AU.png" alt="Logo" 
         style="height: 45px; width: auto; object-fit: contain;">
</a>


    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php?page=admin_dashboard" class="nav-link <?= $page == 'admin_dashboard' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_kelas" class="nav-link <?= $page == 'admin_kelas' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Kelas
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_lab" class="nav-link <?= $page == 'admin_lab' ? 'active' : '' ?>">
                <i class="fas fa-flask"></i> Laboratorium
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_matakuliah" class="nav-link <?= $page == 'admin_matakuliah' ? 'active' : '' ?>">
                <i class="fas fa-book"></i> Mata Kuliah
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_mahasiswa" class="nav-link <?= $page == 'admin_mahasiswa' ? 'active' : '' ?>">
                <i class="fas fa-user-graduate"></i> Mahasiswa
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_asisten" class="nav-link <?= $page == 'admin_asisten' ? 'active' : '' ?>">
                <i class="fas fa-user-tie"></i> Asisten
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_jadwal" class="nav-link <?= ($page == 'admin_jadwal' || $page == 'admin_materi') ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> Jadwal
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_users" class="nav-link <?= $page == 'admin_users' ? 'active' : '' ?>">
                <i class="fas fa-user-cog"></i> Users
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_laporan" class="nav-link <?= $page == 'admin_laporan' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Laporan
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_statistik" class="nav-link <?= $page == 'admin_statistik' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i> Statistik
            </a>
        </li>
        <li class="nav-item">
            <a href="index.php?page=admin_log" class="nav-link <?= $page == 'admin_log' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> Log Aktivitas
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
            <li><span class="dropdown-item-text text-muted">Admin</span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="index.php?page=logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
        </ul>
    </div>
</div>
