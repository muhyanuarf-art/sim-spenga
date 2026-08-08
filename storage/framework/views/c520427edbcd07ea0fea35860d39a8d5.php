<?php $__env->startSection('title', 'Dashboard Monitoring Sekolah'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">

    <?php if(!$tahunAjaran): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Silakan aktifkan di menu <b>Tahun Ajaran</b>.
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Siswa</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($totalSiswa); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Guru</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($totalGuru); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Kelas</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($totalKelas); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jurnal Hari Ini</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($jurnalHariIni); ?> <span class="text-sm text-slate-400 font-medium">/ <?php echo e($jadwalHariIni); ?> jam pelajaran</span></p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card p-5 lg:col-span-1">
            <p class="font-bold text-slate-800 mb-4">Rekap Absensi Hari Ini</p>
            <div class="space-y-3">
                <?php $__currentLoopData = ['Hadir' => 'emerald', 'Sakit' => 'amber', 'Izin' => 'blue', 'Alfa' => 'red']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600"><?php echo e($status); ?></span>
                        <span class="badge bg-<?php echo e($color); ?>-50 text-<?php echo e($color); ?>-700"><?php echo e($rekapHariIni[$status] ?? 0); ?> siswa</span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="card p-5 lg:col-span-2">
            <p class="font-bold text-slate-800 mb-1">🚩 Siswa Alfa Hari Ini</p>
            <p class="text-xs text-slate-400 mb-4">Berdasarkan Absensi Kelas &mdash; status dari guru mapel dengan jam paling akhir yang mengisi hari ini.</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Menurut Mapel</th><th>Jam</th></tr></thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $siswaAlfaHariIni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="font-medium"><?php echo e($a['siswa']->nama ?? '-'); ?></td>
                            <td><?php echo e($a['kelas']->nama_kelas ?? '-'); ?></td>
                            <td class="text-slate-500"><?php echo e($a['mapel'] ?? '-'); ?></td>
                            <td class="text-slate-500"><?php echo e($a['jam_ke'] ? "Jam ke-{$a['jam_ke']}" : '-'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-center text-emerald-600 py-6">🎉 Tidak ada siswa Alfa hari ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Status Pengisian Absensi Per Kelas (Hari Ini)</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th>Status</th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $rekapPerKelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="font-semibold"><?php echo e($r['kelas']); ?></td>
                        <td><?php echo e($r['wali_kelas']); ?></td>
                        <td><?php echo e($r['jumlah_siswa']); ?></td>
                        <td>
                            <?php if($r['sudah_diabsen']): ?>
                                <span class="badge bg-emerald-50 text-emerald-700">Sudah diabsen</span>
                            <?php else: ?>
                                <span class="badge bg-slate-100 text-slate-500">Belum ada data</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/dashboard/admin.blade.php ENDPATH**/ ?>