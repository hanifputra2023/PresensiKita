<?php
$page = 'mahasiswa_leaderboard';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kelas = $mahasiswa['kode_kelas'];

// Logika Poin: Hadir = 10, Izin/Sakit = 5, Alpha = 0
// Query untuk mengambil data leaderboard satu kelas
$query_leaderboard = "
    SELECT m.nim, m.nama, m.foto,
    SUM(CASE 
        WHEN p.status = 'hadir' THEN 10 
        WHEN p.status = 'izin' THEN 5 
        WHEN p.status = 'sakit' THEN 5 
        ELSE 0 
    END) as points,
    SUM(CASE WHEN p.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir
    FROM mahasiswa m
    LEFT JOIN presensi_mahasiswa p ON m.nim = p.nim
    WHERE m.kode_kelas = '$kelas'
    GROUP BY m.nim
    ORDER BY points DESC, total_hadir DESC, m.nama ASC
    LIMIT 50
";
$result = mysqli_query($conn, $query_leaderboard);

$leaderboard = [];
$my_rank = 0;
$my_points = 0;
$rank = 1;

while ($row = mysqli_fetch_assoc($result)) {
    $row['rank'] = $rank;
    // Fallback foto jika kosong
    if (empty($row['foto']) || !file_exists($row['foto'])) {
        $row['foto'] = 'https://ui-avatars.com/api/?name=' . urlencode($row['nama']) . '&background=random&color=fff&rounded=true';
    }
    
    $leaderboard[] = $row;
    
    if ($row['nim'] == $nim) {
        $my_rank = $rank;
        $my_points = $row['points'];
    }
    $rank++;
}


// Pisahkan Top 3 untuk tampilan podium
$top3 = array_slice($leaderboard, 0, 3);
// Sisanya untuk list di bawah
$rest = array_slice($leaderboard, 3);
?>
<?php include 'includes/header.php'; ?>

<style>
    .leaderboard-header {
        background: var(--banner-gradient);
        border-radius: 20px;
        padding: 30px;
        color: white;
        margin-bottom: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }
    .leaderboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
    }
    
    /* Podium Styles */
    .podium-container {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        gap: 15px;
        margin-bottom: 40px;
        min-height: 280px;
    }
    .podium-item {
        text-align: center;
        position: relative;
        transition: transform 0.3s;
    }
    .podium-item:hover {
        transform: translateY(-10px);
    }
    .podium-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 4px solid white;
        box-shadow: var(--card-shadow);
        margin-bottom: -40px;
        position: relative;
        z-index: 2;
        object-fit: cover;
        background: var(--bg-card);
    }
    .podium-base {
        border-radius: 15px 15px 0 0;
        padding: 50px 20px 20px;
        color: white;
        position: relative;
        z-index: 1;
        box-shadow: var(--card-shadow);
    }
    .podium-rank {
        font-size: 2rem;
        font-weight: 800;
        opacity: 0.3;
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
    }
    
    /* Rank 1 */
    .rank-1 .podium-avatar { width: 100px; height: 100px; border-color: var(--warning-color); }
    .rank-1 .podium-base { height: 180px; background: var(--warning-color); width: 140px; }
    .rank-1 { order: 2; }
    
    /* Rank 2 */
    .rank-2 .podium-avatar { border-color: #adb5bd; }
    .rank-2 .podium-base { height: 140px; background: #adb5bd; width: 120px; }
    .rank-2 { order: 1; }
    
    /* Rank 3 */
    .rank-3 .podium-avatar { border-color: #e74a3b; }
    .rank-3 .podium-base { height: 110px; background: #e74a3b; width: 120px; }
    .rank-3 { order: 3; }
    
    /* List Styles */
    .rank-list-item {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.2s;
        border: 1px solid var(--border-color);
    }
    .rank-list-item:hover {
        transform: translateX(5px);
        border-color: var(--primary-color);
    }
    .rank-list-item.my-rank {
        border: 2px solid var(--primary-color);
        background: rgba(0, 102, 204, 0.05);
    }
    .list-rank-num {
        font-size: 1.2rem;
        font-weight: 700;
        color: #a0aec0;
        width: 40px;
        text-align: center;
    }
    .list-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        margin: 0 15px;
        object-fit: cover;
        border: 1px solid var(--border-color);
    }
    .list-info { flex: 1; }
    .list-points {
        font-weight: 700;
        color: var(--primary-color);
        background: rgba(0, 102, 204, 0.1);
        padding: 5px 12px;
        border-radius: 20px;
    }
    
    /* Dark Mode */
    [data-theme="dark"] .rank-list-item.my-rank { background: rgba(0, 102, 204, 0.2); }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .content-wrapper {
            padding: 15px !important;
        }
        .leaderboard-header {
            padding: 20px;
            margin-bottom: 20px;
        }
        .leaderboard-header h2 {
            font-size: 1.5rem;
        }
        
        /* Podium Scale Down */
        .podium-container {
            gap: 8px;
            min-height: auto;
            margin-bottom: 30px;
        }
        
        .podium-avatar {
            width: 50px;
            height: 50px;
            border-width: 3px;
            margin-bottom: -25px;
        }
        
        .podium-base {
            padding: 30px 5px 10px;
            border-radius: 10px 10px 0 0;
        }
        
        .podium-base .text-truncate {
            font-size: 0.8rem;
        }
        
        .podium-base .small {
            font-size: 0.7rem;
        }
        
        .podium-rank {
            font-size: 1.5rem;
            bottom: 5px;
        }
        
        /* Rank 1 Mobile */
        .rank-1 .podium-avatar { width: 70px; height: 70px; }
        .rank-1 .podium-base { height: 150px; width: 100px; }
        
        /* Rank 2 Mobile */
        .rank-2 .podium-avatar { width: 55px; height: 55px; }
        .rank-2 .podium-base { height: 120px; width: 85px; }
        
        /* Rank 3 Mobile */
        .rank-3 .podium-avatar { width: 55px; height: 55px; }
        .rank-3 .podium-base { height: 100px; width: 85px; }
        
        /* List Mobile */
        .rank-list-item {
            padding: 10px 12px;
        }
        .list-rank-num {
            font-size: 1rem;
            width: 25px;
        }
        .list-avatar {
            width: 35px;
            height: 35px;
            margin: 0 10px;
        }
        .list-info h6 {
            font-size: 0.9rem;
        }
        .list-info small {
            font-size: 0.75rem;
        }
        .list-points {
            font-size: 0.75rem;
            padding: 3px 8px;
        }
    }
    
    @media (max-width: 400px) {
        .rank-1 .podium-base { width: 85px; }
        .rank-2 .podium-base { width: 70px; }
        .rank-3 .podium-base { width: 70px; }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                
                <div class="leaderboard-header">
                    <h2 class="fw-bold mb-2"><i class="fas fa-trophy me-2"></i>Papan Peringkat Kelas <?= $mahasiswa['nama_kelas'] ?></h2>
                    <p class="mb-0 opacity-75">Peringkat Anda: <strong>#<?= $my_rank ?></strong> dengan <strong><?= $my_points ?> Poin</strong></p>
                </div>
                
                <!-- Podium Top 3 -->
                <?php if (count($top3) > 0): ?>
                <div class="podium-container">
                    <?php foreach ($top3 as $index => $m): $r = $index + 1; ?>
                    <div class="podium-item rank-<?= $r ?>">
                        <img src="<?= $m['foto'] ?>" alt="<?= $m['nama'] ?>" class="podium-avatar">
                        <div class="podium-base">
                            <div class="text-truncate fw-bold mb-1" style="max-width: 100%;"><?= explode(' ', $m['nama'])[0] ?></div>
                            <div class="small opacity-75"><?= $m['points'] ?> Pts</div>
                            <div class="podium-rank"><?= $r ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- List Sisanya -->
                <div class="rank-list">
                    <?php foreach ($rest as $m): ?>
                    <div class="rank-list-item <?= $m['nim'] == $nim ? 'my-rank' : '' ?>">
                        <div class="list-rank-num"><?= $m['rank'] ?></div>
                        <img src="<?= $m['foto'] ?>" alt="<?= $m['nama'] ?>" class="list-avatar">
                        <div class="list-info">
                            <h6 class="mb-0 fw-bold"><?= $m['nama'] ?></h6>
                            <small class="text-muted"><?= $m['nim'] ?></small>
                        </div>
                        <div class="list-points"><?= $m['points'] ?> Pts</div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>