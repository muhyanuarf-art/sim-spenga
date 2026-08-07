<?php $__env->startSection('title', 'Rekap Absensi Bulanan'); ?>

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
            <button type="button" onclick="window.print()" class="btn-outline">🖨️ Cetak / Export PDF</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="font-extrabold text-slate-800 text-lg">Rekap Absensi Kelas <?php echo e($kelas->nama_kelas); ?></p>
                <p class="text-sm text-slate-400">Bulan <?php echo e(\Carbon\Carbon::create()->month($bulan)->translatedFormat('F')); ?> <?php echo e($tahun); ?> &middot; Wali Kelas: <?php echo e($kelas->waliKelas->name ?? '-'); ?></p>
            </div>
        </div>

        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="border border-slate-200 px-2 py-2 sticky left-0 bg-slate-50">NIS</th>
                        <th class="border border-slate-200 px-2 py-2 sticky left-14 bg-slate-50 text-left min-w-[160px]">Nama Siswa</th>
                        <?php for($t = 1; $t <= $jumlahHari; $t++): ?>
                            <th class="border border-slate-200 px-1 py-2 w-6"><?php echo e($t); ?></th>
                        <?php endfor; ?>
                        <th class="border border-slate-200 px-2 py-2 bg-amber-50">S</th>
                        <th class="border border-slate-200 px-2 py-2 bg-blue-50">I</th>
                        <th class="border border-slate-200 px-2 py-2 bg-red-50">A</th>
                        <th class="border border-slate-200 px-2 py-2 bg-slate-100">Jml</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $rekap; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="border border-slate-200 px-2 py-1.5 text-center sticky left-0 bg-white"><?php echo e($r['siswa']->nis); ?></td>
                        <td class="border border-slate-200 px-2 py-1.5 sticky left-14 bg-white font-medium whitespace-nowrap"><?php echo e($r['siswa']->nama); ?></td>
                        <?php for($t = 1; $t <= $jumlahHari; $t++): ?>
                            <?php $kode = $r['harian'][$t]; ?>
                            <td class="border border-slate-200 text-center
                                <?php if($kode === 'S'): ?> text-amber-600 font-bold
                                <?php elseif($kode === 'I'): ?> text-blue-600 font-bold
                                <?php elseif($kode === 'A'): ?> text-red-600 font-bold
                                <?php endif; ?>">
                                <?php echo e($kode); ?>

                            </td>
                        <?php endfor; ?>
                        <td class="border border-slate-200 text-center font-bold bg-amber-50/50"><?php echo e($r['sakit']); ?></td>
                        <td class="border border-slate-200 text-center font-bold bg-blue-50/50"><?php echo e($r['izin']); ?></td>
                        <td class="border border-slate-200 text-center font-bold bg-red-50/50"><?php echo e($r['alfa']); ?></td>
                        <td class="border border-slate-200 text-center font-bold bg-slate-100"><?php echo e($r['jumlah']); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e($jumlahHari + 6); ?>" class="text-center text-slate-400 py-8">Tidak ada data siswa di kelas ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex gap-6 mt-4 text-xs text-slate-500">
            <span><b>S</b> = Sakit</span>
            <span><b>I</b> = Izin</span>
            <span><b>A</b> = Alfa</span>
            <span>Kolom kosong = Hadir</span>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/walikelas/absensi-bulanan.blade.php ENDPATH**/ ?>