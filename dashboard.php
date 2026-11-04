<?php
include "koneksi.php";

// =======================
// DATA TERBARU
// =======================
$latest = mysqli_query($conn, "SELECT * FROM data_kebisingan ORDER BY id DESC LIMIT 1");
$dataLatest = mysqli_fetch_assoc($latest);

$nilai_db  = $dataLatest['nilai_db'];
$status_db = $dataLatest['status'];

if ($status_db == "Sunyi") {
    $status = "Sunyi";
    $statusClass = "status-sunyi";
    $statusIcon = "🟢";
} elseif ($status_db == "Kondusif") {
    $status = "Kondusif";
    $statusClass = "status-kondusif";
    $statusIcon = "🟡";
} else {
    $status = "Bising";
    $statusClass = "status-bising";
    $statusIcon = "🔴";
}

// =======================
// DATA GRAFIK
// =======================
$grafik = mysqli_query($conn, "SELECT * FROM data_kebisingan ORDER BY id DESC LIMIT 20");

$labels = [];
$values = [];

while ($row = mysqli_fetch_assoc($grafik)) {
    $labels[] = $row['waktu'];
    $values[] = $row['nilai_db'];
}

$labels = array_reverse($labels);
$values = array_reverse($values);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta http-equiv="refresh" content="5">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Pemantauan Tingkat Kebisingan</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --primary: #1565C0;
        --primary-light: #1976D2;
        --bg: #f2f4f7;
        --card-bg: #ffffff;
        --text-muted: #6b7280;
        --sunyi: #2e7d32;
        --kondusif: #f9a825;
        --bising: #c62828;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: var(--bg);
        margin: 0;
        padding: 24px 16px 48px;
        color: #1f2937;
    }

    h1 {
        text-align: center;
        color: var(--primary);
        margin-bottom: 32px;
        font-size: 26px;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .card {
        background: var(--card-bg);
        padding: 24px;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    }

    .card h2 {
        margin-top: 0;
        margin-bottom: 16px;
        font-size: 18px;
        color: #374151;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    /* ===== Kartu nilai terbaru ===== */
    .nilai-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }

    .nilai {
        font-size: 46px;
        font-weight: 700;
        color: var(--primary);
        line-height: 1;
    }

    .nilai small {
        font-size: 18px;
        font-weight: 400;
        color: var(--text-muted);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 18px;
        font-weight: 600;
        color: #fff;
    }

    .status-sunyi     { background: var(--sunyi); }
    .status-kondusif   { background: var(--kondusif); color:#3a2b00; }
    .status-bising     { background: var(--bising); }

    /* ===== Grafik ===== */
    .chart-container {
        position: relative;
        height: 340px;
    }

    /* ===== Tabel ===== */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    table th {
        background: var(--primary);
        color: white;
        padding: 12px 10px;
        font-weight: 600;
    }

    table td {
        border-bottom: 1px solid #eee;
        padding: 10px;
        text-align: center;
    }

    table tr:nth-child(even) {
        background: #f8fafc;
    }

    table tr:hover {
        background: #eef4ff;
    }

    @media (max-width: 600px) {
        .nilai { font-size: 36px; }
        .card { padding: 18px; }
    }
</style>
</head>
<body>

<h1>📊 Dashboard Pemantauan Tingkat Kebisingan</h1>

<div class="container">

    <!-- Nilai Terbaru -->
    <div class="card">
        <h2>Nilai Kebisingan Terbaru</h2>
        <div class="nilai-wrapper">
            <div class="nilai"><?php echo $nilai_db; ?> <small>dB</small></div>
            <div class="status-badge <?php echo $statusClass; ?>">
                <?php echo $statusIcon . " " . $status; ?>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="card">
        <h2>Grafik Kebisingan</h2>
        <div class="chart-container">
            <canvas id="myChart"></canvas>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <h2>Tabel Pemantauan Terakhir</h2>
        <table>
            <tr>
                <th>No</th>
                <th>Nilai dB</th>
                <th>Status</th>
                <th>Waktu</th>
            </tr>
            <?php
            $no = 1;
            $data = mysqli_query($conn, "SELECT * FROM data_kebisingan ORDER BY id DESC LIMIT 10");

            while ($row = mysqli_fetch_assoc($data)) {
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $row['nilai_db']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td><?php echo $row['waktu']; ?></td>
            </tr>
            <?php } ?>
        </table>
    </div>

</div>

<script>
const ctx = document.getElementById('myChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Tingkat Kebisingan (dB)',
            data: <?php echo json_encode($values); ?>,
            borderColor: '#1976D2',
            backgroundColor: 'rgba(25,118,210,0.15)',
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                title: {
                    display: true,
                    text: 'Desibel (dB)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Waktu'
                }
            }
        }
    }
});
</script>

</body>
</html>