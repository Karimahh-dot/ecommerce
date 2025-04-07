<?php
session_start();

// Redirect jika bukan admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Koneksi ke database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ecommerce_db';
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Tambah produk
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_produk'])) {
    $nama_produk = $conn->real_escape_string($_POST['nama_produk']);
    $harga = intval($_POST['harga']);
    $stok = intval($_POST['stok']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    
    // Upload gambar
    $gambar = $_FILES['gambar']['name'];
    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($_FILES['gambar']['name']);
    move_uploaded_file($_FILES['gambar']['tmp_name'], $target_file);
    
    $sql = "INSERT INTO products (nama_produk, harga, stok, gambar, deskripsi) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdiss", $nama_produk, $harga, $stok, $gambar, $deskripsi);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Produk berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
    header("Location: index.php");
    exit;
}

// Update stok
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stok'])) {
    $product_id = intval($_POST['product_id']);
    $new_stock = intval($_POST['new_stock']);
    
    $stmt = $conn->prepare("UPDATE products SET stok = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_stock, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Stok berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shield-lock"></i> Admin Panel
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-shop"></i> Lihat Toko
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="bi bi-plus-circle"></i> Tambah Produk Baru</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Nama Produk</label>
                                <input type="text" name="nama_produk" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Harga</label>
                                <input type="number" name="harga" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stok Awal</label>
                                <input type="number" name="stok" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Deskripsi</label>
                                <textarea name="deskripsi" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gambar Produk</label>
                                <input type="file" name="gambar" class="form-control" required>
                            </div>
                            <button type="submit" name="tambah_produk" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan Produk
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="bi bi-arrow-repeat"></i> Update Stok Produk</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Pilih Produk</label>
                                <select name="product_id" class="form-select" required>
                                    <?php
                                    $sql = "SELECT id, nama_produk FROM products ORDER BY nama_produk";
                                    $result = $conn->query($sql);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id']}'>{$row['nama_produk']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jumlah Stok Baru</label>
                                <input type="number" name="new_stock" class="form-control" min="0" required>
                            </div>
                            <button type="submit" name="update_stok" class="btn btn-warning">
                                <i class="bi bi-arrow-repeat"></i> Update Stok
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4><i class="bi bi-list-ul"></i> Daftar Produk</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Gambar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM products ORDER BY id DESC";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>{$row['id']}</td>";
                                echo "<td>{$row['nama_produk']}</td>";
                                echo "<td>Rp " . number_format($row['harga'], 0, ',', '.') . "</td>";
                                
                                // Tampilan stok dengan warna berbeda
                                if ($row['stok'] > 10) {
                                    echo "<td class='text-success fw-bold'>{$row['stok']}</td>";
                                } elseif ($row['stok'] > 0) {
                                    echo "<td class='text-warning fw-bold'>{$row['stok']} (Hampir Habis)</td>";
                                } else {
                                    echo "<td class='text-danger fw-bold'>Habis</td>";
                                }
                                
                                echo "<td><img src='../uploads/{$row['gambar']}' width='50'></td>";
                                echo "<td>
                                    <a href='edit_product.php?id={$row['id']}' class='btn btn-sm btn-info'><i class='bi bi-pencil'></i></a>
                                    <a href='delete_product.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Yakin ingin menghapus?\")'>
                                        <i class='bi bi-trash'></i>
                                    </a>
                                </td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>