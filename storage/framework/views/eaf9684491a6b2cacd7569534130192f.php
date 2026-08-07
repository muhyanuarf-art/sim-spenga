<?php $__env->startSection('title', 'Rekapitulasi'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
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

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-4">Kepatuhan Pengisian Jurnal - Guru</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Guru</th><th class="text-right">Jumlah Jurnal</th></tr></thead>
                    <tbody>
                        <?php $__currentLoopData = $rekapGuru; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="font-medium"><?php echo e($g->name); ?></td>
                            <td class="text-right font-bold <?php echo e($g->jurnal_bulan_ini == 0 ? 'text-red-500' : 'text-emerald-600'); ?>"><?php echo e($g->jurnal_bulan_ini); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-4">Rekap Per Kelas</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Kelas</th><th>Siswa</th><th>Jurnal</th><th>Total Alfa</th></tr></thead>
                    <tbody>
                        <?php $__currentLoopData = $rekapKelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="font-semibold"><?php echo e($r['kelas']->nama_kelas); ?></td>
                            <td><?php echo e($r['kelas']->siswas_count); ?></td>
                            <td><?php echo e($r['jumlah_jurnal']); ?></td>
                            <td class="font-bold <?php echo e($r['total_alfa'] > 0 ? 'text-red-500' : 'text-slate-400'); ?>"><?php echo e($r['total_alfa']); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/rekap/index.blade.php ENDPATH**/ ?>