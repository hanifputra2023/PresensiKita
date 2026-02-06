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

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <h4 class="mb-4"><i class="fab fa-whatsapp me-2 text-success"></i>Broadcast WhatsApp Gateway</h4>
                
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