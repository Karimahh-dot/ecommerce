<?php
session_start();

if (!isset($_SESSION['order_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = $_SESSION['order_id'];
unset($_SESSION['order_id']);

// Jika ingin menampilkan detail pesanan, bisa query database di sini
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3>Pesanan Berhasil!</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="green" class="bi bi-check-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                            </svg>
                        </div>
                        <h4 class="mb-3">Terima kasih atas pesanan Anda!</h4>
                        <p class="mb-4">Nomor pesanan Anda adalah: <strong>#<?= $order_id ?></strong></p>
                        <p>Kami telah mengirimkan detail pesanan ke email Anda.</p>
                        <a href="index.php" class="btn btn-primary">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>