<?php
session_start();
include 'db.php';

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}


// Hitung total item dan jumlah produk di keranjang
$cart_count = 0;
$cart_total = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $item) {
        $cart_count += $item['jumlah'];
        $cart_total += $item['harga'] * $item['jumlah'];
    }
}

// Fungsi pencarian produk
$search = '';
$where = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $where = "WHERE nama_produk LIKE '%$search%' OR deskripsi LIKE '%$search%'";
}

// Ambil produk dari database
$sql = "SELECT * FROM products $where ORDER BY nama_produk ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Online - Belanja Online Terbaik</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Toko Online
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="d-flex w-100">
                    <form class="d-flex mx-3 flex-grow-1" action="index.php" method="GET">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-light" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a href="cart.php" class="nav-link position-relative">
                                <i class="bi bi-cart3"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $cart_count ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <a href="admin/login.php" class="nav-link">
                                <i class="bi bi-person-lock"></i> Admin
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="bg-light py-5 mb-4">
        <div class="container text-center">
            <h1 class="display-5 fw-bold">Selamat Datang di Toko Online Kami</h1>
            <p class="lead">Temukan produk terbaik dengan harga terbaik</p>
        </div>
    </div>

    <!-- Daftar Produk -->
    <div class="container mb-5">
        <h2 class="mb-4">Produk Terbaru</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php while ($row = $result->fetch_assoc()): ?>
    <div class="col">
        <div class="card h-100 shadow-sm">
            <img src="uploads/<?= htmlspecialchars($row['gambar']) ?>" class="card-img-top p-3" alt="<?= htmlspecialchars($row['nama_produk']) ?>" style="height: 200px; object-fit: contain;">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($row['nama_produk']) ?></h5>
                <p class="card-text text-muted">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                
                <!-- Tampilan stok dengan kondisi -->
                <?php if ($row['stok'] > 10): ?>
                    <p class="stok-ada"><i class="bi bi-check-circle"></i> Stok: <?= $row['stok'] ?></p>
                <?php elseif ($row['stok'] > 0): ?>
                    <p class="stok-sedikit"><i class="bi bi-exclamation-triangle"></i> Stok: <?= $row['stok'] ?> (Hampir Habis!)</p>
                <?php else: ?>
                    <p class="stok-habis"><i class="bi bi-x-circle"></i> Stok Habis</p>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <?php if ($row['stok'] > 0): ?>
                    <a href="cart.php?id=<?= $row['id'] ?>" class="btn btn-primary w-100">
                        <i class="bi bi-cart-plus"></i> Tambah ke Keranjang
                      </a>
                        <?php else: ?>
                         <button class="btn btn-secondary w-100" disabled>
                         <i class="bi bi-cart-x"></i> Stok Habis
                         </button>
                          <?php if (isset($_SESSION['admin'])): ?>
                        <a href="admin/update_stok.php" class="btn btn-sm btn-warning mt-2 w-100">Update Stok</a>
                    <?php endif; ?>
                 <?php endif; ?>
            </div>
        </div>
    </div>
<?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Tidak ada produk ditemukan.</div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Tentang Kami</h5>
                    <p>Toko Online terpercaya dengan berbagai produk berkualitas.</p>
                </div>
                <div class="col-md-4">
                    <h5>Kontak</h5>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-envelope"></i> email@example.com</li>
                        <li><i class="bi bi-telephone"></i> (021) 12345678</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Ikuti Kami</h5>
                    <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
            <hr>
            <div class="text-center">
                &copy; <?= date('Y') ?> Toko Online. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk menampilkan notifikasi saat produk ditambahkan ke keranjang
        document.querySelectorAll('.btn-add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productName = this.closest('.card').querySelector('.card-title').innerText;
                
                // Tampilkan toast notification
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.innerHTML = `
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <strong class="me-auto">Toko Online</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            ${productName} telah ditambahkan ke keranjang!
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                
                // Redirect setelah 2 detik
                setTimeout(() => {
                    window.location.href = this.href;
                }, 2000);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>