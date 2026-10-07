<?php
session_start();
require_once '../config/database.php';

// Proteksi halaman - hanya operator dan admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fungsi helper
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Konfigurasi upload
$max_file_size = 100 * 1024 * 1024;
define('MAX_FILE_SIZE', $max_file_size);
define('UPLOAD_DIR', '../uploads/');

// Daftar ekstensi yang diizinkan
$allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];

// Daftar MIME yang diizinkan
$allowed_mimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'image/jpeg',
    'image/png',
    'image/x-png',
    'image/pjpeg',
    'application/octet-stream',
    'application/zip',
    'application/vnd.ms-office',
];

// Ambil data kategori
$kategori = db_select("SELECT * FROM kategori ORDER BY nama_kategori ASC");

// Ambil folder_id dari URL
$folder_id = isset($_GET['folder']) ? intval($_GET['folder']) : 0;

$error = '';
$success = '';
$form_data = [
    'judul_dokumen' => '',
    'kategori_id' => '',
    'versi' => '1.0',
    'tanggal_kadaluarsa' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan form data untuk refill jika error
    $form_data = [
        'judul_dokumen' => trim($_POST['judul_dokumen'] ?? ''),
        'kategori_id' => intval($_POST['kategori_id'] ?? 0),
        'versi' => trim($_POST['versi'] ?? '1.0'),
        'tanggal_kadaluarsa' => $_POST['tanggal_kadaluarsa'] ?? ''
    ];

    // Ambil folder_id dari form
    $folder_id_post = isset($_POST['folder_id']) ? intval($_POST['folder_id']) : null;

    $uploaded_by = intval($_SESSION['user_id']);

    // Validasi input
    $errors = [];

    if (empty($form_data['judul_dokumen'])) {
        $errors[] = "Judul dokumen wajib diisi.";
    } elseif (strlen($form_data['judul_dokumen']) > 255) {
        $errors[] = "Judul dokumen maksimal 255 karakter.";
    }

    if ($form_data['kategori_id'] <= 0) {
        $errors[] = "Kategori harus dipilih.";
    } else {
        $cek_kategori = db_select_one("SELECT id FROM kategori WHERE id = ?", [$form_data['kategori_id']]);
        if (!$cek_kategori) {
            $errors[] = "Kategori tidak valid.";
        }
    }

    if (empty($form_data['versi'])) {
        $errors[] = "Versi wajib diisi.";
    } elseif (!preg_match('/^\d+(\.\d+)*$/', $form_data['versi'])) {
        $errors[] = "Format versi tidak valid (contoh: 1.0, 2.1.3).";
    }

    if (empty($form_data['tanggal_kadaluarsa'])) {
        $errors[] = "Tanggal kadaluarsa wajib diisi.";
    } elseif (strtotime($form_data['tanggal_kadaluarsa']) < strtotime(date('Y-m-d'))) {
        $errors[] = "Tanggal kadaluarsa tidak boleh kurang dari hari ini.";
    }

    // Validasi file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "File dokumen wajib diupload.";
    } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Terjadi kesalahan saat upload file.";
    } else {
        $file = $_FILES['file'];

        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = "Ukuran file maksimal " . formatBytes(MAX_FILE_SIZE) . ". File Anda: " . formatBytes($file['size']);
        }

        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = "Format file tidak diizinkan. Hanya: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG.";
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $is_valid_mime = in_array($mime_type, $allowed_mimes);

        if (!$is_valid_mime && in_array($file_extension, ['docx', 'xlsx', 'pptx'])) {
            $handle = fopen($file['tmp_name'], 'rb');
            if ($handle) {
                $magic = fread($handle, 2);
                fclose($handle);
                if ($magic === 'PK') {
                    $is_valid_mime = true;
                }
            }
        }

        if (!$is_valid_mime && in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
            $img_info = @getimagesize($file['tmp_name']);
            if ($img_info && in_array($img_info['mime'], ['image/jpeg', 'image/png'])) {
                $is_valid_mime = true;
            }
        }

        if (!$is_valid_mime) {
            $errors[] = "Format file tidak dikenali atau file corrupt. Pastikan file asli (bukan rename manual).";
        }

        $existing = db_select_one(
            "SELECT id FROM dokumen WHERE judul_dokumen = ? AND uploaded_by = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$form_data['judul_dokumen'], $uploaded_by]
        );

        if ($existing) {
            $errors[] = "Anda sudah mengupload dokumen dengan judul serupa baru-baru ini.";
        }
    }

    // Proses upload jika tidak ada error
    if (empty($errors)) {
        $year_dir = UPLOAD_DIR . date('Y') . '/';
        $month_dir = $year_dir . date('m') . '/';

        if (!file_exists($month_dir)) {
            mkdir($month_dir, 0755, true);
        }

        $original_name = pathinfo($file['name'], PATHINFO_FILENAME);
        $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original_name);
        $file_name = sprintf(
            '%s_%s_%s.%s',
            date('Ymd_His'),
            $safe_name,
            bin2hex(random_bytes(4)),
            $file_extension
        );

        $target_file = $month_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            chmod($target_file, 0644);

            // Data yang akan disimpan
            $data = [
                'judul_dokumen' => $form_data['judul_dokumen'],
                'kategori_id' => $form_data['kategori_id'],
                'nama_file' => date('Y') . '/' . date('m') . '/' . $file_name,
                'ukuran_file' => $file['size'],
                'tipe_file' => $mime_type,
                'versi' => $form_data['versi'],
                'tanggal_upload' => date('Y-m-d H:i:s'),
                'tanggal_kadaluarsa' => $form_data['tanggal_kadaluarsa'],
                'status' => 'aktif',
                'uploaded_by' => $uploaded_by
            ];

            // Tambahkan folder_id kalau ada
            if ($folder_id_post > 0) {
                $data['folder_id'] = $folder_id_post;
            }

            $insert_id = db_insert('dokumen', $data);

            if ($insert_id) {
                db_insert('log_aktivitas', [
                    'user_id' => $uploaded_by,
                    'aksi' => 'upload_dokumen',
                    'detail' => 'Upload dokumen: ' . $form_data['judul_dokumen'] . ($folder_id_post > 0 ? ' (Folder ID: ' . $folder_id_post . ')' : ''),
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $redirect = "list.php?success=" . urlencode("Dokumen berhasil diupload");
                if ($folder_id_post > 0) {
                    $redirect .= "&folder=" . $folder_id_post;
                }
                header("Location: " . $redirect);
                exit();
            } else {
                unlink($target_file);
                $error = "Gagal menyimpan data ke database.";
            }
        } else {
            $error = "Gagal mengupload file ke server.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Ambil info folder untuk tampilan
$folder_info = null;
if ($folder_id > 0) {
    $folder_info = db_select_one("SELECT nama_folder FROM folders WHERE id = ?", [$folder_id]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Dokumen - SPMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .sidebar-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
            border-radius: 8px;
        }

        .sidebar {
            min-height: 100vh;
            background: #27ae60;
            color: white;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 12px 20px;
            border-radius: 0 25px 25px 0;
            margin-right: 12px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }

        .sidebar .nav-link i {
            font-size: 1.1rem;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .file-drop-zone {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-drop-zone:hover, .file-drop-zone.dragover {
            border-color: #27ae60;
            background: #f8fff9;
        }

        .file-drop-zone i {
            font-size: 3rem;
            color: #6c757d;
        }

        .preview-file {
            display: none;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .folder-badge {
            background: #e8f0fe;
            color: #1a73e8;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-4 text-center border-bottom border-light border-opacity-25">
                    <img src="../assets/images/logo.png" alt="Logo" class="sidebar-logo mb-2">
                    <h5 class="mb-1 fw-bold">SPMI System</h5>
                    <small class="opacity-75">Operator Panel</small>
                </div>
                <ul class="nav flex-column mt-2">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/operator.php">
                            <i class="bi bi-speedometer2 me-3"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="upload.php<?php echo $folder_id > 0 ? '?folder=' . $folder_id : ''; ?>">
                            <i class="bi bi-upload me-3"></i> Upload Dokumen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="list.php<?php echo $folder_id > 0 ? '?folder=' . $folder_id : ''; ?>">
                            <i class="bi bi-folder me-3"></i> Daftar Dokumen
                        </a>
                    </li>
                    <li class="nav-item mt-auto">
                        <a class="nav-link text-danger" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-3"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-expand-lg p-3">
                    <div class="container-fluid">
                        <span class="navbar-brand mb-0 h1">Upload Dokumen Baru</span>
                    </div>
                </nav>

                <div class="p-4">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0"><i class="bi bi-cloud-upload me-2"></i>Form Upload Dokumen</h5>
                                    <?php if ($folder_id > 0 && $folder_info): ?>
                                    <div class="mt-2">
                                        <span class="folder-badge">
                                            <i class="bi bi-folder-fill"></i>
                                            Folder tujuan: <strong><?php echo e($folder_info['nama_folder']); ?></strong>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($error)): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            <?php echo $error; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                                        <!-- Input hidden untuk folder_id -->
                                        <input type="hidden" name="folder_id" value="<?php echo $folder_id; ?>">

                                        <div class="mb-3">
                                            <label for="judul_dokumen" class="form-label">Judul Dokumen <span class="text-danger">*</span></label>
                                            <input type="text" 
                                                   name="judul_dokumen" 
                                                   id="judul_dokumen"
                                                   class="form-control" 
                                                   value="<?php echo e($form_data['judul_dokumen']); ?>"
                                                   maxlength="255"
                                                   required 
                                                   placeholder="Masukkan judul dokumen">
                                            <div class="form-text">Maksimal 255 karakter</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                                            <select name="kategori_id" id="kategori_id" class="form-select" required>
                                                <option value="">-- Pilih Kategori --</option>
                                                <?php foreach ($kategori as $kat): ?>
                                                <option value="<?php echo (int)$kat['id']; ?>" 
                                                    <?php echo $form_data['kategori_id'] == $kat['id'] ? 'selected' : ''; ?>>
                                                    <?php echo e($kat['nama_kategori']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="versi" class="form-label">Versi <span class="text-danger">*</span></label>
                                                <input type="text" 
                                                       name="versi" 
                                                       id="versi"
                                                       class="form-control" 
                                                       value="<?php echo e($form_data['versi']); ?>"
                                                       required 
                                                       placeholder="Contoh: 1.0">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="tanggal_kadaluarsa" class="form-label">Tanggal Kadaluarsa <span class="text-danger">*</span></label>
                                                <input type="date" 
                                                       name="tanggal_kadaluarsa" 
                                                       id="tanggal_kadaluarsa"
                                                       class="form-control" 
                                                       value="<?php echo e($form_data['tanggal_kadaluarsa']); ?>"
                                                       min="<?php echo date('Y-m-d'); ?>"
                                                       required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">File Dokumen <span class="text-danger">*</span></label>
                                            <div class="file-drop-zone" id="dropZone">
                                                <i class="bi bi-cloud-arrow-up"></i>
                                                <p class="mb-1">Klik atau drag & drop file disini</p>
                                                <small class="text-muted">
                                                    PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, JPEG, PNG<br>
                                                    (Maks. <?php echo formatBytes(MAX_FILE_SIZE); ?>)
                                                </small>
                                                <input type="file" name="file" id="fileInput" class="d-none" 
                                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png" required>
                                            </div>

                                            <div class="preview-file" id="filePreview">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-text fs-2 me-3 text-primary"></i>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1" id="previewName">filename.pdf</h6>
                                                        <small class="text-muted" id="previewSize">2.5 MB</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                            <a href="list.php<?php echo $folder_id > 0 ? '?folder=' . $folder_id : ''; ?>" class="btn btn-secondary">
                                                <i class="bi bi-x-circle me-2"></i>Batal
                                            </a>
                                            <button type="submit" class="btn btn-success" id="btnSubmit">
                                                <i class="bi bi-upload me-2"></i>Upload Dokumen
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Info Card -->
                            <div class="card mt-3 border-info">
                                <div class="card-body bg-light">
                                    <h6 class="card-title text-info"><i class="bi bi-info-circle me-2"></i>Informasi</h6>
                                    <ul class="small text-muted mb-0">
                                        <li>Pastikan dokumen sudah direview sebelum diupload</li>
                                        <li>Versi dokumen mengikuti format semantic versioning (1.0, 1.1, 2.0, dll)</li>
                                        <li>Dokumen akan otomatis berstatus "Perlu Update" setelah tanggal kadaluarsa</li>
                                        <li>File yang diupload tidak dapat diubah, hanya bisa diupload versi baru</li>
                                        <li>Ukuran maksimal file: <strong><?php echo formatBytes(MAX_FILE_SIZE); ?></strong></li>
                                        <?php if ($folder_id > 0): ?>
                                        <li>Dokumen akan disimpan di folder yang sedang aktif</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const previewName = document.getElementById('previewName');
        const previewSize = document.getElementById('previewSize');
        const removeFile = document.getElementById('removeFile');
        const uploadForm = document.getElementById('uploadForm');
        const btnSubmit = document.getElementById('btnSubmit');

        const allowedExts = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png'];
        const maxSize = <?php echo MAX_FILE_SIZE; ?>;

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                validateAndPreview(files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                validateAndPreview(fileInput.files[0]);
            }
        });

        function validateAndPreview(file) {
            const ext = file.name.split('.').pop().toLowerCase();

            if (!allowedExts.includes(ext)) {
                alert('Format file tidak diizinkan.\nFormat yang diperbolehkan: ' + allowedExts.join(', '));
                fileInput.value = '';
                return;
            }

            if (file.size > maxSize) {
                alert('Ukuran file melebihi ' + formatBytes(maxSize) + '!');
                fileInput.value = '';
                return;
            }

            showPreview(file);
        }

        function showPreview(file) {
            previewName.textContent = file.name;
            previewSize.textContent = formatBytes(file.size);
            filePreview.style.display = 'block';
            dropZone.style.display = 'none';
        }

        removeFile.addEventListener('click', () => {
            fileInput.value = '';
            filePreview.style.display = 'none';
            dropZone.style.display = 'block';
        });

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        uploadForm.addEventListener('submit', (e) => {
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Pilih file terlebih dahulu!');
                return;
            }
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
        });
    </script>
</body>
</html>