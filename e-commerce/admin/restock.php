<?php
session_start();
include '../db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Tambahkan 10 stok
    $stmt = $conn->prepare("UPDATE products SET stok = stok + 10 WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    $_SESSION['message'] = "Stok berhasil ditambahkan!";
}

header("Location: update_stok.php");
exit;
?>