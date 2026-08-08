<?php $__env->startSection('title', 'Jurnal Mengajar Guru Tiap Mapel'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <?php if(!$isGuru): ?>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <select name="guru_id" class="input min-w-[200px]" onchange="this.form.submit()">
                    <?php $__currentLoopData = $guruList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($g->id); ?>" <?php echo e($guru && $guru->id === $g->id ? 'selected' : ''); ?>><?php echo e($g->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php else: ?>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <div class="input bg-slate-50 text-slate-500 font-medium"><?php echo e($guru->name); ?></div>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mata Pelajaran</label>
                <select name="mapel_id" class="input min-w-[200px]" onchange="this.form.submit()">
                    <?php $__empty_1 = true; $__currentLoopData = $mapelDiampu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <option value="<?php echo e($m->id); ?>" <?php echo e($mapelId == $m->id ? 'selected' : ''); ?>><?php echo e($m->nama_mapel); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <option value="">Guru belum mengampu mapel apapun</option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    <?php $__currentLoopData = range(1,12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($b); ?>" <?php echo e($b === $bulan ? 'selected' : ''); ?>><?php echo e(\Carbon\Carbon::create()->month($b)->translatedFormat('F')); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    <?php $__currentLoopData = range(now()->year - 1, now()->year + 1); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($y); ?>" <?php echo e($y === $tahun ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="button" onclick="window.print()" class="btn-outline">🖨️ Cetak</button>
        </form>
    </div>

    <?php if(!$guru): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada data guru.
        </div>
    <?php elseif($mapelDiampu->isEmpty()): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ <?php echo e($guru->name); ?> belum diampukan mata pelajaran apapun oleh Kurikulum. Silakan atur di menu Mapping Guru Mengajar.
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Jumlah Pertemuan</p>
                <p class="text-2xl font-extrabold text-slate-800"><?php echo e($ringkasan['pertemuan']); ?></p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Hadir</p>
                <p class="text-2xl font-extrabold text-emerald-600"><?php echo e($ringkasan['hadir']); ?></p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Sakit</p>
                <p class="text-2xl font-extrabold text-amber-600"><?php echo e($ringkasan['sakit']); ?></p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Izin</p>
                <p class="text-2xl font-extrabold text-blue-600"><?php echo e($ringkasan['izin']); ?></p>
            </div>
            <div class="card p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Total Alfa</p>
                <p class="text-2xl font-extrabold text-red-600"><?php echo e($ringkasan['alfa']); ?></p>
            </div>
        </div>

        <div class="card p-5">
            <p class="font-extrabold text-slate-800 text-lg mb-1">Jurnal Mengajar - <?php echo e($guru->name); ?></p>
            <p class="text-sm text-slate-400 mb-4">
                Mata Pelajaran: <b><?php echo e($mapelAktif->nama_mapel ?? '-'); ?></b>
                &middot; <?php echo e(\Carbon\Carbon::create()->month($bulan)->translatedFormat('F')); ?> <?php echo e($tahun); ?>

            </p>

            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr><th>Tanggal</th><th>Jam</th><th>Kelas</th><th>Materi</th><th>Kegiatan</th><th>H/S/I/A</th></tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $jurnal; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="whitespace-nowrap"><?php echo e($j->tanggal->translatedFormat('d M Y')); ?></td>
                            <td class="whitespace-nowrap"><?php echo e($j->label_sesi); ?></td>
                            <td class="font-semibold"><?php echo e($j->kelas->nama_kelas); ?></td>
                            <td class="text-slate-600"><?php echo e($j->materi); ?></td>
                            <td class="text-slate-500"><?php echo e($j->kegiatan ?: '-'); ?></td>
                            <td class="whitespace-nowrap text-xs">
                                <span class="text-emerald-600 font-bold"><?php echo e($j->jumlah_hadir); ?></span> /
                                <span class="text-amber-600 font-bold"><?php echo e($j->jumlah_sakit); ?></span> /
                                <span class="text-blue-600 font-bold"><?php echo e($j->jumlah_izin); ?></span> /
                                <span class="text-red-600 font-bold"><?php echo e($j->jumlah_alfa); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada jurnal pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/laporan/jurnal-guru.blade.php ENDPATH**/ ?>