<?php
session_start();
include "../config/koneksi.php";

// Proteksi admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

// Query laporan
$query = mysqli_query($conn, "
    SELECT *
    FROM laporan_pemesanan
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pemesanan</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

</head>

<body class="bg-slate-100">

<div class="flex">

    <!-- Sidebar -->
   <?php include 'layout/sidebar.php'; ?>

    <!-- Main -->
    <main class="flex-1 p-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">

            <div>
                <h1 class="text-3xl font-bold text-slate-800">
                    Laporan Pemesanan
                </h1>

                <p class="text-slate-500 mt-1">
                    Data laporan transaksi tiket kereta RailTick
                </p>
            </div>

            <!-- Export -->
            <a href="report_export.php"
               target="_blank"
               class="bg-green-500 hover:bg-green-600 transition text-white px-5 py-3 rounded-xl shadow-lg shadow-green-500/20 flex items-center gap-3">

                <i class="fas fa-file-excel"></i>

                Export Excel
            </a>

        </div>

        <!-- Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            <!-- Top -->
            <div class="p-6 border-b border-slate-200 flex items-center justify-between">

                <div>
                    <h2 class="text-lg font-semibold text-slate-800">
                        Data Laporan
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Seluruh data transaksi pemesanan tiket
                    </p>
                </div>

                <div class="bg-slate-100 px-4 py-2 rounded-xl text-sm text-slate-600">
                    Total:
                    <span class="font-semibold">
                        <?php echo mysqli_num_rows($query); ?>
                    </span>
                    Data
                </div>

            </div>

            <!-- Table -->
            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead class="bg-slate-900 text-white">

                        <tr>

                            <th class="px-6 py-4 text-left">No</th>

                            <th class="px-6 py-4 text-left">
                                ID Pesanan
                            </th>

                            <th class="px-6 py-4 text-left">
                                Pemesan
                            </th>

                            <th class="px-6 py-4 text-left">
                                Kereta
                            </th>

                            <th class="px-6 py-4 text-left">
                                Jadwal
                            </th>

                            <th class="px-6 py-4 text-left">
                                Harga/Kursi
                            </th>

                            <th class="px-6 py-4 text-left">
                                Total Harga
                            </th>

                            <th class="px-6 py-4 text-left">
                                Dibuat
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-200 bg-white">

                    <?php
                    $no = 1;

                    while($row = mysqli_fetch_assoc($query)) {

                        $jadwal =
                            date('d M Y', strtotime($row['tanggal_berangkat'])) .
                            " (" .
                            date('H:i', strtotime($row['jam_berangkat'])) .
                            " - " .
                            date('H:i', strtotime($row['jam_tiba'])) .
                            ")";
                    ?>

                        <tr class="hover:bg-slate-50 transition">

                            <td class="px-6 py-4 font-medium text-slate-700">
                                <?php echo $no++; ?>
                            </td>

                            <td class="px-6 py-4">
                                <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-xs font-semibold">
                                    <?php echo htmlspecialchars($row['nomor_laporan']); ?>
                                </span>
                            </td>

                            <td class="px-6 py-4">

                                <div class="font-semibold text-slate-700">
                                    ID:
                                    <?php echo $row['id_user']; ?>
                                </div>

                                <div class="text-slate-500 text-xs mt-1">
                                    <?php echo htmlspecialchars($row['nama_pemesan']); ?>
                                </div>

                            </td>

                            <td class="px-6 py-4">

                                <div class="font-semibold text-slate-700">
                                    ID:
                                    <?php echo $row['id_schedule']; ?>
                                </div>

                                <div class="text-slate-500 text-xs mt-1">
                                    <?php echo htmlspecialchars($row['nama_kereta']); ?>
                                </div>

                            </td>

                            <td class="px-6 py-4 text-slate-600">
                                <?php echo $jadwal; ?>
                            </td>

                            <td class="px-6 py-4 font-semibold text-slate-700">
                                Rp <?php echo number_format($row['harga_perkursi'], 0, ',', '.'); ?>
                            </td>

                            <td class="px-6 py-4">

                                <span class="bg-green-100 text-green-700 px-3 py-2 rounded-lg text-xs font-bold">
                                    Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?>
                                </span>

                            </td>

                            <td class="px-6 py-4 text-slate-500 text-sm">
                                <?php echo date('d M Y H:i', strtotime($row['created_at'])); ?>
                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

</body>
</html>