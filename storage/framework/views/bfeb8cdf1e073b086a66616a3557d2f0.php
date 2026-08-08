<?php $__env->startSection('title', 'Jurnal Mengajar Kelas'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <?php if(in_array(auth()->user()->role, ['admin', 'kurikulum', 'kepala_sekolah'])): ?>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    <?php $__currentLoopData = $daftarKelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($k->id); ?>" <?php echo e($k->id === $kelas->id ? 'selected' : ''); ?>><?php echo e($k->nama_kelas); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <?php endif; ?>
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
        </form>
    </div>

    <div class="card p-5">
        <p class="font-extrabold text-slate-800 text-lg mb-1">Jurnal Mengajar Kelas <?php echo e($kelas->nama_kelas); ?></p>
        <p class="text-sm text-slate-400 mb-4">Bulan <?php echo e(\Carbon\Carbon::create()->month($bulan)->translatedFormat('F')); ?> <?php echo e($tahun); ?></p>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead>
                    <tr><th>Tanggal</th><th>Jam</th><th>Mapel</th><th>Guru</th><th>Materi</th><th>H/S/I/A</th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $jurnal; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="whitespace-nowrap"><?php echo e($j->tanggal->translatedFormat('d M Y')); ?></td>
                        <td><?php echo e($j->label_sesi); ?></td>
                        <td class="font-medium"><?php echo e($j->mapel->nama_mapel); ?></td>
                        <td><?php echo e($j->guru->name); ?></td>
                        <td class="text-slate-500"><?php echo e(\Illuminate\Support\Str::limit($j->materi, 60)); ?></td>
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
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/walikelas/jurnal-kelas.blade.php ENDPATH**/ ?>