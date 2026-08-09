<?php $__env->startSection('title', 'Absensi Guru Tiap Mapel'); ?>

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
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input min-w-[140px]" onchange="this.form.submit()">
                    <?php $__empty_1 = true; $__currentLoopData = $kelasDiampu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <option value="<?php echo e($k->id); ?>" <?php echo e($kelasId == $k->id ? 'selected' : ''); ?>><?php echo e($k->nama_kelas); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <option value="">-</option>
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
    <?php elseif($kelasDiampu->isEmpty()): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ <?php echo e($guru->name); ?> belum mengajar <?php echo e($mapelAktif->nama_mapel ?? 'mapel ini'); ?> di kelas manapun.
        </div>
    <?php else: ?>
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="font-extrabold text-slate-800 text-lg">Absensi <?php echo e($mapelAktif->nama_mapel ?? '-'); ?> - Kelas <?php echo e($kelasAktif->nama_kelas ?? '-'); ?></p>
                    <p class="text-sm text-slate-400">Guru: <?php echo e($guru->name); ?> &middot; Bulan <?php echo e(\Carbon\Carbon::create()->month($bulan)->translatedFormat('F')); ?> <?php echo e($tahun); ?></p>
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
                <span>Kolom kosong = Hadir / tidak ada pertemuan mapel ini pada tanggal tsb</span>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/laporan/absensi-guru.blade.php ENDPATH**/ ?>