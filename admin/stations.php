<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

if (isset($_POST['tambah'])) {
    $kode = strtoupper(mysqli_real_escape_string($conn, $_POST['kode_station']));
    $nama = mysqli_real_escape_string($conn, $_POST['nama_station']);
    $kota = mysqli_real_escape_string($conn, $_POST['kota']);

    $cek = mysqli_query($conn, "SELECT id_station FROM stations WHERE kode_station='$kode' LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        $message = "Kode stasiun sudah digunakan.";
    } else {
        mysqli_query($conn, "INSERT INTO stations (kode_station, nama_station, kota, status_station) VALUES ('$kode', '$nama', '$kota', 'aktif')");
        header("Location: stations.php?status=added");
        exit();
    }
}

if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    mysqli_query($conn, "UPDATE stations SET status_station = IF(status_station='aktif','nonaktif','aktif') WHERE id_station='$id'");
    header("Location: stations.php");
    exit();
}

$stations = mysqli_query($conn, "SELECT * FROM stations ORDER BY status_station ASC, nama_station ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stasiun - RailTick Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap'); body{font-family:'Poppins',sans-serif}</style>
</head>
<body class="bg-gray-50 flex">
<?php include "layout/sidebar.php"; ?>
<main class="flex-1 min-h-screen">
    <header class="h-20 bg-white border-b border-slate-800/10 flex items-center justify-between px-8 sticky top-0 z-30">
        <h1 class="text-xl font-bold text-gray-800">Master Stasiun</h1>
        <div class="text-right hidden sm:block">
            <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Administrator'); ?></p>
            <p class="text-[10px] text-green-500 font-black uppercase tracking-widest">Admin Online</p>
        </div>
    </header>
    <div class="p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        <section class="bg-white p-8 rounded-[2rem] border border-gray-100 shadow-sm h-fit">
            <h2 class="font-bold text-gray-800 mb-6">Tambah Stasiun</h2>
            <?php if ($message): ?>
                <div class="mb-5 bg-red-50 text-red-600 p-4 rounded-xl text-sm font-semibold"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="tambah" value="1">
                <input name="kode_station" maxlength="10" placeholder="Kode, contoh: GMR" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <input name="nama_station" placeholder="Nama stasiun" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <input name="kota" placeholder="Kota" required class="w-full px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 outline-none focus:ring-2 focus:ring-red-500">
                <button class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-xl uppercase text-xs tracking-widest">Simpan Stasiun</button>
            </form>
        </section>
        <section class="lg:col-span-2 bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">Daftar Stasiun</h2>
                <span class="text-[10px] font-black uppercase bg-blue-50 text-blue-600 px-4 py-1.5 rounded-full"><?php echo mysqli_num_rows($stations); ?> data</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-400 text-[10px] uppercase tracking-widest">
                    <tr><th class="px-6 py-4">Kode</th><th class="px-6 py-4">Stasiun</th><th class="px-6 py-4">Kota</th><th class="px-6 py-4">Status</th><th class="px-6 py-4 text-center">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                    <?php while ($row = mysqli_fetch_assoc($stations)): ?>
                        <tr class="hover:bg-gray-50/60">
                            <td class="px-6 py-4 font-black text-blue-600"><?php echo htmlspecialchars($row['kode_station']); ?></td>
                            <td class="px-6 py-4 font-bold text-gray-800"><?php echo htmlspecialchars($row['nama_station']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($row['kota']); ?></td>
                            <td class="px-6 py-4"><span class="text-[10px] font-black uppercase px-3 py-1 rounded-full <?php echo $row['status_station']=='aktif'?'bg-green-50 text-green-600':'bg-gray-100 text-gray-500'; ?>"><?php echo htmlspecialchars($row['status_station']); ?></span></td>
                            <td class="px-6 py-4 text-center"><a href="?toggle=<?php echo $row['id_station']; ?>" class="text-xs font-bold text-red-500 hover:underline">Ubah Status</a></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
</body>
</html>
