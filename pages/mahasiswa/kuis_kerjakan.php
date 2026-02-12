<?php
$page = 'mahasiswa_kuis';
$mahasiswa = get_mahasiswa_login();
$nim = $mahasiswa['nim'];
$kuis_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi Kuis
$stmt = mysqli_prepare($conn, "SELECT k.*, j.sesi as jadwal_sesi FROM kuis k JOIN jadwal j ON k.jadwal_id = j.id WHERE k.id = ? AND k.status = 'aktif'");
mysqli_stmt_bind_param($stmt, "i", $kuis_id);
mysqli_stmt_execute($stmt);
$kuis = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$kuis) {
    set_alert('danger', 'Kuis tidak ditemukan atau sudah ditutup.');
    header("Location: index.php?page=mahasiswa_kuis");
    exit;
}

// Validasi Sesi
$sesi_mhs = $mahasiswa['sesi'] ?? 1;
if ($kuis['jadwal_sesi'] != 0 && $kuis['jadwal_sesi'] != $sesi_mhs) {
    set_alert('danger', 'Kuis ini tidak tersedia untuk sesi Anda.');
    header("Location: index.php?page=mahasiswa_kuis");
    exit;
}

// Cek apakah sudah mengerjakan
$cek_hasil = mysqli_query($conn, "SELECT id FROM hasil_kuis WHERE kuis_id = $kuis_id AND nim = '$nim'");
if (mysqli_num_rows($cek_hasil) > 0) {
    set_alert('warning', 'Anda sudah mengerjakan kuis ini.');
    header("Location: index.php?page=mahasiswa_kuis");
    exit;
}

// [UPDATE] Logika Session untuk Soal & Timer (Anti-Refresh)
if (!isset($_SESSION['kuis_' . $kuis_id . '_soal'])) {
    // Jika belum ada di sesi, ambil dari DB dan acak
    $soal_query = mysqli_query($conn, "SELECT id, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, gambar FROM soal_kuis WHERE kuis_id = $kuis_id ORDER BY RAND()");
    $soal_list = [];
    while ($row = mysqli_fetch_assoc($soal_query)) {
        $soal_list[] = $row;
    }
    $_SESSION['kuis_' . $kuis_id . '_soal'] = $soal_list;
} else {
    // Jika sudah ada, pakai urutan yang tersimpan (biar ga berubah pas refresh)
    $soal_list = $_SESSION['kuis_' . $kuis_id . '_soal'];
}

if (empty($soal_list)) {
    set_alert('warning', 'Soal kuis belum tersedia.');
    header("Location: index.php?page=mahasiswa_kuis");
    exit;
}

// [UPDATE] Logika Timer Server-side
if (!isset($_SESSION['kuis_' . $kuis_id . '_start'])) {
    $_SESSION['kuis_' . $kuis_id . '_start'] = time();
}

$start_time = $_SESSION['kuis_' . $kuis_id . '_start'];
$durasi_detik = $kuis['durasi_menit'] * 60;
$end_time = $start_time + $durasi_detik;
$sisa_waktu = $end_time - time(); // Sisa waktu dalam detik

