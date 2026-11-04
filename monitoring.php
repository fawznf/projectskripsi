<?php
include "koneksi.php";

// =======================
// FILTER TANGGAL
// =======================
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : '';

// =======================
// PAGINATION
// =======================
$limit = 20; // jumlah baris per halaman
$page  = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Bangun kondisi WHERE jika ada filter tanggal
$where = "";
if (!empty($tanggal)) {
    $tanggalEsc = mysqli_real_escape_string($conn, $tanggal);
    $where = "WHERE DATE(waktu) = '$tanggalEsc'";
}

// Hitung total data (untuk info & total halaman)
$totalResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM data_kebisingan $where");
$totalRow    = mysqli_fetch_assoc($totalResult);
$totalData   = (int) $totalRow['total'];
$totalPage   = max(1, ceil($totalData / $limit));

if ($page > $totalPage) $page = $totalPage;
$offset = ($page - 1) * $limit;

// Ambil data sesuai filter + pagination
$data = mysqli_query($conn,
    "SELECT * FROM data_kebisingan
     $where
     ORDER BY id DESC
     LIMIT $limit OFFSET $offset");

// Nomor urut mengikuti posisi data (bukan mulai dari 1 tiap halaman)
$no = $totalData - $offset;

// Fungsi bantu untuk mempertahankan query string saat ganti halaman
function buildQuery($params = []) {
    $current = $_GET;
    foreach ($params as $key => $value) {
        if ($value === null) {
            unset($current[$key]);
        } else {
            $current[$key] = $value;
        }
    }
    return '?' . http_build_query($current);
}

// =======================
// Bangun daftar nomor halaman yang ditampilkan (dengan ... jika banyak)
// Contoh hasil: [1, '...', 4, 5, 6, 7, 8, '...', 99]
// =======================
function buildPageNumbers($current, $total, $delta = 2) {
    $range = [];
    $pages = [];

    for ($i = max(1, $current - $delta); $i <= min($total, $current + $delta); $i++) {
        $range[] = $i;
    }

    if (!in_array(1, $range)) {
        $pages[] = 1;
        if ($range[0] > 2) $pages[] = '...';
    }

    foreach ($range as $r) $pages[] = $r;

    if (!in_array($total, $range)) {
        if (end($range) < $total - 1) $pages[] = '...';
        $pages[] = $total;
    }

    return $pages;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta http-equiv="refresh" content="5">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pemantauan Tingkat Kebisingan</title>

<style>
    :root {
        --primary: #1565C0;
        --bg: #f2f4f7;
        --card-bg: #ffffff;
        --text-muted: #6b7280;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: var(--bg);
        margin: 0;
        padding: 24px 16px 48px;
        color: #1f2937;
    }

    h2 {
        text-align: center;
        color: var(--primary);
        margin-bottom: 28px;
        font-size: 22px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .container {
        max-width: 950px;
        margin: 0 auto;
    }

    .card {
        background: var(--card-bg);
        padding: 20px 24px;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }

    /* ===== Filter bar ===== */
    .filter-bar {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .filter-bar .field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .filter-bar label {
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 600;
    }

    .filter-bar input[type="date"] {
        padding: 8px 10px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }

    .btn {
        padding: 9px 18px;
        border-radius: 8px;
        border: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: var(--primary);
        color: #fff;
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #374151;
    }

    .info-text {
        margin-top: 12px;
        font-size: 13.5px;
        color: var(--text-muted);
    }

    /* ===== Tabel ===== */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        border-bottom: 1px solid #eee;
        padding: 10px;
        text-align: center;
        font-size: 14px;
    }

    th {
        background: var(--primary);
        color: #fff;
        font-weight: 600;
    }

    tr:nth-child(even) { background: #f8fafc; }
    tr:hover { background: #eef4ff; }

    .empty-state {
        text-align: center;
        padding: 30px;
        color: var(--text-muted);
    }

    /* ===== Pagination (kotak angka) ===== */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        margin-top: 18px;
        flex-wrap: wrap;
    }

    .pagination a, .pagination span {
        min-width: 38px;
        height: 38px;
        padding: 0 6px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .pagination a.page-num, .pagination span.page-num {
        background: #eef2ff;
        color: var(--primary);
    }

    .pagination a.page-num:hover {
        background: #dbe6ff;
    }

    .pagination .current-page {
        background: var(--primary);
        color: #fff;
    }

    .pagination .ellipsis {
        color: var(--text-muted);
        background: transparent;
        cursor: default;
    }

    .pagination .nav-btn {
        background: #eef2ff;
        color: var(--primary);
        padding: 0 14px;
    }

    .pagination .nav-btn:hover {
        background: #dbe6ff;
    }

    .pagination .disabled {
        background: #f3f4f6;
        color: #b0b6bf;
        pointer-events: none;
    }

    @media (max-width: 600px) {
        .card { padding: 16px; }
        .filter-bar { flex-direction: column; align-items: stretch; }
        .pagination a, .pagination span {
            min-width: 32px;
            height: 32px;
            font-size: 13px;
        }
    }
</style>
</head>
<body>

<h2>Pemantauan Tingkat Kebisingan Perpustakaan Universitas Muhammadiyah Indonesia Bekasi</h2>

<div class="container">

    <!-- Filter -->
    <div class="card">
        <form class="filter-bar" method="get">
            <div class="field">
                <label for="tanggal">Filter Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" value="<?php echo htmlspecialchars($tanggal); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
            <?php if (!empty($tanggal)): ?>
                <a href="monitoring.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>

        <div class="info-text">
            <?php if (!empty($tanggal)): ?>
                Menampilkan data tanggal <b><?php echo date('d-m-Y', strtotime($tanggal)); ?></b>
                — total <b><?php echo number_format($totalData); ?></b> data.
            <?php else: ?>
                Menampilkan seluruh data — total <b><?php echo number_format($totalData); ?></b> data.
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <table>
            <tr>
                <th>No</th>
                <th>Nilai dB</th>
                <th>Status</th>
                <th>Waktu</th>
            </tr>

            <?php if ($totalData === 0): ?>
                <tr>
                    <td colspan="4" class="empty-state">Tidak ada data untuk ditampilkan.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?php echo $no--; ?></td>
                    <td><?php echo $row['nilai_db']; ?></td>
                    <td>
                        <?php
                        if ($row['status'] == "Sunyi") {
                            echo "<span style='color:green;font-weight:bold;'>🟢 Sunyi</span>";
                        } elseif ($row['status'] == "Kondusif") {
                            echo "<span style='color:orange;font-weight:bold;'>🟡 Kondusif</span>";
                        } elseif ($row['status'] == "Bising") {
                            echo "<span style='color:red;font-weight:bold;'>🔴 Bising</span>";
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td><?php echo $row['waktu']; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </table>

        <!-- Pagination -->
        <?php if ($totalPage > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="nav-btn" href="<?php echo buildQuery(['page' => $page - 1]); ?>">‹</a>
            <?php else: ?>
                <span class="nav-btn disabled">‹</span>
            <?php endif; ?>

            <?php foreach (buildPageNumbers($page, $totalPage) as $p): ?>
                <?php if ($p === '...'): ?>
                    <span class="ellipsis">...</span>
                <?php elseif ($p == $page): ?>
                    <span class="page-num current-page"><?php echo $p; ?></span>
                <?php else: ?>
                    <a class="page-num" href="<?php echo buildQuery(['page' => $p]); ?>"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($page < $totalPage): ?>
                <a class="nav-btn" href="<?php echo buildQuery(['page' => $page + 1]); ?>">›</a>
            <?php else: ?>
                <span class="nav-btn disabled">›</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>