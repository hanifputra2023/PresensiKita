<?php
$page = 'asisten_bantuan';
$asisten = get_asisten_login();
$kode_asisten = $asisten['kode_asisten'];

// Proses kirim tiket
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kategori = escape($_POST['kategori']);
    $subjek = escape($_POST['subjek']);
    $pesan = escape($_POST['pesan']);
    
    if (empty($kategori) || empty($subjek) || empty($pesan)) {
        set_alert('danger', 'Mohon lengkapi semua kolom.');
    } else {
        // Handle File Upload
        $lampiran = null;
        $upload_ok = true;
        
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size = 500 * 1024; // 500KB
            
            $file_tmp = $_FILES['lampiran']['tmp_name'];
            $file_size = $_FILES['lampiran']['size'];
            
            // Validasi MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);
            
            if (!in_array($mime, $allowed_types)) {
                set_alert('danger', 'Format file tidak didukung! Gunakan JPG, PNG, atau WebP.');
                $upload_ok = false;
            } elseif ($file_size > $max_size) {
                set_alert('danger', 'Ukuran file terlalu besar! Maksimal 500KB.');
                $upload_ok = false;
            } else {
                $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
                $new_name = 'ticket_ast_' . time() . '_' . uniqid() . '.' . $ext;
                $dest = 'uploads/bantuan/' . $new_name;
                if (!is_dir('uploads/bantuan')) mkdir('uploads/bantuan', 0777, true);
                
                if (optimize_and_save_image($file_tmp, $dest, 1280, 1280, 80)) {
                    $lampiran = $dest;
                } else {
                    set_alert('danger', 'Gagal mengupload lampiran.');
                    $upload_ok = false;
                }
            }
        }

        // Simpan ke database (menggunakan kode_asisten di kolom nim)
        if ($upload_ok) {
            $stmt = mysqli_prepare($conn, "INSERT INTO tiket_bantuan (nim, kategori, subjek, pesan, status, lampiran) VALUES (?, ?, ?, ?, 'pending', ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $kode_asisten, $kategori, $subjek, $pesan, $lampiran);
            
            if (mysqli_stmt_execute($stmt)) {
                $id = mysqli_insert_id($conn);
                log_aktivitas($_SESSION['user_id'], 'KIRIM_BANTUAN', 'tiket_bantuan', $id, "Asisten mengirim tiket: $subjek");
                set_alert('success', 'Laporan/Saran berhasil dikirim! Menunggu tanggapan admin.');
            } else {
                set_alert('danger', 'Gagal mengirim: ' . mysqli_error($conn));
            }
        }
    }
    header("Location: index.php?page=asisten_bantuan");
    exit;
}

