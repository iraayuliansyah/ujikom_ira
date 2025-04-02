<?php 
// Menentukan informasi koneksi database
$host = "localhost"; // Nama host database (biasanya "localhost" jika dijalankan di server lokal)
$user = "root"; // Nama pengguna database (default untuk XAMPP adalah "root")
$pass = ""; // Kata sandi database (default untuk XAMPP adalah kosong)
$dbname = "taskhub_ira2"; // Nama database yang akan digunakan

// Membuat koneksi ke database menggunakan objek mysqli
$conn = new mysqli($host, $user, $pass, $dbname);

// Memeriksa apakah koneksi berhasil atau tidak
if ($conn->connect_error) {
    // Jika terjadi kesalahan saat koneksi, hentikan program dan tampilkan pesan error
    die("Koneksi gagal: " . $conn->connect_error);
}

// Jika sampai sini, berarti koneksi berhasil
?>
