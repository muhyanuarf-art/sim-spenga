<?php $__env->startSection('title', 'Dashboard Kurikulum'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <?php if(!$tahunAjaran): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Silakan aktifkan di menu <b>Tahun Ajaran</b>.
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jadwal Hari Ini</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($totalJadwalHariIni); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jurnal Terisi</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($totalJurnalHariIni); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Guru</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($totalGuru); ?></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Mapping Aktif</p>
            <p class="text-2xl font-extrabold text-slate-800"><?php echo e($totalMappingKelas); ?></p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <a href="<?php echo e(route('kurikulum.guru-mengajar.index')); ?>" class="card p-5 hover:shadow-md transition group">
            <p class="font-bold text-slate-800 group-hover:text-brand-600">👨‍🏫 Mapping Guru Mengajar</p>
            <p class="text-sm text-slate-400 mt-1">Atur guru mengajar mapel apa di kelas mana.</p>
        </a>
        <a href="<?php echo e(route('jadwal.index')); ?>" class="card p-5 hover:shadow-md transition group">
            <p class="font-bold text-slate-800 group-hover:text-brand-600">🗓️ Jadwal Pelajaran</p>
            <p class="text-sm text-slate-400 mt-1">Susun jadwal pelajaran manual atau import Excel.</p>
        </a>
        <a href="<?php echo e(route('rekap.index')); ?>" class="card p-5 hover:shadow-md transition group">
            <p class="font-bold text-slate-800 group-hover:text-brand-600">📈 Rekapitulasi</p>
            <p class="text-sm text-slate-400 mt-1">Pantau kepatuhan pengisian jurnal & absensi.</p>
        </a>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Jurnal Mengajar Terbaru Hari Ini</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Guru</th><th>Kelas</th><th>Mapel</th><th>Materi</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $jurnalHariIni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="font-medium"><?php echo e($j->guru->name); ?></td>
                        <td><?php echo e($j->kelas->nama_kelas); ?></td>
                        <td><?php echo e($j->mapel->nama_mapel); ?></td>
                        <td class="text-slate-500"><?php echo e(\Illuminate\Support\Str::limit($j->materi, 60)); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada jurnal yang diisi hari ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/dashboard/kurikulum.blade.php ENDPATH**/ ?>