<?php $__env->startSection('title', 'Absensi & Jurnal Mengajar'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3">Pilih Hari</p>
        <div class="flex flex-wrap gap-2">
            <?php $__currentLoopData = $hariList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('mengajar.index', ['hari' => $h])); ?>"
                   class="px-4 py-2 rounded-lg text-sm font-semibold border <?php echo e($h === $hari ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50'); ?>">
                    <?php echo e($h); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Jadwal Mengajar - <?php echo e($hari); ?></p>
        <p class="text-xs text-slate-400 -mt-3 mb-4">Jam yang berurutan untuk kelas & mapel yang sama otomatis digabung jadi 1 sesi — cukup isi absensi & jurnal 1x.</p>

        <?php if(!$tahunAjaran): ?>
            <p class="text-sm text-amber-600">Tidak ada Tahun Ajaran aktif.</p>
        <?php elseif($sesiList->isEmpty()): ?>
            <p class="text-sm text-slate-400 py-8 text-center">Tidak ada jadwal mengajar pada hari <?php echo e($hari); ?>.</p>
        <?php else: ?>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php $__currentLoopData = $sesiList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sesi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('mengajar.form', $sesi['ids'])); ?>"
                   class="border rounded-xl p-4 transition block <?php echo e(($sesi['sudah_diisi'] ?? false) ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 hover:border-brand-400 hover:bg-brand-50/40'); ?>">
                    <div class="flex items-center justify-between mb-1 gap-2">
                        <p class="text-xs font-bold text-brand-600">
                            <?php if($sesi['jam_awal']->id === $sesi['jam_akhir']->id): ?>
                                <?php echo e($sesi['jam_awal']->label); ?>

                            <?php else: ?>
                                Jam ke-<?php echo e($sesi['jam_awal']->jam_ke); ?> s.d ke-<?php echo e($sesi['jam_akhir']->jam_ke); ?>

                                (<?php echo e(substr($sesi['jam_awal']->jam_mulai, 0, 5)); ?> - <?php echo e(substr($sesi['jam_akhir']->jam_selesai, 0, 5)); ?>)
                            <?php endif; ?>
                        </p>
                        <?php if($sesi['sudah_diisi'] ?? false): ?>
                            <span class="badge bg-emerald-100 text-emerald-700 shrink-0">Terisi</span>
                        <?php endif; ?>
                    </div>
                    <p class="font-semibold text-slate-800">Kelas <?php echo e($sesi['kelas']->nama_kelas); ?></p>
                    <p class="text-sm text-slate-500"><?php echo e($sesi['mapel']->nama_mapel); ?></p>
                    <?php if($sesi['slots']->count() > 1): ?>
                        <p class="text-xs text-slate-400 mt-1"><?php echo e($sesi['slots']->count()); ?> jam pelajaran &middot; 1x isi absensi</p>
                    <?php endif; ?>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/absensi/pilih-kelas.blade.php ENDPATH**/ ?>