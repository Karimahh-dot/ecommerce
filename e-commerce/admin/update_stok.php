<?php
session_start();
include '../db.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Proses update stok
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = intval($_POST['product_id']);
    $new_stock = intval($_POST['new_stock']);
    
    $stmt = $conn->prepare("UPDATE products SET stok = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_stock, $product_id);
    $stmt->execute();
    
    $_SESSION['message'] = "Stok berhasil diperbarui!";
    header("Location: update_stok.php");
    exit;
}

// Ambil semua produk
$sql = "SELECT id, nama_produk, stok FROM products ORDER BY nama_produk";
$result = $conn->query($sql);

// Ambil produk yang stoknya habis
$sql_low_stock = "SELECT nama_produk FROM products WHERE stok = 0";
$result_low_stock = $conn->query($sql_low_stock);

if ($result_low_stock->num_rows > 0) {
    echo '<div class="alert alert-warning mt-3">';
    echo '<h5>Produk yang stoknya habis:</h5>';
    echo '<ul>';
    while ($row = $result_low_stock->fetch_assoc()) {
        echo '<li>'.htmlspecialchars($row['nama_produk']).'</li>';
    }
    echo '</ul>';
    echo '</div>';
}

?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Stok Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Update Stok Produk</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Stok Saat Ini</th>
                    <th>Stok Baru</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td><?= $row['stok'] ?></td>
                    <td>
                        <form method="POST" class="row g-2">
                            <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                            <div class="col">
                                <input type="number" name="new_stock" class="form-control" min="0" value="<?= $row['stok'] ?>">
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </td>
                    <td>
                        <a href="restock.php?id=<?= $row['id'] ?>" class="btn btn-success">Restock +10</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>