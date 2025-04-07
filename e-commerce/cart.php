<?php
session_start();

// Koneksi ke database
include 'db.php';

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Tambah produk ke keranjang
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Pastikan ID adalah integer
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }
        $_SESSION['cart'][$id] = [
            'nama_produk' => htmlspecialchars($product['nama_produk']),
            'harga' => floatval($product['harga']),
            'gambar' => htmlspecialchars($product['gambar']),
            'jumlah' => isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['jumlah'] + 1 : 1
        ];
    }
    header("Location: cart.php");
    exit;
}

// Update jumlah produk
if (isset($_POST['update'])) {
    foreach ($_POST['quantity'] as $id => $quantity) {
        $id = intval($id);
        $quantity = intval($quantity);
        if ($quantity > 0 && isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['jumlah'] = $quantity;
        } elseif ($quantity <= 0 && isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: cart.php");
    exit;
}

// Hapus produk dari keranjang
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit;
}

// Hapus semua item dari keranjang
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Keranjang Belanja</h1>
        
        <form action="cart.php" method="post">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        if (!empty($_SESSION['cart'])) {
                            foreach ($_SESSION['cart'] as $id => $item) {
                                $subtotal = $item['harga'] * $item['jumlah'];
                                $total += $subtotal;
                                echo "<tr>";
                                echo "<td>
                                    <img src='uploads/{$item['gambar']}' alt='{$item['nama_produk']}' width='50' class='me-3'>
                                    {$item['nama_produk']}
                                </td>";
                                echo "<td>Rp. " . number_format($item['harga'], 0, ',', '.') . "</td>";
                                echo "<td>
                                    <input type='number' name='quantity[{$id}]' value='{$item['jumlah']}' min='1' class='form-control' style='width: 70px;'>
                                </td>";
                                echo "<td>Rp. " . number_format($subtotal, 0, ',', '.') . "</td>";
                                echo "<td>
                                    <a href='cart.php?remove={$id}' class='btn btn-danger btn-sm'>Hapus</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>Keranjang belanja kosong</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" name="update" class="btn btn-primary">Update Keranjang</button>
                        <a href="cart.php?clear=true" class="btn btn-outline-danger">Kosongkan Keranjang</a>
                    </div>
                    <div class="col-md-6 text-end">
                        <h4>Total: Rp. <?php echo number_format($total, 0, ',', '.'); ?></h4>
                        <a href="checkout.php" class="btn btn-success btn-lg">Proses Checkout</a>
                    </div>
                </div>
            <?php endif; ?>
        </form>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-outline-secondary">&laquo; Lanjut Belanja</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>