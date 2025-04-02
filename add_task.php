<?php
session_start();

// Cek apakah pengguna sudah login, jika tidak maka alihkan ke halaman login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include 'config.php'; // Menghubungkan ke database

// Mengecek apakah request yang diterima adalah metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form input
    $title = $_POST['title']; // Judul tugas
    $description = $_POST['description']; // Deskripsi tugas
    $due_date = $_POST['due_date']; // Tenggat waktu tugas
    $user_id = $_SESSION['user']['id']; // ID pengguna yang sedang login
    $status = "Open"; // Status awal tugas (default: Open)
    $file_path = null; // Inisialisasi path file lampiran (jika ada)

    // Proses Upload File jika ada file yang diunggah
    if (!empty($_FILES['attachment']['name'])) {
        $upload_dir = "uploads/"; // Direktori tempat menyimpan file
        $file_name = time() . "_" . basename($_FILES["attachment"]["name"]); // Penamaan unik dengan timestamp
        $target_file = $upload_dir . $file_name; // Path lengkap file yang akan disimpan
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION)); // Mendapatkan ekstensi file
        $allowed_types = ["pdf", "doc", "docx", "xls", "xlsx", "png", "jpg", "jpeg"]; // Ekstensi file yang diperbolehkan
    
        // Cek apakah ekstensi file sesuai dengan yang diperbolehkan
        if (in_array($file_type, $allowed_types)) {
            // Pindahkan file dari penyimpanan sementara ke folder tujuan
            if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
                $file_path = $target_file; // Simpan path file ke dalam variabel
                echo "File berhasil diupload: " . $file_path; // Debugging (bisa dihapus jika sudah berjalan baik)
            } else {
                echo "Gagal mengunggah file!"; // Pesan error jika gagal upload
            }
        }
    }

    // Menyimpan data tugas ke dalam database
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, due_date, status, file_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $title, $description, $due_date, $status, $file_path);
    $stmt->execute(); // Menjalankan query

    // Redirect kembali ke halaman dashboard setelah tugas berhasil ditambahkan
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id"> <!-- Menentukan bahasa halaman -->
<head>
    <meta charset="UTF-8"> <!-- Mengatur karakter encoding -->
    <title>Tambah Tugas</title> <!-- Judul halaman -->
    <link rel="stylesheet" href="css/add_task.css"> <!-- Menautkan file CSS untuk styling -->
</head>
<body>
    <h1>Tambah Tugas Baru</h1> <!-- Judul utama halaman -->

    <!-- Menampilkan pesan error jika ada -->
    <?php if (isset($error)): ?> 
        <p style="color: red;"><?php echo $error; ?></p> <!-- Pesan error ditampilkan dalam warna merah -->
    <?php endif; ?>

    <!-- Formulir untuk menambahkan tugas -->
    <form action="add_task.php" method="post" enctype="multipart/form-data">
        <label>Judul:</label> <!-- Label untuk input judul -->
        <input type="text" name="title" required> <!-- Input judul tugas (wajib diisi) -->

        <label>Deskripsi:</label> <!-- Label untuk deskripsi tugas -->
        <textarea name="description"></textarea> <!-- Area teks untuk deskripsi tugas -->

        <label>Tenggat Waktu:</label> <!-- Label untuk tenggat waktu -->
        <input type="date" name="due_date" required> <!-- Input tanggal dengan tipe date (wajib diisi) -->

        <label>Lampiran (PDF, Word, Excel, Gambar):</label> <!-- Label untuk file lampiran -->
        <input type="file" name="attachment"> <!-- Input untuk mengunggah file -->

        <button type="submit">Tambah Tugas</button> <!-- Tombol untuk mengirim formulir -->
    </form>

    <br>
    <a href="dashboard.php">Kembali ke Dashboard</a> <!-- Link untuk kembali ke halaman dashboard -->
</body>
</html>

