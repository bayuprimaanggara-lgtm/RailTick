<?php
session_start();
include "../config/koneksi.php";
include "../config/operational_status.php";
sync_operational_status($conn);

// 1. Proteksi Halaman User
if (!isset($_SESSION['role']) || $_SESSION['role'] != "user") {
    header("Location: ../auth/login.php");
    exit();
}

// 2. Validasi Parameter URL
if (
    !isset($_GET['id']) || empty($_GET['id']) ||
    !isset($_GET['asal_station_id']) || empty($_GET['asal_station_id']) ||
    !isset($_GET['tujuan_station_id']) || empty($_GET['tujuan_station_id'])
) {
    header("Location: jadwal.php");
    exit();
}

$id_schedule = (int) $_GET['id'];
$asal_station_id = (int) $_GET['asal_station_id'];
$tujuan_station_id = (int) $_GET['tujuan_station_id'];
$id_user = (int) $_SESSION['user_id'];

// 3. Ambil data user terbaru untuk Header agar sinkron otomatis
$query_user_db = mysqli_query($conn, "SELECT nama_lengkap, username FROM users WHERE id_user = '$id_user'");
$user_db = mysqli_fetch_assoc($query_user_db);
$nama_tampil = !empty($user_db['nama_lengkap']) ? $user_db['nama_lengkap'] : $user_db['username'];

