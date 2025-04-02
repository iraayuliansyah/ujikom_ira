<?php
session_start(); // Memulai sesi

// Cek apakah user sudah login, jika belum, redirect ke halaman login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php'; // Menyertakan file konfigurasi database

$user_id = $_SESSION['user']['id']; // Mengambil ID user dari sesi
$message = ''; // Variabel untuk menyimpan pesan error atau sukses

// Cek apakah ada parameter 'id' pada URL, jika tidak ada, redirect ke dashboard
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$task_id = $_GET['id']; // Ambil ID tugas dari URL

// Ambil data tugas dari database berdasarkan ID tugas dan user yang sedang login
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) { // Jika tugas tidak ditemukan, tampilkan pesan error
    echo "Tugas tidak ditemukan!";
    exit;
}

$file_path = $task['file_path']; // Simpan path file lama jika ada

// Jika form dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']); // Ambil dan bersihkan input judul
    $description = trim($_POST['description']); // Ambil dan bersihkan input deskripsi
    $due_date = $_POST['due_date']; // Ambil input tenggat waktu

    // Cek apakah ada file yang diunggah
    if (isset($_FILES["attachment"]) && $_FILES["attachment"]["error"] == 0) {
        $upload_dir = "uploads/"; // Direktori penyimpanan file
        if (!is_dir($upload_dir)) { // Jika folder belum ada, buat folder baru
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["attachment"]["name"]); // Buat nama unik untuk file
        $target_file = $upload_dir . $file_name; // Path tujuan file
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION)); // Dapatkan ekstensi file
        $allowed_types = ["jpg", "jpeg", "png", "pdf", "doc", "docx", "xls", "xlsx"]; // Format yang diperbolehkan

        if (in_array($file_type, $allowed_types)) { // Cek apakah format file diizinkan
            if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) { // Pindahkan file ke folder tujuan
                // Hapus file lama jika ada sebelum menyimpan file baru
                if (!empty($file_path) && file_exists($file_path)) {
                    unlink($file_path);
                }
                $file_path = $target_file; // Simpan path file baru
            } else {
                $message = "Gagal mengunggah file!"; // Jika gagal upload, tampilkan pesan error
            }
        } else {
            $message = "Format file tidak didukung!"; // Jika format tidak sesuai, tampilkan pesan error
        }
    }

    // Jika pengguna mencentang "hapus lampiran"
    if (isset($_POST['delete_attachment']) && $_POST['delete_attachment'] == "1") {
        if (!empty($file_path) && file_exists($file_path)) {
            unlink($file_path); // Hapus file dari server
        }
        $file_path = NULL; // Set file_path ke NULL agar dihapus di database
    }

    // Debugging: Catat file_path yang akan disimpan
    error_log("File Path yang akan disimpan: " . ($file_path ?? 'NULL'));

    // Buat query update, jika ada file_path maka update dengan path baru, jika tidak ada maka set NULL
    $query = (!empty($file_path)) 
        ? "UPDATE tasks SET title = '$title', description = '$description', due_date = '$due_date', file_path = '$file_path' WHERE id = '$task_id' AND user_id = '$user_id'" 
        : "UPDATE tasks SET title = '$title', description = '$description', due_date = '$due_date', file_path = NULL WHERE id = '$task_id' AND user_id = '$user_id'";

    // Debugging: Catat query yang dieksekusi
    error_log("QUERY yang dieksekusi: " . $query);

    if ($conn->query($query) === TRUE) { // Eksekusi query update
        error_log("UPDATE sukses!"); // Catat log jika berhasil
        header("Location: dashboard.php"); // Redirect ke dashboard setelah update
        exit;
    } else {
        error_log("UPDATE gagal: " . $conn->error); // Catat error jika gagal
        $message = "Gagal mengupdate tugas!";
    }

    // Cek apakah statement berhasil disiapkan
    if (!$stmt) {
        die("Error saat mempersiapkan query: " . $conn->error);
    }

    // Eksekusi query yang telah disiapkan
    if ($stmt->execute()) {
        error_log("UPDATE sukses!"); // Catat log jika berhasil
        header("Location: dashboard.php"); // Redirect ke dashboard
        exit;
    } else {
        error_log("UPDATE gagal: " . $stmt->error); // Catat log jika gagal
        $message = "Gagal mengupdate tugas!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"> <!-- Menentukan karakter encoding untuk dokumen -->
    <title>Edit Tugas</title> <!-- Judul halaman -->
    <link rel="stylesheet" href="css/edit_task.css"> <!-- Menyertakan file CSS untuk styling -->
</head>
<body>
    <h2>Edit Tugas</h2> <!-- Judul utama halaman -->

    <!-- Menampilkan pesan error jika ada -->
    <?php if ($message): ?>
        <?php error_reporting(E_ALL); ini_set('display_errors', 1); ?> <!-- Menampilkan error PHP (hanya untuk debugging) -->
        <p style="color:red;"><?php echo $message; ?></p> <!-- Menampilkan pesan error dengan warna merah -->
    <?php endif; ?>

    <!-- Form untuk mengedit tugas -->
    <form method="POST" enctype="multipart/form-data">
        <!-- Input untuk judul tugas -->
        <label>Judul Tugas:</label><br>
        <input type="text" name="title" required value="<?php echo htmlspecialchars($task['title']); ?>"><br><br>

        <!-- Input untuk deskripsi tugas -->
        <label>Deskripsi:</label><br>
        <textarea name="description" required><?php echo htmlspecialchars($task['description']); ?></textarea><br><br>

        <!-- Input untuk tenggat waktu tugas -->
        <label>Tenggat Waktu:</label><br>
        <input type="date" name="due_date" required value="<?php echo htmlspecialchars($task['due_date']); ?>"><br><br>

        <!-- Input untuk mengunggah lampiran -->
        <label>Lampiran (PDF, Word, Excel, Gambar):</label><br>
        <input type="file" name="attachment"><br><br>

        <!-- Menampilkan file lampiran jika ada -->
        <?php if (!empty($task['file_path'])): ?>
            <p><strong>Lampiran saat ini:</strong> 
                <a href="<?php echo htmlspecialchars($task['file_path']); ?>" target="_blank">Lihat File</a> <!-- Link untuk melihat file yang diunggah -->
            </p>

            <!-- Opsi untuk menghapus lampiran -->
            <label>
                <input type="checkbox" name="delete_attachment" value="1"> Hapus lampiran
            </label><br><br>
        <?php else: ?>
            <p><i>Tidak ada lampiran</i></p> <!-- Menampilkan pesan jika tidak ada lampiran -->
        <?php endif; ?>

        <!-- Tombol untuk menyimpan perubahan -->
        <button type="submit">Simpan Perubahan</button>
    </form>

    <!-- Link untuk kembali ke dashboard -->
    <p><a href="dashboard.php">Kembali ke Dashboard</a></p>
</body>
</html>