// Ambil riwayat
$riwayat = [];
$stmt = mysqli_prepare($conn, "SELECT * FROM tiket_bantuan WHERE nim = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, "s", $kode_asisten);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $riwayat[] = $row;
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
                <h4 class="mb-4 pt-2"><i class="fas fa-headset me-2"></i>Pusat Bantuan & Saran</h4>
                
                <?= show_alert() ?>
                
                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-pen me-2"></i>Buat Pesan Baru
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Kategori</label>
                                        <select name="kategori" class="form-select" required>
                                            <option value="">-- Pilih Kategori --</option>
                                            <option value="Masalah Sistem">Masalah Sistem (Bug/Error)</option>
                                            <option value="Saran">Saran / Masukan</option>
                                            <option value="Pertanyaan">Pertanyaan Umum</option>
                                            <option value="Lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Subjek</label>
                                        <input type="text" name="subjek" class="form-control" placeholder="Contoh: Error saat input nilai" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Pesan Detail</label>
                                        <textarea name="pesan" class="form-control" rows="5" placeholder="Jelaskan detail masalah atau saran Anda..." required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Lampiran (Opsional)</label>
                                        <input type="file" name="lampiran" id="inputLampiran" class="form-control" accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text small">
                                            Maks. 500KB (JPG, PNG, WebP).
                                            <span id="compressStatus" class="fw-bold text-primary"></span>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-paper-plane me-2"></i>Kirim
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <i class="fas fa-history me-2"></i>Riwayat Pesan Anda
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($riwayat)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                                        <p>Belum ada riwayat tiket.</p>
                                    </div>
                                <?php else: ?>
                                    <!-- Desktop View -->
                                    <div class="table-responsive d-none d-md-block">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Kategori</th>
                                                    <th>Subjek</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($riwayat as $r): ?>
                                                    <tr>
                                                        <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                                        <td>
                                                            <?php
                                                            $badge_color = 'secondary';
                                                            if ($r['kategori'] == 'Masalah Sistem') $badge_color = 'danger';
                                                            elseif ($r['kategori'] == 'Saran') $badge_color = 'success';
                                                            elseif ($r['kategori'] == 'Pertanyaan') $badge_color = 'info';
                                                            ?>
                                                            <span class="badge bg-<?= $badge_color ?>"><?= htmlspecialchars($r['kategori']) ?></span>
                                                        </td>
                                                        <td><?= htmlspecialchars($r['subjek']) ?></td>
                                                        <td>
                                                            <?php
                                                            $status_badge = 'secondary';
                                                            if ($r['status'] == 'proses') $status_badge = 'warning';
                                                            elseif ($r['status'] == 'selesai') $status_badge = 'success';
                                                            elseif ($r['status'] == 'ditolak') $status_badge = 'danger';
                                                            ?>
                                                            <span class="badge bg-<?= $status_badge ?> rounded-pill"><?= ucfirst($r['status']) ?></span>
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button" class="btn btn-sm btn-info text-white btn-detail" 
                                                                    data-bs-toggle="modal" data-bs-target="#modalDetail"
                                                                    data-subjek="<?= htmlspecialchars($r['subjek']) ?>"
                                                                    data-kategori="<?= htmlspecialchars($r['kategori']) ?>"
                                                                    data-tanggal="<?= date('d M Y H:i', strtotime($r['created_at'])) ?>"
                                                                    data-pesan="<?= htmlspecialchars($r['pesan']) ?>"
                                                                    data-lampiran="<?= htmlspecialchars($r['lampiran'] ?? '') ?>"
                                                                    data-tanggapan="<?= htmlspecialchars($r['tanggapan'] ?? '') ?>">
                                                                <i class="fas fa-eye me-1"></i>Detail
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Mobile View -->
                                    <div class="d-md-none p-3">
                                        <?php foreach ($riwayat as $r): ?>
                                            <div class="card mb-3 border shadow-sm">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <?php
                                                            $badge_color = 'secondary';
                                                            if ($r['kategori'] == 'Masalah Sistem') $badge_color = 'danger';
                                                            elseif ($r['kategori'] == 'Saran') $badge_color = 'success';
                                                            elseif ($r['kategori'] == 'Pertanyaan') $badge_color = 'info';
                                                            ?>
                                                            <span class="badge bg-<?= $badge_color ?> mb-1"><?= htmlspecialchars($r['kategori']) ?></span>
                                                            <div class="small text-muted"><?= date('d/m/y H:i', strtotime($r['created_at'])) ?></div>
                                                        </div>
                                                        <?php
                                                        $status_badge = 'secondary';
                                                        if ($r['status'] == 'proses') $status_badge = 'warning';
                                                        elseif ($r['status'] == 'selesai') $status_badge = 'success';
                                                        elseif ($r['status'] == 'ditolak') $status_badge = 'danger';
                                                        ?>
                                                        <span class="badge bg-<?= $status_badge ?> rounded-pill"><?= ucfirst($r['status']) ?></span>
                                                    </div>
                                                    
                                                    <h6 class="card-title fw-bold mb-2"><?= htmlspecialchars($r['subjek']) ?></h6>
                                                    <p class="text-muted small mb-3 text-truncate"><?= htmlspecialchars(substr($r['pesan'], 0, 80)) ?>...</p>
                                                    
                                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 btn-detail" 
                                                            data-bs-toggle="modal" data-bs-target="#modalDetail"
                                                            data-subjek="<?= htmlspecialchars($r['subjek']) ?>"
                                                            data-kategori="<?= htmlspecialchars($r['kategori']) ?>"
                                                            data-tanggal="<?= date('d M Y H:i', strtotime($r['created_at'])) ?>"
                                                            data-pesan="<?= htmlspecialchars($r['pesan']) ?>"
                                                            data-lampiran="<?= htmlspecialchars($r['lampiran'] ?? '') ?>"
                                                            data-tanggapan="<?= htmlspecialchars($r['tanggapan'] ?? '') ?>">
                                                        <i class="fas fa-eye me-1"></i>Lihat Detail
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Tiket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold" id="detail_subjek"></h6>
                <p class="text-muted small mb-3">
                    <span id="detail_kategori"></span> &bull; <span id="detail_tanggal"></span>
                </p>
                
                <div class="p-3 bg-light border rounded mb-3" id="detail_pesan" style="white-space: pre-wrap;"></div>
                
                <div class="mb-3" id="area_lampiran" style="display: none;">
                    <label class="form-label fw-bold small text-muted">Lampiran</label>
                    <div>
                        <button type="button" id="btn_view_lampiran" class="btn btn-sm btn-outline-primary" onclick="viewLampiran()"><i class="fas fa-eye me-1"></i>Lihat Lampiran</button>
                    </div>
                    <div id="preview_lampiran" class="mt-3 bg-light p-2 rounded border text-center" style="display: none;"></div>
                </div>
                
                <div id="area_tanggapan" style="display: none;">
                    <div class="alert alert-info border-0 mb-0">
                        <strong><i class="fas fa-reply me-1"></i>Tanggapan Admin:</strong><br>
                        <span id="detail_tanggapan" style="white-space: pre-wrap;"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalDetail = document.getElementById('modalDetail');
    modalDetail.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        
        document.getElementById('detail_subjek').textContent = button.getAttribute('data-subjek');
        document.getElementById('detail_kategori').textContent = button.getAttribute('data-kategori');
        document.getElementById('detail_tanggal').textContent = button.getAttribute('data-tanggal');
        document.getElementById('detail_pesan').textContent = button.getAttribute('data-pesan');
        
        const lampiran = button.getAttribute('data-lampiran');
        const btnLampiran = document.getElementById('btn_view_lampiran');
        const areaLampiran = document.getElementById('area_lampiran');
        
        document.getElementById('preview_lampiran').style.display = 'none';
        document.getElementById('preview_lampiran').innerHTML = '';
        btnLampiran.innerHTML = '<i class="fas fa-eye me-1"></i>Lihat Lampiran';
        
        if (lampiran) {
            areaLampiran.style.display = 'block';
            btnLampiran.setAttribute('data-src', lampiran);
        } else {
            areaLampiran.style.display = 'none';
        }
        
        const tanggapan = button.getAttribute('data-tanggapan');
        const areaTanggapan = document.getElementById('area_tanggapan');
        if (tanggapan) {
            areaTanggapan.style.display = 'block';
            document.getElementById('detail_tanggapan').textContent = tanggapan;
        } else {
            areaTanggapan.style.display = 'none';
        }
    });
});

