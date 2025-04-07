<?php
session_start();

// Koneksi ke database
require_once 'db.php';

// Redirect jika keranjang kosong
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = 'Keranjang belanja kosong!';
    header('Location: cart.php');
    exit;
}

// Fungsi sanitasi input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Proses checkout
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Akses tidak sah!');
    }

    // Sanitasi input
    $nama_pelanggan = $conn->real_escape_string(clean_input($_POST['nama']));
    $email = filter_var(clean_input($_POST['email']), FILTER_VALIDATE_EMAIL);
    $telepon = preg_replace('/[^0-9]/', '', clean_input($_POST['telepon']));
    $alamat = $conn->real_escape_string(clean_input($_POST['alamat']));
    $provinsi = $conn->real_escape_string(clean_input($_POST['provinsi']));
    $kota = $conn->real_escape_string(clean_input($_POST['kota']));
    $kecamatan = $conn->real_escape_string(clean_input($_POST['kecamatan']));
    $kode_pos = $conn->real_escape_string(clean_input($_POST['kode_pos']));
    $metode_pembayaran = in_array($_POST['metode_pembayaran'], ['transfer', 'cod', 'e-wallet']) 
                    ? $conn->real_escape_string($_POST['metode_pembayaran']) 
                    : 'transfer';

    $bank_tujuan = '';
    $nomor_rekening = '';
    $nama_pemilik_rekening = '';

    if ($metode_pembayaran === 'transfer') {
        $bank_tujuan = $conn->real_escape_string(clean_input($_POST['bank_tujuan']));
        $nomor_rekening = $conn->real_escape_string(clean_input($_POST['nomor_rekening']));
        $nama_pemilik_rekening = $conn->real_escape_string(clean_input($_POST['nama_pemilik_rekening']));
        
        if (empty($bank_tujuan) || empty($nomor_rekening) || empty($nama_pemilik_rekening)) {
            $_SESSION['error'] = 'Harap lengkapi detail pembayaran transfer bank!';
            header('Location: checkout.php');
            exit;
        }
    }
    // Validasi input
    if (empty($nama_pelanggan) || !$email || strlen($telepon) < 10 || empty($alamat)) {
        $_SESSION['error'] = 'Harap isi semua field dengan benar!';
        header('Location: checkout.php');
        exit;
    }

    // Mulai transaksi
    $conn->begin_transaction();

    try {
        $total_harga = 0;
        $items = [];

        // Hitung total dan validasi stok
        foreach ($_SESSION['cart'] as $id => $item) {
            $id_produk = intval($id);
            $jumlah = intval($item['jumlah']);
            
            $stmt = $conn->prepare("SELECT id, harga, stok FROM products WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $id_produk);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if ($row['stok'] < $jumlah) {
                    throw new Exception("Stok produk {$item['nama_produk']} tidak mencukupi");
                }
                
                $total_harga += $row['harga'] * $jumlah;
                $items[] = [
                    'product_id' => $id_produk,
                    'quantity' => $jumlah,
                    'price' => $row['harga'],
                    'name' => $item['nama_produk']
                ];
            } else {
                throw new Exception("Produk tidak ditemukan");
            }
        }

        // Tambahkan ongkos kirim
        $ongkir = 15000;
        $total_harga += $ongkir;

        // Gabungkan alamat lengkap
        $alamat_lengkap = "$alamat, Kec. $kecamatan, $kota, $provinsi, $kode_pos";

        // Simpan pesanan
        $stmt = $conn->prepare("INSERT INTO orders (nama_pelanggan, email, telepon, alamat, total, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssd", $nama_pelanggan, $email, $telepon, $alamat_lengkap, $total_harga);
        $stmt->execute();
        $order_id = $conn->insert_id;

            // Di bagian simpan pembayaran, modifikasi menjadi:
        $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, bank_tujuan, nomor_rekening, nama_pemilik_rekening, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issssd", $order_id, $metode_pembayaran, $bank_tujuan, $nomor_rekening, $nama_pemilik_rekening, $total_harga);
            // Simpan detail pesanan dan update stok
        foreach ($items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();
            
            $stmt = $conn->prepare("UPDATE products SET stok = stok - ? WHERE id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
        }

        // Commit transaksi
        $conn->commit();

        // Kosongkan keranjang
        unset($_SESSION['cart']);
        $_SESSION['order_id'] = $order_id;
        $_SESSION['success'] = 'Pesanan berhasil! Nomor pesanan: #'.$order_id;

        // Kirim email konfirmasi
        // send_confirmation_email($email, $order_id, $nama_pelanggan, $total_harga);

        header('Location: order_confirmation.php');
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header('Location: checkout.php');
        exit;
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Toko Online</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">Checkout</h1>
            <a href="cart.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-fill"></i> Informasi Pelanggan</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="checkoutForm">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telepon" class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" id="telepon" name="telepon" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat Jalan</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="provinsi" class="form-label">Provinsi</label>
                                    <input type="text" class="form-control" id="provinsi" name="provinsi" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kota" class="form-label">Kota/Kabupaten</label>
                                    <input type="text" class="form-control" id="kota" name="kota" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="kecamatan" class="form-label">Kecamatan</label>
                                    <input type="text" class="form-control" id="kecamatan" name="kecamatan" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kode_pos" class="form-label">Kode Pos</label>
                                    <input type="text" class="form-control" id="kode_pos" name="kode_pos" required>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-credit-card-fill"></i> Pembayaran</h4>
                    </div>
                    <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="metode_pembayaran" id="transfer" value="transfer" checked>
                            <label class="form-check-label" for="transfer">
                                <i class="bi bi-bank"></i> Transfer Bank
                            </label>
                        </div>
                        <div id="bankDetails" class="mb-3 ps-4">
                            <div class="mb-2">
                                <label class="form-label">Bank Tujuan</label>
                                <select class="form-select" name="bank_tujuan">
                                    <option value="BCA">BCA - 1234567890 (PT Toko Online)</option>
                                    <option value="Mandiri">Mandiri - 9876543210 (PT Toko Online)</option>
                                    <option value="BNI">BNI - 5678901234 (PT Toko Online)</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Nomor Rekening Pengirim</label>
                                <input type="text" class="form-control" name="nomor_rekening" placeholder="Masukkan nomor rekening Anda">
                            </div>
                            <div>
                                <label class="form-label">Nama Pemilik Rekening</label>
                                <input type="text" class="form-control" name="nama_pemilik_rekening" placeholder="Nama sesuai rekening">
                            </div>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="metode_pembayaran" id="cod" value="cod">
                            <label class="form-check-label" for="cod">
                                <i class="bi bi-cash"></i> Cash on Delivery (COD)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metode_pembayaran" id="ewallet" value="e-wallet">
                            <label class="form-check-label" for="ewallet">
                                <i class="bi bi-wallet2"></i> E-Wallet
                            </label>
                        </div>
                    </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-cart-check-fill"></i> Ringkasan Pesanan</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-3">
                            <?php 
                            $total = 0;
                            foreach ($_SESSION['cart'] as $id => $item): 
                                $subtotal = $item['harga'] * $item['jumlah'];
                                $total += $subtotal;
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($item['nama_produk']) ?></h6>
                                    <small class="text-muted"><?= $item['jumlah'] ?> × Rp <?= number_format($item['harga'], 0, ',', '.') ?></small>
                                </div>
                                <span class="fw-bold">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                            </li>
                            <?php endforeach; ?>
                            
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Subtotal</span>
                                <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Ongkos Kirim</span>
                                <span>Rp 15.000</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between fw-bold fs-5">
                                <span>Total Pembayaran</span>
                                <span>Rp <?= number_format($total + 15000, 0, ',', '.') ?></span>
                            </li>
                        </ul>
                        
                        <button type="submit" form="checkoutForm" class="btn btn-primary btn-lg w-100 py-3">
                            <i class="bi bi-lock-fill"></i> Bayar Sekarang
                        </button>
                        
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Syarat dan Ketentuan</a>
                            </label>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Syarat dan Ketentuan -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Syarat dan Ketentuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Kebijakan Pembayaran</h6>
                    <p>Pembayaran harus dilakukan sesuai dengan metode yang dipilih. Untuk pembayaran transfer, harap melakukan transfer dalam waktu 24 jam setelah pemesanan.</p>
                    
                    <h6>2. Pengiriman</h6>
                    <p>Pengiriman akan dilakukan setelah pembayaran dikonfirmasi. Waktu pengiriman tergantung pada lokasi tujuan.</p>
                    
                    <h6>3. Retur dan Pengembalian</h6>
                    <p>Produk dapat dikembalikan dalam waktu 7 hari setelah diterima dengan syarat produk masih dalam kondisi baru dan tagihan asli dilampirkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Saya Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validasi sebelum submit
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        if (!document.getElementById('agreeTerms').checked) {
            e.preventDefault();
            alert('Anda harus menyetujui syarat dan ketentuan');
        }
    });

    // Animasi untuk tombol bayar
    const payButton = document.querySelector('button[type="submit"]');
    payButton.addEventListener('mouseover', () => {
        payButton.classList.add('shadow');
    });
    payButton.addEventListener('mouseout', () => {
        payButton.classList.remove('shadow');
    });

    // Tampilkan/sembunyikan detail bank berdasarkan metode pembayaran
document.querySelectorAll('input[name="metode_pembayaran"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const bankDetails = document.getElementById('bankDetails');
        bankDetails.style.display = this.value === 'transfer' ? 'block' : 'none';
    });
});

// Sembunyikan detail bank jika metode bukan transfer saat pertama kali load
document.addEventListener('DOMContentLoaded', function() {
    const selectedMethod = document.querySelector('input[name="metode_pembayaran"]:checked').value;
    document.getElementById('bankDetails').style.display = selectedMethod === 'transfer' ? 'block' : 'none';
});
    </script>
</body>
</html>