// Proses Submit Jawaban
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_kuis'])) {
    $benar = 0;
    $salah = 0;
    $total_soal = count($soal_list);
    
    // Ambil kunci jawaban
    $kunci_query = mysqli_query($conn, "SELECT id, kunci_jawaban FROM soal_kuis WHERE kuis_id = $kuis_id");
    $kunci_jawaban = [];
    while ($k = mysqli_fetch_assoc($kunci_query)) {
        $kunci_jawaban[$k['id']] = $k['kunci_jawaban'];
    }
    
    $detail_jawaban = []; // Array untuk menampung detail jawaban
    foreach ($kunci_jawaban as $id_soal => $kunci) {
        $jawaban_mhs = isset($_POST['jawaban'][$id_soal]) ? $_POST['jawaban'][$id_soal] : '';
        $is_benar = ($jawaban_mhs == $kunci) ? 1 : 0;
        
        if ($is_benar) {
            $benar++;
        } else {
            $salah++;
        }
        
        // Simpan ke array dulu
        $detail_jawaban[] = [
            'soal_id' => $id_soal,
            'jawaban' => $jawaban_mhs,
            'is_benar' => $is_benar
        ];
    }
    
    // Hitung nilai berdasarkan metode yang dipilih asisten
    if ($kuis['metode_penilaian'] == 'poin_murni') {
        $nilai = $benar;
    } elseif ($kuis['metode_penilaian'] == 'bobot_kustom') {
        $nilai = $benar * $kuis['bobot_per_soal'];
    } else {
        $nilai = ($total_soal > 0) ? ($benar / $total_soal) * 100 : 0;
    }

    $waktu_mulai = $_POST['waktu_mulai']; // Dikirim dari hidden input
    $waktu_selesai = date('Y-m-d H:i:s');
    
    $stmt_insert = mysqli_prepare($conn, "INSERT INTO hasil_kuis (kuis_id, nim, nilai, benar, salah, waktu_mulai, waktu_selesai) VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_insert, "isdiiss", $kuis_id, $nim, $nilai, $benar, $salah, $waktu_mulai, $waktu_selesai);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        $hasil_kuis_id = mysqli_insert_id($conn);
        
        // Simpan detail jawaban ke tabel detail_jawaban_kuis
        $stmt_detail = mysqli_prepare($conn, "INSERT INTO detail_jawaban_kuis (hasil_kuis_id, soal_id, jawaban_mahasiswa, is_benar) VALUES (?, ?, ?, ?)");
        foreach ($detail_jawaban as $dj) {
            mysqli_stmt_bind_param($stmt_detail, "iisi", $hasil_kuis_id, $dj['soal_id'], $dj['jawaban'], $dj['is_benar']);
            mysqli_stmt_execute($stmt_detail);
        }

        // Hapus sesi kuis setelah selesai
        unset($_SESSION['kuis_' . $kuis_id . '_start']);
        unset($_SESSION['kuis_' . $kuis_id . '_soal']);
        set_alert('success', "Kuis selesai! Nilai Anda: " . number_format($nilai, 1));
        // Log aktivitas
        log_aktivitas($_SESSION['user_id'], 'KERJAKAN_KUIS', 'hasil_kuis', mysqli_insert_id($conn), "Nilai: $nilai");
    } else {
        set_alert('danger', 'Gagal menyimpan hasil kuis.');
    }
    
    header("Location: index.php?page=mahasiswa_kuis");
    exit;
}

// Jika waktu habis di server side, paksa submit (opsional, atau biarkan JS handle)
if ($sisa_waktu <= 0 && $_SERVER['REQUEST_METHOD'] != 'POST') {
    // Bisa redirect atau set flag expired
    $sisa_waktu = 0;
}