function viewLampiran() {
    const btn = document.getElementById('btn_view_lampiran');
    const src = btn.getAttribute('data-src');
    const container = document.getElementById('preview_lampiran');
    
    if (container.style.display === 'block') {
        container.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-eye me-1"></i>Lihat Lampiran';
        return;
    }
    if (!src) return;
    
    const ext = src.split('.').pop().toLowerCase();
    if (['jpg', 'jpeg', 'png'].includes(ext)) {
        container.innerHTML = `<img src="${src}" style="max-width: 100%; height: auto;" alt="Lampiran">`;
    } else if (ext === 'pdf') {
        container.innerHTML = `<iframe src="${src}" style="width:100%; height:400px;" frameborder="0"></iframe>`;
    } else {
        container.innerHTML = `<a href="${src}" target="_blank" class="btn btn-primary"><i class="fas fa-download me-1"></i>Download File</a>`;
    }
    container.style.display = 'block';
    btn.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Tutup Lampiran';
}
</script>

<script>
// Client-side Image Compression
document.getElementById('inputLampiran')?.addEventListener('change', async function(e) {
    const input = e.target;
    const file = input.files[0];
    const status = document.getElementById('compressStatus');
    
    if (!file) return;

    if (!file.type.match(/image.*/)) {
        alert('File harus berupa gambar (JPG, PNG, WebP)!');
        input.value = '';
        return;
    }

    status.innerText = 'Mengompres...';
    input.disabled = true;

    try {
        const compressedFile = await compressImage(file, 1280, 0.8, 500 * 1024);
        
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(compressedFile);
        input.files = dataTransfer.files;
        
        status.innerHTML = '<i class="fas fa-check"></i> Siap (' + (compressedFile.size/1024).toFixed(0) + 'KB)';
    } catch (error) {
        console.error(error);
        alert("Gagal memproses gambar.");
        input.value = '';
        status.innerText = '';
    } finally {
        input.disabled = false;
    }
});

function compressImage(file, maxWidth, quality, maxBytes) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = event => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;
                if (width > maxWidth) {
                    height = Math.round(height * (maxWidth / width));
                    width = maxWidth;
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                let currentQuality = quality;
                const tryCompress = (q) => {
                    canvas.toBlob(blob => {
                        if (!blob) return reject(new Error('Canvas error'));
                        if (blob.size > maxBytes && q > 0.1) {
                            tryCompress(q - 0.1);
                        } else {
                            resolve(new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            }));
                        }
                    }, 'image/jpeg', q);
                };
                tryCompress(currentQuality);
            };
            img.onerror = error => reject(error);
        };
        reader.onerror = error => reject(error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
