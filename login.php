<?php
// Menyertakan file konfigurasi database
include 'config.php';

// Memulai sesi untuk menyimpan data pengguna setelah login
session_start();

// Mengecek apakah metode yang digunakan adalah POST (form dikirim)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form input
    $email = $_POST['email']; // Mengambil input email
    $password = $_POST['password']; // Mengambil input password

    // Mempersiapkan query untuk mencari pengguna berdasarkan email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc(); // Mengambil hasil query sebagai array asosiatif

    // Mengecek apakah pengguna ditemukan dan password cocok
    if ($user && password_verify($password, $user['password'])) {
        // Jika login berhasil, simpan data pengguna di session
        $_SESSION['user'] = $user;

        // Arahkan pengguna ke halaman dashboard
        header("Location: dashboard.php");
        exit; // Menghentikan eksekusi skrip setelah redirect
    } else {
        // Jika email atau password salah, tampilkan pesan error
        echo "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css"> <!-- Menyertakan file CSS untuk tampilan -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body>
    <div class="login-container"> <!-- Container utama untuk form login -->
        <form method="POST"> <!-- Form untuk login, metode POST digunakan untuk mengirim data secara aman -->
            <h2>Login Pengguna</h2> <!-- Judul form login -->

            <!-- Input email -->
            <input type="email" name="email" required placeholder="Email"> <!-- Input untuk email pengguna, required agar wajib diisi -->
            <br><br>

            <!-- Container untuk input password dan ikon toggle -->
            <div class="password-container">
                <input type="password" id="password" name="password" required placeholder="Password"> <!-- Input untuk password -->
                <i id="togglePassword" class="fa fa-eye-slash"></i> <!-- Ikon untuk menampilkan atau menyembunyikan password -->
            </div>
            <br>

            <!-- Tombol submit untuk login -->
            <button type="submit">Login</button>

            <!-- Link ke halaman registrasi jika pengguna belum memiliki akun -->
            <p>Belum punya akun? <a href="register.php">Daftar</a></p>
        </form>
    </div>
</body>

<script>
    // Menambahkan event listener ke ikon toggle password
    document.getElementById("togglePassword").addEventListener("click", function() {
        // Mengambil elemen input password berdasarkan ID
        var passwordField = document.getElementById("password");
        // Mengambil elemen ikon toggle berdasarkan ID
        var icon = document.getElementById("togglePassword");

        // Mengecek apakah tipe input saat ini adalah "password"
        if (passwordField.type === "password") {
            // Jika iya, ubah tipe input menjadi "text" agar password terlihat
            passwordField.type = "text";
            // Menghapus ikon mata tertutup (fa-eye-slash)
            icon.classList.remove("fa-eye-slash");
            // Menambahkan ikon mata terbuka (fa-eye) untuk menunjukkan password terlihat
            icon.classList.add("fa-eye");
        } else {
            // Jika tidak, ubah kembali tipe input menjadi "password" agar password tersembunyi
            passwordField.type = "password";
            // Menghapus ikon mata terbuka (fa-eye)
            icon.classList.remove("fa-eye");
            // Menambahkan ikon mata tertutup (fa-eye-slash) untuk menunjukkan password tersembunyi
            icon.classList.add("fa-eye-slash");
        }
    });
</script>



</html>