// 4. Ambil Detail Jadwal & Rute
$query_detail = mysqli_query($conn, "
    SELECT
        s.id_schedule,
        s.id_route_kai,
        s.nama_kereta,
        sa.id_station AS asal_station_id,
        sa.nama_station AS asal,
        st.id_station AS tujuan_station_id,
        st.nama_station AS tujuan,
        rs_asal.jam_berangkat,
        rs_tujuan.jam_tiba,
        TIMEDIFF(rs_tujuan.jam_tiba, rs_asal.jam_berangkat) AS durasi,
        rf.harga
    FROM schedules s
    JOIN train_routes tr ON tr.id_route_kai = s.id_route_kai
    JOIN route_stations rs_asal ON rs_asal.id_route_kai = tr.id_route_kai
    JOIN route_stations rs_tujuan ON rs_tujuan.id_route_kai = tr.id_route_kai
    JOIN stations sa ON sa.id_station = rs_asal.id_station
    JOIN stations st ON st.id_station = rs_tujuan.id_station
    JOIN route_fares rf
        ON rf.id_route_kai = tr.id_route_kai
       AND rf.asal_station_id = rs_asal.id_station
       AND rf.tujuan_station_id = rs_tujuan.id_station
    WHERE s.id_schedule = '$id_schedule'
      AND sa.id_station = '$asal_station_id'
      AND st.id_station = '$tujuan_station_id'
      AND rs_asal.urutan < rs_tujuan.urutan
    LIMIT 1
");

$detail = mysqli_fetch_assoc($query_detail);

if (!$detail) {
    header("Location: jadwal.php?error=notfound");
    exit();
}

$message = "";
$success_kode = "";

// Ambil Tanggal Terpilih
$tanggal_selected = isset($_POST['tanggal']) && !empty($_POST['tanggal'])
    ? mysqli_real_escape_string($conn, $_POST['tanggal'])
    : (isset($_GET['tanggal']) && !empty($_GET['tanggal'])
        ? mysqli_real_escape_string($conn, $_GET['tanggal'])
        : date('Y-m-d'));

$run_check = mysqli_query($conn, "
    SELECT id_run
    FROM train_runs
    WHERE id_schedule = '$id_schedule'
      AND tanggal_berangkat = '$tanggal_selected'
      AND status_run = 'terjadwal'
    LIMIT 1
");
$is_run_available = mysqli_num_rows($run_check) > 0;

// Ambil Semua Kursi
$query_seats = mysqli_query($conn, "SELECT * FROM seats ORDER BY gerbong ASC, nomor_kursi ASC");
$all_seats = [];
while ($seat = mysqli_fetch_assoc($query_seats)) {
    $all_seats[] = $seat;
}

// Ambil Kursi yang sudah dibooking
$query_booked = mysqli_query($conn, "
    SELECT id_seat
    FROM seat_reservations
    WHERE id_schedule = '$id_schedule'
      AND tanggal_berangkat = '$tanggal_selected'
      AND status_seat = 'booked'
");

$booked_seats = [];
while ($row = mysqli_fetch_assoc($query_booked)) {
    $booked_seats[] = $row['id_seat'];
}

/* --- PROSES BOOKING --- */
if (isset($_POST['pesan'])) {
    $jumlah_tiket   = (int) $_POST['jumlah'];
    $tanggal_pergi  = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $harga_satuan   = (float) $detail['harga'];
    $total_harga    = $jumlah_tiket * $harga_satuan;
    $selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : [];

    if (!is_array($selected_seats)) {
        $selected_seats = [];
    }

    $cek_run_booking = mysqli_query($conn, "
        SELECT id_run
        FROM train_runs
        WHERE id_schedule = '$id_schedule'
          AND tanggal_berangkat = '$tanggal_pergi'
          AND status_run = 'terjadwal'
        LIMIT 1
    ");

    if ($tanggal_pergi < date('Y-m-d')) {
    $message = "Tanggal keberangkatan tidak boleh kurang dari hari ini.";
} elseif (mysqli_num_rows($cek_run_booking) == 0) {
    $message = "Kereta ini belum disetting berangkat pada tanggal tersebut.";
} elseif ($tanggal_pergi == date('Y-m-d') && strtotime($detail['jam_berangkat']) <= strtotime(date('H:i:s'))) {
    $message = "Jadwal keberangkatan untuk hari ini sudah lewat dan tidak bisa dipesan.";
} elseif ($jumlah_tiket < 1) {
        $message = "Jumlah tiket minimal 1.";
    } elseif (count($selected_seats) != $jumlah_tiket) {
        $message = "Jumlah kursi yang dipilih harus sama dengan jumlah tiket.";
    } else {
        $seat_ids_clean = [];
        foreach ($selected_seats as $seat_id) {
            $seat_ids_clean[] = (int) $seat_id;
        }

        $seat_ids_string = implode(",", $seat_ids_clean);

        if (empty($seat_ids_string)) {
            $message = "Silakan pilih kursi terlebih dahulu.";
        } else {
            $cek_double = mysqli_query($conn, "
                SELECT id_seat
                FROM seat_reservations
                WHERE id_schedule = '$id_schedule'
                  AND tanggal_berangkat = '$tanggal_pergi'
                  AND status_seat = 'booked'
                  AND id_seat IN ($seat_ids_string)
            ");

            if (mysqli_num_rows($cek_double) > 0) {
                $message = "Maaf, ada kursi yang baru saja dipesan orang lain. Silakan pilih ulang.";
            } else {
                $kode_booking = "RT-" . strtoupper(substr(md5(time() . $id_user . rand()), 0, 6));

                mysqli_begin_transaction($conn);

                try {
                    $sql_booking = "INSERT INTO bookings (
                                        kode_booking, id_user, id_schedule, asal_station_id, tujuan_station_id, tanggal_berangkat,
                                        jumlah_tiket, harga_satuan, total_harga, status_booking
                                    ) VALUES (
                                        '$kode_booking', '$id_user', '$id_schedule', '{$detail['asal_station_id']}', '{$detail['tujuan_station_id']}', '$tanggal_pergi',
                                        '$jumlah_tiket', '$harga_satuan', '$total_harga', 'berhasil'
                                    )";

                    $query_booking = mysqli_query($conn, $sql_booking);

                    if (!$query_booking) {
                        throw new Exception("Gagal simpan booking");
                    }

                    $id_booking = mysqli_insert_id($conn);

                    foreach ($seat_ids_clean as $seat_id) {
                        $nama_penumpang = isset($_SESSION['nama']) ? mysqli_real_escape_string($conn, $_SESSION['nama']) : 'Penumpang';
                        $nik_penumpang = "00000000";

                        $sql_passenger = "INSERT INTO booking_passengers (
                                            id_booking, nama_penumpang, nik_penumpang, status_tiket
                                          ) VALUES (
                                            '$id_booking', '$nama_penumpang', '$nik_penumpang', 'valid'
                                          )";

                        $query_passenger = mysqli_query($conn, $sql_passenger);

                        if (!$query_passenger) {
                            throw new Exception("Gagal simpan penumpang");
                        }

                        $id_passenger = mysqli_insert_id($conn);

                        $sql_reservation = "INSERT INTO seat_reservations (
                                                id_booking, id_passenger, id_schedule,
                                                tanggal_berangkat, id_seat, status_seat
                                            ) VALUES (
                                                '$id_booking', '$id_passenger', '$id_schedule',
                                                '$tanggal_pergi', '$seat_id', 'booked'
                                            )";

                        $query_reservation = mysqli_query($conn, $sql_reservation);

                        if (!$query_reservation) {
                            throw new Exception("Gagal simpan reservasi kursi");
                        }
                    }

                    mysqli_commit($conn);
                    $success_kode = $kode_booking;

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $message = "Terjadi kesalahan saat menyimpan booking.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Tiket - RailTick</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }

        .seat {
            width: 52px; height: 52px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; cursor: pointer;
            transition: all 0.2s ease; user-select: none; border: 1px solid #e5e7eb;
        }
        .seat.available { background: #dcfce7; color: #166534; }
        .seat.available:hover { transform: scale(1.05); }
        .seat.booked { background: #fee2e2; color: #991b1b; cursor: not-allowed; opacity: 0.9; }
        .seat.selected { background: #dbeafe !important; color: #1d4ed8 !important; border: 2px solid #2563eb; }
        .seat-placeholder { width: 52px; height: 52px; }

        .modal-overlay { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); transition: all 0.3s ease; }
        .modal-card { transform: scale(0.9); opacity: 0; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .modal-active .modal-card { transform: scale(1); opacity: 1; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-100 hidden md:flex flex-col sticky top-0 h-screen shadow-sm no-print">
        <div class="p-8 border-b border-gray-50 flex items-center gap-3">
            <div class="bg-red-500 p-2 rounded-lg text-white shadow-lg shadow-red-200">
                <i class="fas fa-train"></i>
            </div>
            <span class="text-xl font-bold text-gray-800 italic tracking-tight">RailTick</span>
        </div>
        
        <nav class="flex-1 p-6 space-y-3 mt-4">
            <a href="dashboard.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium"><i class="fas fa-home"></i> Beranda</a>
            <a href="jadwal.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium"><i class="fas fa-search"></i> Cari Jadwal</a>
            <a href="riwayat.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium"><i class="fas fa-history"></i> Riwayat Pesanan</a>
            <a href="edit_profil.php" class="flex items-center gap-3 text-gray-500 hover:bg-gray-50 hover:text-red-500 px-4 py-3 rounded-2xl transition font-medium"><i class="fas fa-user-circle"></i> Profil Saya</a>
        </nav>

        <div class="p-6 border-t border-gray-50">
            <button onclick="toggleLogoutModal(true)" class="w-full flex items-center gap-3 text-gray-400 hover:text-red-500 px-4 py-3 transition text-sm font-bold uppercase tracking-widest outline-none">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </aside>

    <main class="flex-1">
        <!-- Topbar -->
        <header class="bg-white/80 backdrop-blur-md sticky top-0 z-30 px-8 py-5 border-b border-gray-100 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="jadwal.php" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-arrow-left text-lg"></i></a>
                <h1 class="text-sm text-gray-400 font-medium uppercase tracking-wider">Konfirmasi Pemesanan Tiket</h1>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nama_tampil); ?></p>
                    <p class="text-[10px] text-red-500 font-bold uppercase tracking-widest">Penumpang RailTick</p>
                </div>
                <a href="edit_profil.php" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 border border-gray-200 hover:border-red-500 hover:text-red-500 transition shadow-sm group">
                    <i class="fas fa-user group-hover:scale-110 transition-transform"></i>
                </a>
            </div>
        </header>

        <div class="p-8 max-w-6xl mx-auto">
            <?php if ($success_kode != ""): ?>
                <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl border border-green-100 text-center mb-10">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fas fa-check-circle"></i></div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2 uppercase tracking-tight">Pemesanan Berhasil!</h2>
                    <p class="text-gray-500 mb-6 font-medium">Kode Booking Anda: <span class="font-black text-red-600 text-xl tracking-widest ml-2"><?php echo $success_kode; ?></span></p>
                    <div class="flex justify-center gap-4">
                        <a href="riwayat.php" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-bold transition hover:bg-slate-800 uppercase text-xs tracking-widest">Cek Riwayat</a>
                        <a href="dashboard.php" class="bg-gray-100 text-gray-600 px-8 py-3 rounded-xl font-bold transition hover:bg-gray-200 uppercase text-xs tracking-widest">Ke Dashboard</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$is_run_available && $success_kode == ""): ?>
                <div class="bg-yellow-50 text-yellow-700 p-5 rounded-2xl border border-yellow-100 mb-8 font-semibold text-sm">
                    Kereta ini belum disetting berangkat pada tanggal <?php echo date('d M Y', strtotime($tanggal_selected)); ?>. Pilih tanggal lain dari halaman jadwal.
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Summary Detail Perjalanan -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-gray-100 sticky top-28">
                        <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2 uppercase text-xs tracking-wider"><i class="fas fa-info-circle text-blue-500"></i> Detail Perjalanan</h3>
                        <div class="space-y-6">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Nama Kereta</p>
                                <p class="text-lg font-black text-slate-800 uppercase"><?php echo htmlspecialchars($detail['nama_kereta']); ?></p>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-1 bg-blue-500 rounded-full self-stretch"></div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Keberangkatan</p>
                                    <p class="font-bold text-gray-800"><?php echo htmlspecialchars($detail['asal']); ?></p>
                                    <p class="text-xs text-blue-600 font-semibold">Pukul <?php echo date('H:i', strtotime($detail['jam_berangkat'])); ?> WIB</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-1 bg-red-500 rounded-full self-stretch"></div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Kedatangan</p>
                                    <p class="font-bold text-gray-800"><?php echo htmlspecialchars($detail['tujuan']); ?></p>
                                    <p class="text-xs text-red-600 font-semibold">Pukul <?php echo date('H:i', strtotime($detail['jam_tiba'])); ?> WIB</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Durasi Perjalanan</p>
                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($detail['durasi']); ?></p>
                            </div>
                        </div>
                        <div class="mt-10 pt-6 border-t border-dashed border-gray-100">
                            <p class="text-xs text-gray-400 font-medium mb-1 uppercase tracking-widest">Harga Dasar</p>
                            <p class="text-xl font-black text-gray-800">Rp <?php echo number_format($detail['harga'], 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Form Informasi Tiket & Pemilihan Kursi -->
                <div class="lg:col-span-2">
                    <div class="bg-white p-10 rounded-[2.5rem] shadow-sm border border-gray-100">
                        <h3 class="font-bold text-gray-800 text-xl mb-8 tracking-tight">Informasi Reservasi</h3>

                        <form method="POST" id="bookingForm" class="space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-3 ml-1">Tanggal Perjalanan</label>
                                    <div class="relative">
                                        <i class="fas fa-calendar-alt absolute left-4 top-4 text-gray-300"></i>
                                        <input type="date" name="tanggal" id="tanggal" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($tanggal_selected); ?>" class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase mb-3 ml-1">Jumlah Tiket</label>
                                    <div class="relative">
                                        <i class="fas fa-users absolute left-4 top-4 text-gray-300"></i>
                                        <input type="number" name="jumlah" id="jumlah" min="1" max="10" placeholder="Maks. 10" required value="<?php echo isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : ''; ?>" class="w-full pl-12 pr-4 py-4 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-2 focus:ring-red-500 outline-none transition font-medium">
                                    </div>
                                </div>
                            </div>

                            <!-- BOX PILIH KURSI (SAMA PERSIS DENGAN KODINGAN YANG ANDA BERIKAN) -->
                            <div class="bg-gray-50 border border-gray-100 rounded-3xl p-6">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-lg tracking-tight">Pilih Kursi</h4>
                                        <p class="text-sm text-gray-500">Hijau = tersedia, Merah = sudah dipesan, Biru = pilihan kamu</p>
                                    </div>
                                    <div class="flex gap-4 text-xs font-semibold flex-wrap">
                                        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-green-100 border"></span> Tersedia</div>
                                        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-red-100 border"></span> Terisi</div>
                                        <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-blue-100 border border-blue-500"></span> Dipilih</div>
                                    </div>
                                </div>

                                <?php
                                $groupedSeats = [];
                                foreach ($all_seats as $seat) {
                                    $groupedSeats[$seat['gerbong']][] = $seat;
                                }
                                ?>

                                <div class="space-y-8">
                                    <?php foreach ($groupedSeats as $gerbong => $seats): ?>
                                        <div>
                                            <h5 class="font-bold text-gray-700 mb-4">Gerbong <?php echo $gerbong; ?></h5>
                                            <?php
                                            $seatRows = [];
                                            foreach ($seats as $seat) {
                                                $rowLetter = strtoupper(substr($seat['nomor_kursi'], 0, 1));
                                                $seatRows[$rowLetter][] = $seat;
                                            }
                                            ksort($seatRows);
                                            ?>

                                            <div class="space-y-4">
                                                <?php foreach ($seatRows as $rowLetter => $rowSeats): ?>
                                                    <?php
                                                    usort($rowSeats, function($a, $b) {
                                                        $numA = (int) preg_replace('/[^0-9]/', '', $a['nomor_kursi']);
                                                        $numB = (int) preg_replace('/[^0-9]/', '', $b['nomor_kursi']);
                                                        return $numA <=> $numB;
                                                    });

                                                    $leftSeats = array_slice($rowSeats, 0, 2);
                                                    $rightSeats = array_slice($rowSeats, 2, 2);
                                                    ?>

                                                    <div class="flex items-center justify-center gap-4">
                                                        <!-- KIRI -->
                                                        <div class="flex gap-3 w-[125px] justify-start">
                                                            <?php foreach ($leftSeats as $seat): ?>
                                                                <?php
                                                                    $isBooked = in_array($seat['id_seat'], $booked_seats);
                                                                    $seatClass = $isBooked ? 'booked' : 'available';
                                                                ?>
                                                                <div class="seat <?php echo $seatClass; ?>"
                                                                    data-seat-id="<?php echo $seat['id_seat']; ?>"
                                                                    data-seat-label="Gerbong <?php echo $seat['gerbong']; ?> - <?php echo $seat['nomor_kursi']; ?>">
                                                                    <?php echo $seat['nomor_kursi']; ?>
                                                                </div>
                                                            <?php endforeach; ?>

                                                            <?php for ($i = count($leftSeats); $i < 2; $i++): ?>
                                                                <div class="seat-placeholder"></div>
                                                            <?php endfor; ?>
                                                        </div>

                                                        <!-- JALAN -->
                                                        <div class="w-12 flex justify-center">
                                                            <div class="w-3 h-12 rounded-full bg-gray-200"></div>
                                                        </div>

                                                        <!-- KANAN -->
                                                        <div class="flex gap-3 w-[125px] justify-start">
                                                            <?php foreach ($rightSeats as $seat): ?>
                                                                <?php
                                                                    $isBooked = in_array($seat['id_seat'], $booked_seats);
                                                                    $seatClass = $isBooked ? 'booked' : 'available';
                                                                ?>
                                                                <div class="seat <?php echo $seatClass; ?>"
                                                                    data-seat-id="<?php echo $seat['id_seat']; ?>"
                                                                    data-seat-label="Gerbong <?php echo $seat['gerbong']; ?> - <?php echo $seat['nomor_kursi']; ?>">
                                                                    <?php echo $seat['nomor_kursi']; ?>
                                                                </div>
                                                            <?php endforeach; ?>

                                                            <?php for ($i = count($rightSeats); $i < 2; $i++): ?>
                                                                <div class="seat-placeholder"></div>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="mt-6 p-4 bg-white rounded-2xl border border-gray-100">
                                    <p class="text-sm text-gray-500 mb-2">Kursi dipilih:</p>
                                    <div id="selectedSeatLabels" class="font-bold text-gray-800">Belum ada kursi dipilih</div>
                                </div>
                                <div id="selectedSeatInputs"></div>
                            </div>

                            <!-- Footer Payment Card -->
                            <div class="bg-slate-900 p-8 rounded-3xl text-white flex justify-between items-center shadow-xl shadow-slate-200">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-opacity-70">Total Pembayaran</p>
                                    <h4 class="text-3xl font-black tracking-tight" id="total_view">Rp 0</h4>
                                </div>
                                <div class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center text-xl text-green-400"><i class="fas fa-shield-alt"></i></div>
                            </div>

                            <button name="pesan" type="submit" <?php echo !$is_run_available ? 'disabled' : ''; ?> class="w-full <?php echo $is_run_available ? 'bg-red-500 hover:bg-red-600 shadow-red-200' : 'bg-gray-300 cursor-not-allowed shadow-gray-100'; ?> text-white font-black py-5 rounded-2xl shadow-xl transition-all transform active:scale-95 text-sm tracking-[0.2em] uppercase">PESAN SEKARANG <i class="fas fa-ticket-alt"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Logout -->
    <div id="logoutModal" class="modal-overlay fixed inset-0 z-[100] hidden items-center justify-center p-4">
        <div class="modal-card bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl p-8 text-center border border-gray-100">
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl"><i class="fas fa-sign-out-alt"></i></div>
            <h3 class="text-xl font-black text-gray-800 mb-2 uppercase tracking-tight">Yakin ingin Keluar?</h3>
            <p class="text-gray-400 text-sm leading-relaxed mb-8">Sesi pemesanan tiket Anda akan dibatalkan jika Anda keluar sekarang.</p>
            <div class="grid grid-cols-2 gap-4">
                <button onclick="toggleLogoutModal(false)" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-4 rounded-2xl transition uppercase text-[10px] tracking-widest">Batal</button>
                <a href="../auth/logout.php" class="bg-red-500 hover:bg-red-600 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-red-200 uppercase text-[10px] flex items-center justify-center tracking-widest">Ya, Keluar</a>
            </div>
        </div>
    </div>

    <!-- Popup Modal Notifikasi -->
    <div id="customPopup" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/40 backdrop-blur-sm px-4">
        <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-6 relative border border-gray-100">
            <div class="flex items-start gap-4">
                <div id="popupIcon" class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl bg-red-100 text-red-600">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="flex-1">
                    <h3 id="popupTitle" class="text-lg font-bold text-gray-800 mb-1 tracking-tight">Peringatan</h3>
                    <p id="popupMessage" class="text-sm text-gray-500 leading-relaxed">Notifikasi sistem.</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closePopup()" class="bg-slate-900 hover:bg-red-500 text-white px-6 py-2.5 rounded-xl font-bold transition text-xs uppercase tracking-widest">Oke</button>
            </div>
        </div>
    </div>

    <script>
        const inputJumlah = document.getElementById('jumlah');
        const totalView = document.getElementById('total_view');
        const hargaSatuan = <?php echo json_encode((float)$detail['harga']); ?>;
        const seatElements = document.querySelectorAll('.seat.available');
        const selectedSeatLabels = document.getElementById('selectedSeatLabels');
        const selectedSeatInputs = document.getElementById('selectedSeatInputs');
        const tanggalInput = document.getElementById('tanggal');
        
        let selectedSeats = [];

        function toggleLogoutModal(show) {
            const m = document.getElementById('logoutModal');
            if (show) { m.classList.remove('hidden'); m.classList.add('flex'); setTimeout(() => m.classList.add('modal-active'), 10); }
            else { m.classList.remove('modal-active'); setTimeout(() => { m.classList.add('hidden'); m.classList.remove('flex'); }, 300); }
        }

        function showPopup(message, type = 'error', title = 'Peringatan') {
            const popup = document.getElementById('customPopup');
            const popupTitle = document.getElementById('popupTitle');
            const popupMessage = document.getElementById('popupMessage');
            const popupIcon = document.getElementById('popupIcon');
            popupTitle.innerText = title;
            popupMessage.innerText = message;
            if (type === 'warning') {
                popupIcon.className = 'w-14 h-14 rounded-2xl flex items-center justify-center text-xl bg-yellow-100 text-yellow-600';
                popupIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            } else {
                popupIcon.className = 'w-14 h-14 rounded-2xl flex items-center justify-center text-xl bg-red-100 text-red-600';
                popupIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
            }
            popup.classList.remove('hidden'); popup.classList.add('flex');
        }

        function closePopup() {
            document.getElementById('customPopup').classList.remove('flex');
            document.getElementById('customPopup').classList.add('hidden');
        }

        function updateTotal() {
            const val = parseInt(inputJumlah.value) || 0;
            totalView.innerText = 'Rp ' + (val * hargaSatuan).toLocaleString('id-ID');
        }

        function updateUI() {
            selectedSeatLabels.innerText = selectedSeats.length ? selectedSeats.map(s => s.label).join(', ') : 'Belum ada kursi dipilih';
            selectedSeatInputs.innerHTML = '';
            selectedSeats.forEach(s => {
                const i = document.createElement('input'); i.type = 'hidden'; i.name = 'selected_seats[]'; i.value = s.id;
                selectedSeatInputs.appendChild(i);
            });
        }

        seatElements.forEach(seat => {
            seat.addEventListener('click', function() {
                const max = parseInt(inputJumlah.value) || 0;
                const id = this.dataset.seatId;
                const lbl = this.dataset.seatLabel;
                if(max < 1) { showPopup('Silakan isi jumlah tiket terlebih dahulu.', 'warning', 'Jumlah Kosong'); return; }
                const idx = selectedSeats.findIndex(s => s.id === id);
                if(idx > -1) {
                    selectedSeats.splice(idx, 1);
                    this.classList.remove('selected');
                } else {
                    if(selectedSeats.length >= max) { showPopup('Jumlah kursi sudah sesuai jumlah tiket.', 'warning', 'Batas Tercapai'); return; }
                    selectedSeats.push({id, label: lbl});
                    this.classList.add('selected');
                }
                updateUI();
            });
        });

        inputJumlah.addEventListener('input', () => {
            updateTotal();
            if(selectedSeats.length > inputJumlah.value) {
                selectedSeats = [];
                document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
                updateUI();
            }
        });

        tanggalInput.addEventListener('change', () => {
            const f = document.createElement('form'); f.method = 'POST'; f.action = '';
            const i1 = document.createElement('input'); i1.type = 'hidden'; i1.name = 'tanggal'; i1.value = tanggalInput.value;
            const i2 = document.createElement('input'); i2.type = 'hidden'; i2.name = 'jumlah'; i2.value = inputJumlah.value;
            f.append(i1, i2); document.body.append(f); f.submit();
        });

        document.getElementById('bookingForm').onsubmit = (e) => {
            if(selectedSeats.length != inputJumlah.value) {
                e.preventDefault();
                showPopup('Harap pilih kursi sebanyak ' + inputJumlah.value + ' orang.', 'warning', 'Kursi Belum Lengkap');
            }
        };

        updateTotal(); updateUI();

        <?php if ($message != ""): ?>
        document.addEventListener('DOMContentLoaded', () => { showPopup(<?php echo json_encode($message); ?>); });
        <?php endif; ?>
    </script>
</body>
</html>
