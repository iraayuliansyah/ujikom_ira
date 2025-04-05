<?php
// Menyertakan file konfigurasi database
include 'config.php';

// Variabel untuk menyimpan pesan notifikasi
$message = '';

// Mengecek apakah metode yang digunakan adalah POST (form dikirim)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form input
    $name     = trim($_POST['name']); // Menghapus spasi sebelum dan sesudah nama
    $email    = trim($_POST['email']); // Menghapus spasi sebelum dan sesudah email
    $password = $_POST['password']; // Mengambil password tanpa di-trim (karena spasi bisa jadi bagian password)

    // Pola validasi password:
    // Minimal 8 karakter, mengandung huruf, angka, dan simbol (@$!%*#?&_-)
    $pattern = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&_\\-])[A-Za-z\d@$!%*#?&_\\-]{8,}$/';

    // Mengecek apakah password sesuai dengan pola yang ditentukan
    if (!preg_match($pattern, $password)) {
        $message = "Password harus minimal 8 karakter dan mengandung huruf, angka, dan simbol (termasuk _ dan -)";
    } else {
        // Mengecek apakah email sudah terdaftar di database
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Jika email sudah terdaftar, arahkan ke halaman login dengan notifikasi
            header("Location: login.php?akun_sudah_terdaftar");
            exit;
        } else {
            // Mengenkripsi password sebelum disimpan ke database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Mempersiapkan query untuk memasukkan data baru ke tabel users
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if (!$stmt) {
                // Jika ada kesalahan dalam query, simpan pesan error
                $message = "Gagal menyiapkan statement: " . $conn->error;
            } else {
                // Mengikat parameter ke statement dan mengeksekusi query
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                if ($stmt->execute()) {
                    // Jika pendaftaran berhasil, arahkan ke halaman registrasi succes dengan notifikasi
                    header("Location: register_succes.php?notif=registrasi_berhasil");
                    exit;
                } else {
                    // Jika terjadi kesalahan saat eksekusi, simpan pesan error
                    $message = "Gagal mendaftar: " . $stmt->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Registrasi Pengguna</title>
    <link rel="stylesheet" href="css/register.css"> <!-- Menyertakan file CSS untuk styling -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="register-container"> <!-- Container utama untuk form registrasi -->
        <h2>Registrasi Pengguna</h2>

        <!-- Menampilkan pesan error jika ada -->
        <?php if ($message): ?>
            <p style="color:red;"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- Form registrasi -->
        <form method="POST" action="">
            <label>Nama:</label><br>
            <input type="text" name="name" required placeholder="Masukkan nama"><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" required placeholder="Masukkan email"><br><br>

            <label>Password:</label><br>
            <div class="password-container">
                <input type="password" id="registerPassword" name="password" required placeholder="Min 8 karakter, huruf, angka, simbol">
                <i id="toggleRegisterPassword" class="fa fa-eye-slash"></i>
            </div>
            <br><br>

            <button type="submit">Daftar</button> <!-- Tombol submit -->
        </form>

        <!-- Link ke halaman login jika pengguna sudah memiliki akun -->
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>
</body>
<script>
    // Menambahkan event listener ke elemen dengan ID "toggleRegisterPassword"
    document.getElementById("toggleRegisterPassword").addEventListener("click", function() {

        // Mengambil elemen input password dengan ID "registerPassword"
        let passwordInput = document.getElementById("registerPassword");

        // Mengecek apakah tipe input saat ini adalah "password"
        if (passwordInput.type === "password") {
            // Jika iya, ubah tipe input menjadi "text" agar password terlihat
            passwordInput.type = "text";

            // Menghapus kelas ikon mata tertutup (fa-eye-slash)
            this.classList.remove("fa-eye-slash");

            // Menambahkan kelas ikon mata terbuka (fa-eye)
            this.classList.add("fa-eye");
        } else {
            // Jika tidak, ubah kembali tipe input menjadi "password" agar password tersembunyi
            passwordInput.type = "password";

            // Menghapus kelas ikon mata terbuka (fa-eye)
            this.classList.remove("fa-eye");

            // Menambahkan kembali kelas ikon mata tertutup (fa-eye-slash)
            this.classList.add("fa-eye-slash");
        }
    });
</script>


</html>