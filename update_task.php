<?php
session_start(); // Memulai sesi untuk menyimpan pesan status
include 'config.php'; // Menghubungkan ke database

// Mengecek apakah request menggunakan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_id = $_POST['task_id']; // Mengambil ID tugas dari form
    $new_status = $_POST['new_status']; // Mengambil status baru dari form

    // Daftar status yang diperbolehkan
    $allowed_statuses = ["Open", "In Progress", "Done"];
    
    // Memastikan status yang dimasukkan valid
    if (!in_array($new_status, $allowed_statuses)) {
        $_SESSION['message'] = "Status tidak valid!"; // Menyimpan pesan error di sesi
        header("Location: dashboard.php"); // Mengarahkan kembali ke dashboard
        exit;
    }

    // Menyiapkan query SQL untuk memperbarui status tugas berdasarkan task_id
    $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $task_id); // Mengikat parameter status (string) dan ID tugas (integer)

    // Mengeksekusi query
    if ($stmt->execute()) {
        $_SESSION['message'] = "Status tugas berhasil diperbarui!"; // Pesan sukses
    } else {
        $_SESSION['message'] = "Gagal memperbarui status!"; // Pesan error jika gagal
    }

    // Redirect kembali ke dashboard setelah update
    header("Location: dashboard.php");
    exit;
}
?>
