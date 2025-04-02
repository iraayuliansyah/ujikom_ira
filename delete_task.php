<?php
session_start(); // Memulai sesi untuk menyimpan pesan status
include 'config.php'; // Menghubungkan ke database

// Mengecek apakah request dikirim dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id']; // Mengambil ID tugas dari form

    // Menyiapkan query SQL untuk menghapus tugas berdasarkan ID
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id); // Mengikat parameter ID tugas (integer)

    // Mengeksekusi query
    if ($stmt->execute()) {
        $_SESSION['message'] = "Tugas berhasil dihapus!"; // Pesan sukses jika tugas berhasil dihapus
    } else {
        $_SESSION['message'] = "Gagal menghapus tugas!"; // Pesan error jika tugas gagal dihapus
    }

    // Redirect kembali ke dashboard setelah proses penghapusan selesai
    header("Location: dashboard.php");
    exit;
}
?>