$waktu_mulai_formatted = date('Y-m-d H:i:s', $start_time);
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Quiz Specific Styles using Theme Variables */
    .quiz-container {
        max-width: 1100px;
        margin: 0 auto;
        padding-bottom: 60px;
    }
    
    .quiz-header-card {
        border-top: 5px solid var(--primary-color);
        border-radius: 12px;
        
    }
    
    .quiz-question-card {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .quiz-question-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--card-shadow);
    }
    
    .question-text {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1.2rem;
        color: var(--text-main);
        line-height: 1.5;
    }
    
    /* Custom Radio Button Styling */
    .option-label {
        cursor: pointer;
        width: 100%;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        transition: all 0.2s;
        display: flex;
        align-items: center;
        background-color: var(--bg-input);
        color: var(--text-main);
    }
    
    .option-label:hover {
        background-color: rgba(0, 102, 204, 0.05); /* Light primary tint */
        border-color: var(--primary-color);
    }
    
    .form-check-input:checked + .option-label {
        background-color: rgba(0, 102, 204, 0.1);
        border-color: var(--primary-color);
        font-weight: 600;
        color: var(--primary-color);
    }
    
    /* Hide default radio but keep it accessible */
    .form-check-input {
        position: absolute;
        opacity: 0;
    }
    
    /* Timer Floating */
    .timer-float {
        position: fixed;
        top: 80px; 
        right: 20px;
        background: var(--bg-card);
        color: var(--text-main);
        padding: 12px 20px;
        border-radius: 50px;
        box-shadow: var(--card-shadow);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid var(--border-color);
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .timer-float i {
        color: var(--danger-color);
    }
    
    /* Dark mode specific adjustments */
    [data-theme="dark"] .option-label:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    
    [data-theme="dark"] .form-check-input:checked + .option-label {
        background-color: rgba(58, 143, 217, 0.2);
        color: #66b0ff;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 px-0">
            <?php include 'includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 col-lg-10">
            <div class="content-wrapper p-4">
                <!-- Timer Floating -->
                <div class="timer-float">
                    <i class="fas fa-clock text-danger"></i>
                    <span id="timer" class="timer-text">00:00</span>
                </div>

                <div class="quiz-container">
                    <form method="POST" id="formKuis">
                        <input type="hidden" name="submit_kuis" value="1">
                        <input type="hidden" name="waktu_mulai" value="<?= $waktu_mulai_formatted ?>">
                        
                        <!-- Header Card -->
                        <div class="card quiz-header-card mb-4 shadow-sm">
                            <div class="card-body p-4">
                                <h2 class="card-title fw-bold mb-3 text-primary"><?= htmlspecialchars($kuis['judul']) ?></h2>
                                <?php if ($kuis['deskripsi']): ?>
                                    <p class="card-text text-muted"><?= nl2br(htmlspecialchars($kuis['deskripsi'])) ?></p>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span><i class="fas fa-list-ol me-1"></i><?= count($soal_list) ?> Soal</span>
                                    <span><i class="fas fa-clock me-1"></i><?= $kuis['durasi_menit'] ?> Menit</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Questions -->
                        <div class="row g-4">
                            <?php foreach ($soal_list as $index => $s): ?>
                                <div class="col-lg-6">
                                    <div class="card quiz-question-card shadow-sm h-100">
                                        <div class="card-body p-4">
                                            <div class="question-text">
                                                <span class="badge bg-primary me-2"><?= ($index + 1) ?></span>
                                                <?= nl2br(htmlspecialchars($s['pertanyaan'])) ?>
                                            </div>
                                            
                                            <?php if (!empty($s['gambar'])): ?>
                                                <div class="mb-3 text-center">
                                                    <img src="<?= $s['gambar'] ?>" alt="Gambar Soal" class="img-fluid rounded" style="max-height: 250px; cursor: pointer;" onclick="window.open('<?= $s['gambar'] ?>', '_blank')">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex flex-column gap-2">
                                                <?php 
                                                $opsi = ['A', 'B', 'C', 'D'];
                                                foreach ($opsi as $opt): 
                                                    $val = 'opsi_' . strtolower($opt);
                                                ?>
                                                    <div class="position-relative">
                                                        <input class="form-check-input" type="radio" name="jawaban[<?= $s['id'] ?>]" id="opt_<?= $s['id'] ?>_<?= $opt ?>" value="<?= $opt ?>" required>
                                                        <label class="option-label" for="opt_<?= $s['id'] ?>_<?= $opt ?>">
                                                            <span class="fw-bold me-3 text-primary"><?= $opt ?>.</span>
                                                            <?= htmlspecialchars($s[$val]) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4 mb-5">
                            <button type="submit" class="btn btn-primary btn-lg shadow" onclick="return confirm('Yakin ingin mengumpulkan jawaban?')">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Jawaban
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Timer Logic
// Menggunakan sisa waktu dari server agar sinkron meski di-refresh
var sisaDetik = <?= max(0, $sisa_waktu) ?>;
var totalDurasi = <?= $durasi_detik ?>;

// Update tampilan awal
updateTimer();

var x = setInterval(function() {
    sisaDetik--;
    
    if (sisaDetik <= 0) {
        clearInterval(x);
        sisaDetik = 0;
        updateTimer();
        alert("Waktu habis! Jawaban Anda akan dikirim otomatis.");
        document.getElementById("formKuis").submit();
        return;
    }
    
    updateTimer();
}, 1000);

function updateTimer() {
    var minutes = Math.floor(sisaDetik / 60);
    var seconds = Math.floor(sisaDetik % 60);
    
    document.getElementById("timer").innerHTML = 
        (minutes < 10 ? "0" + minutes : minutes) + ":" + 
        (seconds < 10 ? "0" + seconds : seconds);
        
    // Warning warna merah jika < 1 menit
    if (sisaDetik < 60) {
        document.getElementById("timer").classList.add("text-danger");
        document.getElementById("timer").classList.add("blink");
    }
}

// Prevent accidental leave
window.onbeforeunload = function() {
    return "Yakin ingin meninggalkan halaman? Jawaban Anda mungkin tidak tersimpan.";
};

// Remove warning on submit
document.getElementById("formKuis").onsubmit = function() {
    window.onbeforeunload = null;
};
</script>

<?php include 'includes/footer.php'; ?>