<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

include '../db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Hapus gambar produk
    $sql = "SELECT gambar FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row && file_exists("../uploads/" . $row['gambar'])) {
        unlink("../uploads/" . $row['gambar']);
    }
    
    // Hapus produk dari database
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Produk berhasil dihapus!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
}

header("Location: index.php");
exit;
?>