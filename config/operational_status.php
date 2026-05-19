<?php

function sync_operational_status($conn)
{
    mysqli_query($conn, "
        UPDATE train_runs run
        JOIN schedules s ON s.id_schedule = run.id_schedule
        SET run.status_run = CASE
            WHEN NOW() >= TIMESTAMP(
                DATE_ADD(run.tanggal_berangkat, INTERVAL IF(s.jam_tiba < s.jam_berangkat, 1, 0) DAY),
                s.jam_tiba
            ) THEN 'selesai'
            WHEN NOW() >= TIMESTAMP(run.tanggal_berangkat, s.jam_berangkat) THEN 'berjalan'
            ELSE 'terjadwal'
        END
        WHERE run.status_run <> 'batal'
    ");
}

function operational_status_badge_class($status)
{
    if ($status === 'selesai') {
        return 'bg-green-50 text-green-600';
    }
    if ($status === 'berjalan') {
        return 'bg-yellow-50 text-yellow-600';
    }
    if ($status === 'batal') {
        return 'bg-red-50 text-red-600';
    }
    return 'bg-blue-50 text-blue-600';
}
