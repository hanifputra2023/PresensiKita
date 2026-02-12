<?php
$page = 'admin_broadcast';
cek_role(['admin']);

// Ambil daftar kelas untuk filter
$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_type = $_POST['target_type'];
    $pesan = $_POST['pesan'];
    $filter_kelas = isset($_POST['filter_kelas']) ? $_POST['filter_kelas'] : '';
    
    $targets = [];
    $query = "";
    
    // Logika pemilihan target
    if ($target_type == 'semua_mahasiswa') {
        $query = "SELECT no_hp, nama FROM mahasiswa WHERE status = 'aktif' AND no_hp IS NOT NULL AND no_hp != ''";
    } elseif ($target_type == 'kelas_tertentu' && !empty($filter_kelas)) {
        $filter_kelas = escape($filter_kelas);
        $query = "SELECT no_hp, nama FROM mahasiswa WHERE kode_kelas = '$filter_kelas' AND status = 'aktif' AND no_hp IS NOT NULL AND no_hp != ''";
    } elseif ($target_type == 'semua_asisten') {
        $query = "SELECT no_hp, nama FROM asisten WHERE status = 'aktif' AND no_hp IS NOT NULL AND no_hp != ''";
    } elseif ($target_type == 'alpha_hari_ini') {
        // Cari mahasiswa yang alpha pada jadwal hari ini (sudah lewat jam selesai)
        $today = date('Y-m-d');
        $query = "SELECT DISTINCT m.no_hp, m.nama 
                  FROM presensi_mahasiswa pm
                  JOIN mahasiswa m ON pm.nim = m.nim
                  JOIN jadwal j ON pm.jadwal_id = j.id
                  WHERE pm.status = 'alpha' 
                  AND j.tanggal = '$today'
                  AND m.no_hp IS NOT NULL AND m.no_hp != ''";
    }

    if ($query) {
        $result = mysqli_query($conn, $query);
        $success_count = 0;
        $fail_count = 0;

        while ($row = mysqli_fetch_assoc($result)) {
            // Format nomor HP (ganti 08 dengan 628)
            $no_hp = trim($row['no_hp']);
            if (substr($no_hp, 0, 1) == '0') {
                $no_hp = '62' . substr($no_hp, 1);
            }

            // Personalisasi pesan (opsional)
            $pesan_final = str_replace('{nama}', $row['nama'], $pesan);

            // Kirim menggunakan fungsi yang sudah ada di includes/fungsi.php
            $kirim = kirim_notifikasi($no_hp, $pesan_final);
            
            // Cek response dari Fonnte (biasanya JSON)
            $response = json_decode($kirim, true);
            if ($response && isset($response['status']) && $response['status'] == true) {
                $success_count++;
            } else {
                $fail_count++;
            }
            
            // Delay sedikit untuk menghindari rate limit/spam detection
            usleep(500000); // 0.5 detik
        }

        if ($success_count > 0) {
            set_alert('success', "Broadcast berhasil dikirim ke $success_count penerima. Gagal: $fail_count.");
            log_aktivitas($_SESSION['user_id'], 'BROADCAST_WA', 'system', 0, "Mengirim broadcast ke $target_type ($success_count sukses)");
        } else {
            set_alert('warning', "Tidak ada pesan yang terkirim. Pastikan token WA valid dan data nomor HP tersedia.");
        }
    } else {
        set_alert('danger', 'Target tidak valid atau query kosong.');
    }
}
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Welcome Banner Modern */
    .welcome-banner-broadcast {
        background: var(--banner-gradient);
        border-radius: 24px;
        padding: 40px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 102, 204, 0.3);
        animation: fadeInUp 0.5s ease;
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner-broadcast::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse-glow-broadcast 4s ease-in-out infinite;
    }
    
    @keyframes pulse-glow-broadcast {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.05);
            opacity: 0.6;
        }
    }
    
    @keyframes pulse-badge-broadcast {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 8px rgba(255, 255, 255, 0);
        }
    }
    
    .welcome-banner-broadcast h1 {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-broadcast .banner-subtitle {
        font-size: 16px;
        opacity: 0.95;
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-broadcast .banner-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
        position: relative;
        z-index: 1;
    }
    
    .welcome-banner-broadcast .banner-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        z-index: 1;
        animation: pulse-badge-broadcast 2s ease-in-out infinite;
    }
    
    .welcome-banner-broadcast .banner-badge i {
        font-size: 8px;
        margin-right: 6px;
        animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Dark Mode Support */
    [data-theme="dark"] .welcome-banner-broadcast {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .welcome-banner-broadcast {
            padding: 24px;
            border-radius: 16px;
        }
        
        .welcome-banner-broadcast h1 {
            font-size: 24px;
        }
        
        .welcome-banner-broadcast .banner-icon {
            width: 50px;
            height: 50px;
            font-size: 22px;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <!-- Welcome Banner -->
                <div class="welcome-banner-broadcast mb-4">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="banner-icon">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <div>
                            <h1 class="mb-1">Broadcast WhatsApp</h1>
                            <p class="banner-subtitle mb-0">Kirim pesan massal ke mahasiswa dan asisten melalui WhatsApp Gateway</p>
                        </div>
                    </div>
                    <span class="banner-badge">
                        <i class="fas fa-circle"></i>WHATSAPP GATEWAY
                    </span>
                </div>
                
                <?= show_alert() ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-paper-plane me-2"></i>Kirim Pesan Massal
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Target Penerima</label>
                                <select name="target_type" id="targetType" class="form-select" required onchange="toggleKelas()">
                                    <option value="">-- Pilih Target --</option>
                                    <option value="semua_mahasiswa">Semua Mahasiswa Aktif</option>
                                    <option value="kelas_tertentu">Kelas Tertentu</option>
                                    <option value="semua_asisten">Semua Asisten</option>
                                    <option value="alpha_hari_ini">Mahasiswa Alpha Hari Ini (Peringatan)</option>
                                </select>
                            </div>

                            <div class="mb-3" id="divKelas" style="display: none;">
                                <label class="form-label fw-bold">Pilih Kelas</label>
                                <select name="filter_kelas" class="form-select">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php while ($k = mysqli_fetch_assoc($kelas_list)): ?>
                                        <option value="<?= $k['kode_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Isi Pesan</label>
                                <textarea name="pesan" class="form-control" rows="6" required placeholder="Tulis pesan Anda di sini... Gunakan {nama} untuk menyebut nama penerima secara otomatis."></textarea>
                                <div class="form-text">
                                    Tips: Gunakan *teks* untuk tebal, _teks_ untuk miring. <br>
                                    Contoh: "Halo {nama}, jangan lupa besok ada praktikum!"
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Pastikan Token WhatsApp di <code>includes/fungsi.php</code> sudah dikonfigurasi dan perangkat terhubung.
                            </div>

                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Yakin ingin mengirim pesan ini ke banyak penerima?')">
                                <i class="fab fa-whatsapp me-2"></i>Kirim Broadcast
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleKelas() {
    var type = document.getElementById('targetType').value;
    var div = document.getElementById('divKelas');
    div.style.display = (type == 'kelas_tertentu') ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>