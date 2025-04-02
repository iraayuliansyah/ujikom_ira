<?php
session_start(); // Memulai sesi PHP untuk menyimpan data pengguna yang sedang login

// Mengecek apakah pengguna sudah login
if (!isset($_SESSION['user'])) {
    header("Location: login.php"); // Jika belum login, alihkan ke halaman login
    exit; // Hentikan eksekusi skrip
}

include 'config.php'; // Menghubungkan ke file konfigurasi database

$statuses = ["Open", "In Progress", "Done"]; // Daftar status tugas yang tersedia
$tasks = []; // Array kosong untuk menyimpan daftar tugas pengguna

$user_id = $_SESSION['user']['id']; // Mengambil ID pengguna yang sedang login dari sesi

$tasks_per_page = 3; // Menentukan jumlah tugas yang ditampilkan per halaman

// Mengambil nomor halaman dari parameter URL, default ke halaman 1 jika tidak ada
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Menghitung offset untuk query SQL (posisi mulai pengambilan data)
$offset = ($page - 1) * $tasks_per_page;

// Looping untuk mengambil tugas berdasarkan setiap status
foreach ($statuses as $status) {

    // Menyiapkan query SQL untuk mengambil tugas berdasarkan user_id dan status tertentu
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id = ? AND status = ? LIMIT ? OFFSET ?");

    // Mengikat parameter ke dalam query
    $stmt->bind_param("isii", $user_id, $status, $tasks_per_page, $offset);

    // Menjalankan query
    $stmt->execute();

    // Mengambil hasil query
    $result = $stmt->get_result();

    // Menyimpan hasil tugas ke dalam array berdasarkan statusnya
    $tasks[$status] = $result->fetch_all(MYSQLI_ASSOC);

    // Query untuk menghitung total jumlah tugas berdasarkan user_id dan status tertentu
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = ?");

    // Mengikat parameter untuk query
    $count_stmt->bind_param("is", $user_id, $status);

    // Menjalankan query
    $count_stmt->execute();

    // Mengikat hasil perhitungan ke variabel
    $count_stmt->bind_result($total_tasks);

    // Mengambil nilai hasil query
    $count_stmt->fetch();

    // Menutup statement setelah digunakan
    $count_stmt->close();

    // Menghitung jumlah halaman berdasarkan total tugas dan jumlah tugas per halaman
    $total_pages[$status] = ceil($total_tasks / $tasks_per_page);
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Tugas</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <nav class="navbar"> <!-- Membuat navigasi dengan class "navbar" untuk styling -->
        <h2>TaskHub</h2> <!-- Menampilkan judul atau logo TaskHub -->

        <div class="nav-right"> <!-- Container untuk elemen navigasi di sebelah kanan -->
            <a href="add_task.php" class="btn-add">+ Tambah Tugas</a> <!-- Tombol untuk menambahkan tugas baru -->

            <!-- Menampilkan nama pengguna yang sedang login -->
            <p class="user-info">Halo, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></p>

            <p>|</p> <!-- Garis pemisah antara informasi pengguna dan tombol logout -->

            <a href="logout.php" class="btn-logout">Logout</a> <!-- Tombol untuk logout dari sistem -->
        </div>
    </nav>



    <div class="container"> <!-- Container utama yang menampung semua kolom status tugas -->

        <?php foreach ($statuses as $status): ?>
            <!-- Loop melalui setiap status tugas (Open, In Progress, Done) untuk menampilkan tugas yang sesuai -->

            <div class="column"> <!-- Kolom untuk setiap status tugas -->
                <h3><?php echo $status; ?></h3>
                <!-- Menampilkan nama status tugas sebagai judul kolom -->

                <?php if (!empty($tasks[$status])): ?>
                    <!-- Memeriksa apakah ada tugas dalam status ini -->

                    <?php foreach ($tasks[$status] as $task): ?>
                        <!-- Loop untuk menampilkan setiap tugas dalam status ini -->

                        <div class="task"> <!-- Container untuk setiap tugas -->
                            <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                            <!-- Menampilkan judul tugas dengan htmlspecialchars untuk mencegah XSS -->

                            <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                            <!-- Menampilkan deskripsi tugas dengan nl2br agar newline tetap terlihat dan htmlspecialchars untuk keamanan -->

                            <small>
                                <strong>Tenggat Waktu:</strong>
                                <?php echo date("d M Y", strtotime($task['due_date'])); ?>
                                <!-- Menampilkan tenggat waktu tugas dengan format tanggal yang lebih mudah dibaca (dd MMM YYYY) -->
                            </small>


                            <!-- Lampiran File -->
                            <?php if (!empty($task['file_path'])): ?> <!-- Mengecek apakah tugas memiliki lampiran file -->
                                <button onclick="toggleAttachment(<?php echo $task['id']; ?>)" class="btn">View Lampiran</button>
                                <!-- Tombol untuk menampilkan atau menyembunyikan lampiran file -->

                                <div id="attachment-<?php echo $task['id']; ?>" class="file-attachment" style="display: none;">
                                    <!-- Kontainer untuk menampilkan lampiran dengan ID unik berdasarkan task ID -->

                                    <p><strong>Lampiran:</strong></p> <!-- Judul untuk bagian lampiran -->

                                    <?php
                                    // Mendapatkan ekstensi file dari file yang diunggah
                                    $file_extension = strtolower(pathinfo($task['file_path'], PATHINFO_EXTENSION));

                                    // Menyusun URL file berdasarkan direktori penyimpanan
                                    $file_url = "http://localhost/ujikom_ira2/" . $task['file_path'];

                                    // Mengambil nama file dari URL
                                    $file_name = basename($file_url);
                                    ?>

                                    <?php if (in_array($file_extension, ['jpg', 'jpeg', 'png'])): ?>
                                        <!-- Jika file adalah gambar, tampilkan sebagai elemen img -->
                                        <img src="<?php echo $file_url; ?>" alt="Lampiran" width="100%" height="200">

                                    <?php elseif ($file_extension == 'pdf'): ?>
                                        <!-- Jika file adalah PDF, tampilkan dalam iframe untuk pratinjau -->
                                        <iframe src="<?php echo $file_url; ?>" width="100%" height="200px"></iframe>

                                    <?php elseif (in_array($file_extension, ['doc', 'docx', 'xls', 'xlsx'])): ?>
                                        <!-- Jika file adalah dokumen Word atau Excel, hanya tampilkan nama file -->
                                        <p>File: <?php echo $file_name; ?></p>
                                    <?php endif; ?>

                                    <br> <!-- Baris baru untuk memberikan jarak -->

                                    <!-- Tombol untuk mengunduh file lampiran -->
                                    <a href="<?php echo $file_url; ?>" download="<?php echo $file_name; ?>" class="btn">Unduh Lampiran</a>
                                </div>
                            <?php endif; ?> <!-- Akhir pengecekan apakah ada lampiran -->

                            <!-- Form untuk mengubah status tugas -->
                            <form action="update_task.php" method="POST">
                                <!-- Mengirimkan data ke file update_task.php dengan metode POST -->

                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <!-- Menyimpan ID tugas dalam input tersembunyi agar dapat dikenali saat diproses -->

                                <label for="status_<?php echo $task['id']; ?>">Pindahkan ke:</label>
                                <!-- Label untuk dropdown pilihan status tugas -->

                                <select name="new_status" id="status_<?php echo $task['id']; ?>" class="status-select">
                                    <!-- Dropdown untuk memilih status baru tugas -->

                                    <?php foreach ($statuses as $option): ?>
                                        <!-- Loop untuk menampilkan semua status yang tersedia sebagai opsi dalam dropdown -->

                                        <option value="<?php echo $option; ?>" <?php echo ($task['status'] === $option) ? 'selected' : ''; ?>>
                                            <!-- Jika status saat ini sama dengan opsi, tambahkan atribut "selected" -->
                                            <?php echo $option; ?> <!-- Menampilkan nama status sebagai opsi -->
                                        </option>

                                    <?php endforeach; ?>
                                </select>

                                <button type="submit" class="btn-move">Pindah</button>
                                <!-- Tombol untuk mengirimkan formulir dan memperbarui status tugas -->
                            </form>

                            <!-- Tombol Edit & Hapus -->
                            <div class="task-actions"> <!-- Container untuk tombol aksi pada setiap tugas -->

                                <!-- Tombol Edit -->
                                <a href="edit_task.php?id=<?php echo $task['id']; ?>" class="btn-edit">Edit</a>
                                <!-- Mengarahkan pengguna ke halaman edit_task.php dengan menyertakan ID tugas dalam URL -->

                                <!-- Form untuk menghapus tugas -->
                                <form action="delete_task.php" method="POST" onsubmit="return confirm('Yakin ingin menghapus tugas ini?');">
                                    <!-- Form dikirim ke delete_task.php menggunakan metode POST -->
                                    <!-- onsubmit digunakan untuk menampilkan konfirmasi sebelum penghapusan -->

                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <!-- Menyimpan ID tugas dalam input tersembunyi agar dapat dikenali saat dihapus -->

                                    <button type="submit" class="btn-delete">Hapus</button>
                                    <!-- Tombol untuk menghapus tugas setelah konfirmasi -->
                                </form>

                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-task">Tidak ada tugas</p>
                <?php endif; ?>



                <!-- Pagination (Navigasi Halaman) -->
                <div class="pagination"> <!-- Container untuk navigasi halaman -->

                    <?php if ($page > 1): ?>
                        <!-- Jika halaman saat ini lebih dari 1, tampilkan tombol "Previous" -->
                        <a href="?page=<?php echo $page - 1; ?>" class="btn">&laquo; Prev</a>
                        <!-- Link menuju halaman sebelumnya dengan mengurangi nomor halaman saat ini -->
                    <?php endif; ?>

                    <span>Halaman <?php echo $page; ?> dari <?php echo $total_pages[$status]; ?></span>
                    <!-- Menampilkan informasi halaman saat ini dan jumlah total halaman -->

                    <?php if ($page < $total_pages[$status]): ?>
                        <!-- Jika halaman saat ini kurang dari total halaman, tampilkan tombol "Next" -->
                        <a href="?page=<?php echo $page + 1; ?>" class="btn">Next &raquo;</a>
                        <!-- Link menuju halaman berikutnya dengan menambah nomor halaman saat ini -->
                    <?php endif; ?>

                </div>

            </div>
        <?php endforeach; ?>
    </div>


</body>
<script>
    // Fungsi untuk menampilkan atau menyembunyikan lampiran berdasarkan taskId
    function toggleAttachment(taskId) {
        // Mengambil elemen container lampiran berdasarkan ID tugas
        var attachmentContainer = document.getElementById("attachment-" + taskId);

        // Mengecek apakah elemen saat ini tersembunyi atau tidak
        if (attachmentContainer.style.display === "none" || attachmentContainer.style.display === "") {
            // Jika tersembunyi, tampilkan elemen
            attachmentContainer.style.display = "block";
        } else {
            // Jika sudah terlihat, sembunyikan elemen
            attachmentContainer.style.display = "none";
        }
    }

    // Fungsi untuk mengubah ukuran tampilan PDF berdasarkan taskId
    function resizePdf(taskId) {
        // Mengambil elemen iframe PDF berdasarkan ID tugas
        var iframe = document.getElementById("pdf-" + taskId);
        // Mengambil elemen tombol untuk mengubah teksnya
        var button = document.getElementById("resize-btn-" + taskId);

        // Mengecek apakah tinggi iframe saat ini adalah 400px
        if (iframe.style.height === "400px") {
            // Jika iya, kecilkan ukuran iframe menjadi 200px
            iframe.style.height = "200px";
            // Ubah teks tombol menjadi "Perbesar Tampilan"
            button.innerHTML = "Perbesar Tampilan";
        } else {
            // Jika tidak, ubah ukuran iframe menjadi 400px
            iframe.style.height = "400px";
            // Ubah teks tombol menjadi "Perkecil Tampilan"
            button.innerHTML = "Perkecil Tampilan";
        }
    }
</script>


</html>