<?php
/**
 * File: indddex.php
 * Aplikasi To-Do List Sedderhana:
 * - Penyimpanan JSON
 * - Bootstrap 5 UI sederhana & modern
 * - Checkbox status, hapus, edit
 * - Progress bar
 * - Scroll otomatis ke bawah
 */

class TaskManager {
    private $daftarTugas = [];
    private $namaFilePenyimpanan = 'data_tugas.json';

    public function __construct() {
        $this->muatDaftarTugas();
    }

    private function muatDaftarTugas() {
        if (file_exists($this->namaFilePenyimpanan)) {
            $kontenData = file_get_contents($this->namaFilePenyimpanan);
            $this->daftarTugas = json_decode($kontenData, true) ?: [];
            usort($this->daftarTugas, function($a, $b) {
                if ($a['status_selesai'] != $b['status_selesai']) {
                    return $a['status_selesai'] - $b['status_selesai'];
                }
                return strtotime($b['waktu_buat']) - strtotime($a['waktu_buat']);
            });
        }
    }

    private function simpanDaftarTugas() {
        file_put_contents($this->namaFilePenyimpanan, json_encode($this->daftarTugas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function tambahItem($judulEntri, $uraianEntri = '') {
        if (empty(trim($judulEntri))) return false;
        $itemBaru = [
            'identifier' => uniqid('task_'),
            'judul' => htmlspecialchars(trim($judulEntri)),
            'uraian' => htmlspecialchars(trim($uraianEntri)),
            'waktu_buat' => date('Y-m-d H:i:s'),
            'status_selesai' => false
        ];
        $this->daftarTugas[] = $itemBaru;
        $this->simpanDaftarTugas();
        return true;
    }

    public function alihStatusItem($id) {
        foreach ($this->daftarTugas as &$item) {
            if ($item['identifier'] === $id) {
                $item['status_selesai'] = !$item['status_selesai'];
                $this->simpanDaftarTugas();
                return true;
            }
        }
        return false;
    }

    public function hapusItem($id) {
        $awal = count($this->daftarTugas);
        $this->daftarTugas = array_filter($this->daftarTugas, fn($i) => $i['identifier'] !== $id);
        if (count($this->daftarTugas) < $awal) {
            $this->simpanDaftarTugas();
            return true;
        }
        return false;
    }

    public function perbaruiItem($id, $judulBaru, $uraianBaru) {
        if (empty(trim($judulBaru))) return false;
        foreach ($this->daftarTugas as &$item) {
            if ($item['identifier'] === $id) {
                $item['judul'] = htmlspecialchars(trim($judulBaru));
                $item['uraian'] = htmlspecialchars(trim($uraianBaru));
                $this->simpanDaftarTugas();
                return true;
            }
        }
        return false;
    }

    public function dapatkanSemuaItem() {
        return $this->daftarTugas;
    }

    public function dapatkanItemById($id) {
        foreach ($this->daftarTugas as $item) {
            if ($item['identifier'] === $id) return $item;
        }
        return null;
    }
}

$pengelolaTugas = new TaskManager();
$notifikasi = null;

$idTugasUntukEdit = null;
$dataTugasUntukEdit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $idItem = $_POST['id_item'] ?? '';
    switch ($aksi) {
        case 'tambah':
            if ($pengelolaTugas->tambahItem($_POST['judul_entri'] ?? '', $_POST['deskripsi_entri'] ?? '')) {
                $notifikasi = ['jenis' => 'success', 'pesan' => 'Tugas berhasil ditambahkan!'];
            } else {
                $notifikasi = ['jenis' => 'danger', 'pesan' => 'Judul tugas tidak boleh kosong!'];
            }
            break;
        case 'alih_status':
            $pengelolaTugas->alihStatusItem($idItem);
            break;
        case 'hapus':
            if ($pengelolaTugas->hapusItem($idItem)) {
                $notifikasi = ['jenis' => 'success', 'pesan' => 'Tugas berhasil dihapus!'];
            } else {
                $notifikasi = ['jenis' => 'danger', 'pesan' => 'Gagal menghapus tugas.'];
            }
            break;
        case 'edit_item_proses':
            if ($pengelolaTugas->perbaruiItem($idItem, $_POST['judul_entri_edit'] ?? '', $_POST['deskripsi_entri_edit'] ?? '')) {
                $notifikasi = ['jenis' => 'success', 'pesan' => 'Tugas berhasil diperbarui!'];
            } else {
                $notifikasi = ['jenis' => 'danger', 'pesan' => 'Gagal memperbarui tugas. Judul tidak boleh kosong atau tugas tidak ditemukan!'];
            }
            break;
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . ($notifikasi ? '?notif_type=' . $notifikasi['jenis'] . '&notif_msg=' . urlencode($notifikasi['pesan']) : ''));
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_id'])) {
    $idTugasUntukEdit = $_GET['edit_id'];
    $dataTugasUntukEdit = $pengelolaTugas->dapatkanItemById($idTugasUntukEdit);
    if (!$dataTugasUntukEdit) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?notif_type=danger&notif_msg=' . urlencode('Tugas yang akan diedit tidak ditemukan.'));
        exit();
    }
}

$semuaTugas = $pengelolaTugas->dapatkanSemuaItem();
$total = count($semuaTugas);
$selesai = count(array_filter($semuaTugas, fn($item) => $item['status_selesai']));
$persen = $total > 0 ? round(($selesai / $total) * 100) : 0;

?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Tugas Harian</title>
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/1035/1035688.png" type="image/png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f0f2f5; /* Abu-abu terang yang lembut */
        color: #333;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
    }
    
    .container {
        max-width: 768px;
    }

    .card {
        border: none;
        border-radius: 0.75rem; /* Sedikit lebih kecil dari 1rem */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); /* Bayangan lebih ringan */
        overflow: hidden;
    }

    .card-header {
        background-color: #4a69bd; /* Biru yang kuat tapi tidak terlalu cerah */
        color: white;
        padding: 1.5rem; /* Padding lebih sederhana */
        font-weight: 600;
        font-size: 1.4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: none;
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
    }
    .card-header .bi {
        font-size: 1.6rem;
        margin-right: 0.6rem;
    }

    .card-body {
        padding: 1.5rem; /* Padding sederhana */
    }

    /* Styling untuk semua form */
    form.mb-4, .form-edit-container {
        background-color: #ffffff; /* Latar belakang form putih */
        border: 1px solid #e0e0e0; /* Border abu-abu tipis */
        padding: 1.25rem; /* Padding form sederhana */
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Bayangan sangat ringan */
        margin-bottom: 2rem;
    }

    /* Styling khusus untuk form edit */
    .form-edit-container {
        border-left: 5px solid #4a69bd; /* Aksen biru di kiri */
        background-color: #eef2f9; /* Biru sangat muda */
    }
    .form-edit-container .form-label {
        color: #4a69bd; /* Warna label edit */
    }
    .form-edit-container .h5 {
        color: #4a69bd; /* Warna judul form edit */
    }


    .form-label {
        font-weight: 500;
        color: #555;
    }
    .form-control {
        border-radius: 0.4rem;
        border-color: #d0d0d0;
        padding: 0.6rem 0.9rem;
        font-size: 0.9rem;
    }
    .form-control:focus {
        border-color: #4a69bd;
        box-shadow: 0 0 0 0.2rem rgba(74, 105, 189, 0.25);
    }

    .btn {
        border-radius: 0.4rem;
        font-weight: 500; /* Sedikit lebih tipis */
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
    }
    .btn .bi {
        margin-right: 0.4rem; /* Margin ikon lebih kecil */
    }
    .btn-primary {
        background-color: #4a69bd;
        border-color: #4a69bd;
    }
    .btn-primary:hover {
        background-color: #3b5093;
        border-color: #3b5093;
    }
    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #333;
    }
    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #e0a800;
    }
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    .btn-secondary:hover {
        background-color: #5a6268;
        border-color: #5a6268;
    }

    .progress {
        height: 22px; /* Disesuaikan */
        border-radius: 0.4rem;
        background-color: #e9ecef;
        margin-top: 1.5rem;
    }
    .progress-bar {
        background-color: #28a745 !important; /* Warna hijau Bootstrap success */
        font-weight: 600;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
    }

    .list-group {
        margin-top: 1.5rem; /* Jarak dari progress bar */
    }

    .list-group-item {
        border: 1px solid #e0e0e0;
        margin-bottom: 0.75rem;
        border-radius: 0.5rem; /* Lebih membulat */
        background-color: #ffffff;
        transition: transform 0.15s ease, box-shadow 0.15s ease; /* Transisi lebih cepat */
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.03); /* Bayangan sangat tipis */
        padding: 0.8rem 1.2rem; /* Padding lebih ringkas */
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }
    .list-group-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); /* Efek hover sederhana */
    }

    .form-check-input {
        width: 1.1em; /* Lebih kecil */
        height: 1.1em;
        margin-top: 0.25em; /* Penyesuaian margin */
        border-radius: 0.25em;
        cursor: pointer;
        flex-shrink: 0;
    }
    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }
    /* Styling untuk item yang selesai */
    .list-group-item.bg-light.text-muted {
        opacity: 0.7; /* Lebih pudar */
        background-color: #f5fcf5 !important; /* Hijau sangat pucat */
        border-color: #dbebe3 !important;
    }
    .list-group-item.bg-light.text-muted strong,
    .list-group-item.bg-light.text-muted .small {
        text-decoration: line-through;
        color: #999 !important; /* Abu-abu lebih gelap untuk teks dicoret */
        font-weight: 400;
    }

    /* Konten dalam item list */
    .list-group-item > form.d-flex {
        flex-grow: 1;
        margin-right: 1rem;
    }
    .list-group-item .content-wrapper {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .list-group-item strong {
        font-size: 1rem; /* Ukuran judul standar */
        font-weight: 600;
        color: #333;
    }
    .list-group-item .small {
        font-size: 0.85em; /* Ukuran deskripsi */
        color: #666;
        margin-top: 0.2rem;
    }
    .list-group-item .text-muted {
        font-size: 0.75em; /* Ukuran waktu dibuat */
        display: flex;
        align-items: center;
        margin-top: 0.4rem;
        color: #888;
    }
    .list-group-item .text-muted .bi {
        margin-right: 0.2em;
    }

    /* Tombol aksi (edit & hapus) */
    .list-group-item .ms-2 {
        flex-shrink: 0;
        display: flex;
        gap: 0.4rem; /* Jarak antar tombol lebih kecil */
    }
    .btn-sm.btn-outline-primary, .btn-sm.btn-outline-danger {
        padding: 0.3rem 0.5rem; /* Padding sangat kecil */
        font-size: 0.8rem;
        border-radius: 0.3rem;
    }
    .btn-sm.btn-outline-primary {
        color: #4a69bd;
        border-color: #4a69bd;
    }
    .btn-sm.btn-outline-primary:hover {
        background-color: #4a69bd;
        color: white;
    }
    .btn-sm.btn-outline-danger {
        color: #dc3545; /* Merah Bootstrap */
        border-color: #dc3545;
    }
    .btn-sm.btn-outline-danger:hover {
        background-color: #dc3545;
        color: white;
    }
    .btn-sm .bi {
        font-size: 1em; /* Ukuran ikon sesuai tombol */
    }

    .alert {
        border-radius: 0.5rem;
        font-weight: 500;
        padding: 0.8rem 1.2rem;
        margin-bottom: 1.5rem; /* Jarak lebih kecil */
        font-size: 0.9rem;
    }
    /* Warna alert default Bootstrap */
    .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
    .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .alert-info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
    
    .card-footer {
        background-color: #f0f2f5; /* Sama dengan body background */
        border-top: 1px solid #e0e0e0;
        padding: 0.8rem 1.5rem; /* Padding lebih ringkas */
        font-size: 0.85rem;
        color: #888;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom-left-radius: 0.75rem;
        border-bottom-right-radius: 0.75rem;
    }
    .badge {
        font-size: 0.75em; /* Ukuran badge lebih kecil */
        padding: .3em .6em;
        border-radius: 0.5rem;
        font-weight: 600;
    }
    .badge.bg-primary { background-color: #4a69bd !important; }
    .badge.bg-success { background-color: #28a745 !important; }
  </style>
</head>
<body class="bg-light py-4">
<div class="container">
  <div class="card shadow">
    <div class="card-header bg-primary text-white d-flex align-items-center">
      <i class="bi bi-check2-all fs-4 me-2"></i> Daftar Tugas Harian
    </div>
    <div class="card-body">
      <?php if (isset($_GET['notif_type'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_GET['notif_type']) ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars(urldecode($_GET['notif_msg'])) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>

      <?php if ($idTugasUntukEdit && $dataTugasUntukEdit): ?>
      <div class="form-edit-container">
        <h5 class="mb-4"><i class="bi bi-pencil-square me-2"></i> Edit Tugas</h5>
        <form method="POST">
          <input type="hidden" name="id_item" value="<?= htmlspecialchars($dataTugasUntukEdit['identifier']) ?>">
          <input type="hidden" name="aksi" value="edit_item_proses">

          <div class="mb-3">
            <label for="judul_entri_edit" class="form-label">Judul Tugas</label>
            <input type="text" class="form-control" id="judul_entri_edit" name="judul_entri_edit" value="<?= htmlspecialchars($dataTugasUntukEdit['judul']) ?>" required>
          </div>
          
          <div class="mb-4">
            <label for="deskripsi_entri_edit" class="form-label">Deskripsi (Opsional)</label>
            <textarea class="form-control" id="deskripsi_entri_edit" name="deskripsi_entri_edit" rows="3" placeholder="Tambahkan detail tugas..."><?= htmlspecialchars($dataTugasUntukEdit['uraian']) ?></textarea>
          </div>
          
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-warning me-2">
              <i class="bi bi-save"></i> Simpan Perubahan
            </button>
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">
              <i class="bi bi-x-circle"></i> Batal
            </a>
          </div>
        </form>
      </div>
      <hr class="my-5">
      <?php else: ?>
      <form method="POST" class="mb-4">
        <input type="hidden" name="aksi" value="tambah">
        <h5 class="mb-4 text-primary"><i class="bi bi-plus-circle me-2"></i> Tambah Tugas Baru</h5>
        <div class="mb-3">
          <label for="judul_entri" class="form-label">Judul Tugas</label>
          <input type="text" name="judul_entri" class="form-control" placeholder="Contoh: Selesaikan laporan bulanan" required>
        </div>
        <div class="mb-4">
          <label for="deskripsi_entri" class="form-label">Deskripsi (Opsional)</label>
          <textarea name="deskripsi_entri" class="form-control" placeholder="Detail atau catatan tambahan..."></textarea>
        </div>
        <div class="d-flex justify-content-end">
          <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambahkan</button>
        </div>
      </form>
      <hr class="my-5">
      <?php endif; ?>

      <h2 class="h5 mb-4"><i class="bi bi-list-task me-2"></i> Daftar Tugas</h2>
      
      <div class="mb-4">
        <label class="form-label mb-2">Progress Tugas</label>
        <div class="progress">
          <div class="progress-bar bg-success" role="progressbar" style="width: <?= $persen ?>%;" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100">
            <span class="fw-bold"><?= $persen ?>%</span>
          </div>
        </div>
      </div>

      <?php if (empty($semuaTugas)): ?>
        <div class="alert alert-info text-center py-4">
          <i class="bi bi-check-lg me-2"></i> Hebat! Belum ada tugas saat ini. Tambahkan yang baru!
        </div>
      <?php else: ?>
        <div class="list-group">
        <?php foreach ($semuaTugas as $entri): ?>
          <div class="list-group-item d-flex justify-content-between align-items-start <?= $entri['status_selesai'] ? 'bg-light text-muted' : '' ?>">
            <form method="POST" class="d-flex w-100 me-3">
              <input type="hidden" name="id_item" value="<?= $entri['identifier'] ?>">
              <input type="hidden" name="aksi" value="alih_status">
              <input class="form-check-input me-2" type="checkbox" id="entri-<?= $entri['identifier'] ?>" <?= $entri['status_selesai'] ? 'checked' : '' ?> onchange="this.form.submit()">
              <div class="content-wrapper">
                <div><strong><?= htmlspecialchars($entri['judul']) ?></strong></div>
                <?php if (!empty($entri['uraian'])): ?>
                  <div class="small mt-1"><?= nl2br(htmlspecialchars($entri['uraian'])) ?></div>
                <?php endif; ?>
                <small class="text-muted mt-2"><i class="bi bi-clock me-1"></i>Dibuat: <?= $entri['waktu_buat'] ?></small>
              </div>
            </form>
            <div class="ms-2">
              <a href="?edit_id=<?= $entri['identifier'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Tugas">
                <i class="bi bi-pencil-fill"></i>
              </a>
              <form method="POST" class="d-inline-flex ms-2">
                <input type="hidden" name="id_item" value="<?= $entri['identifier'] ?>">
                <button name="aksi" value="hapus" class="btn btn-sm btn-outline-danger" title="Hapus Tugas">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="card-footer text-end text-muted small">
      Total: <span class="badge bg-primary"><?= $total ?></span> | Selesai: <span class="badge bg-success"><?= $selesai ?></span>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.onload = function() {
  const urlParams = new URLSearchParams(window.location.search);
  const notifType = urlParams.get('notif_type');
  const notifMsg = urlParams.get('notif_msg');
  if (notifType && notifMsg) {
    history.replaceState({}, document.title, window.location.pathname);
  }

  // Scroll otomatis ke bawah hanya jika notifikasi sukses dan pesan menunjukkan penambahan tugas
  if (notifType === "success" && notifMsg && decodeURIComponent(notifMsg).includes("ditambahkan")) {
    setTimeout(() => {
        const listGroup = document.querySelector('.list-group');
        if (listGroup) {
            listGroup.scrollTop = listGroup.scrollHeight; 
        } else {
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    }, 300);
  }
};
</script>
</body>
</html>