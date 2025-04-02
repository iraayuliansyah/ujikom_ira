<?php
session_start(); // Memulai sesi untuk mengakses data sesi yang sedang aktif

session_destroy(); // Menghapus semua data sesi yang tersimpan (logout pengguna)

header("Location: login.php"); // Mengarahkan pengguna kembali ke halaman login setelah logout

exit; // Menghentikan eksekusi script untuk memastikan tidak ada kode yang berjalan setelah redirect
?>
