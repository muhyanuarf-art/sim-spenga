<?php $__env->startSection('title', 'Dashboard Guru'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <?php if(!$tahunAjaran): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Hubungi Admin/Kurikulum.
        </div>
    <?php endif; ?>

    <?php if($kelasWali): ?>
        <div class="rounded-xl bg-brand-50 border border-brand-100 text-brand-800 px-5 py-4 flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="font-bold">🎓 Anda adalah Wali Kelas <?php echo e($kelasWali->nama_kelas); ?></p>
                <p class="text-sm text-brand-700/70">Pantau kehadiran & jurnal mengajar kelas Anda.</p>
            </div>
            <div class="flex gap-2">
                <a href="<?php echo e(route('walikelas.absensi-bulanan')); ?>" class="btn-outline bg-white">Rekap Absensi</a>
                <a href="<?php echo e(route('walikelas.jurnal-kelas')); ?>" class="btn-outline bg-white">Jurnal Kelas</a>
            </div>
        </div>

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">🚩 Siswa Alfa Hari Ini &mdash; Kelas <?php echo e($kelasWali->nama_kelas); ?></p>
            <p class="text-xs text-slate-400 mb-4">Berdasarkan Absensi Kelas &mdash; status dari guru mapel dengan jam paling akhir yang mengisi hari ini.</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Nama Siswa</th><th>Menurut Mapel</th><th>Jam</th></tr></thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $siswaAlfaHariIni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="font-medium"><?php echo e($a['siswa']->nama ?? '-'); ?></td>
                            <td class="text-slate-500"><?php echo e($a['mapel'] ?? '-'); ?></td>
                            <td class="text-slate-500"><?php echo e($a['jam_ke'] ? "Jam ke-{$a['jam_ke']}" : '-'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="3" class="text-center text-emerald-600 py-6">🎉 Tidak ada siswa Alfa hari ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <p class="font-bold text-slate-800">Jadwal Mengajar Hari Ini</p>
            <a href="<?php echo e(route('mengajar.index')); ?>" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua &rarr;</a>
        </div>

        <?php if($jadwalHariIni->isEmpty()): ?>
            <p class="text-sm text-slate-400 py-6 text-center">Tidak ada jadwal mengajar untuk hari ini.</p>
        <?php else: ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php $__currentLoopData = $jadwalHariIni; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sesi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('mengajar.form', $sesi['ids'])); ?>" class="border border-slate-200 rounded-xl p-4 hover:border-brand-400 hover:bg-brand-50/40 transition block">
                    <p class="text-xs font-bold text-brand-600 mb-1">
                        <?php if($sesi['jam_awal']->id === $sesi['jam_akhir']->id): ?>
                            <?php echo e($sesi['jam_awal']->label); ?>

                        <?php else: ?>
                            Jam ke-<?php echo e($sesi['jam_awal']->jam_ke); ?> s.d ke-<?php echo e($sesi['jam_akhir']->jam_ke); ?>

                        <?php endif; ?>
                    </p>
                    <p class="font-semibold text-slate-800">Kelas <?php echo e($sesi['kelas']->nama_kelas); ?></p>
                    <p class="text-sm text-slate-500"><?php echo e($sesi['mapel']->nama_mapel); ?></p>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Jurnal Terakhir Saya</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Materi</th></tr></thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $jurnalTerakhir; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><?php echo e($j->tanggal->translatedFormat('d M Y')); ?></td>
                        <td><?php echo e($j->kelas->nama_kelas); ?></td>
                        <td><?php echo e($j->mapel->nama_mapel); ?></td>
                        <td class="text-slate-500"><?php echo e(\Illuminate\Support\Str::limit($j->materi, 50)); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada jurnal.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/dashboard/guru.blade.php ENDPATH**/ ?>