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
            <button type="button" onclick="window.print()" class="btn-outline ml-auto">🖨️ Cetak / Export PDF</button>
        </form>
        <p class="text-xs text-slate-400 mt-3">
            📅 Hari ini: <b class="text-slate-500"><?php echo e(now()->translatedFormat('l, d F Y')); ?></b>
            &middot; bulan &amp; tahun di atas otomatis mengikuti tanggal server saat halaman ini dibuka.
        </p>
    </div>

    <?php if(!$tahunAjaran): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif, jadi jumlah "seharusnya" belum bisa dihitung dari jadwal.
        </div>
    <?php endif; ?>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
            <p class="font-extrabold text-slate-800 text-lg">Rekapitulasi Jurnal Mengajar</p>
        </div>
        <p class="text-sm text-slate-400 mb-4">
            Bulan <?php echo e(\Carbon\Carbon::create()->month($bulan)->translatedFormat('F')); ?> <?php echo e($tahun); ?> &middot;
            "Seharusnya" dihitung dari jadwal pelajaran per SESI mengajar (jam berurutan = 1 sesi = 1 jurnal), bukan per jam.
        </p>

        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="border border-slate-200 px-2 py-2 sticky left-0 bg-slate-50 text-left min-w-[170px]">Guru</th>
                        <?php for($t = 1; $t <= $jumlahHari; $t++): ?>
                            <th class="border border-slate-200 px-1 py-2 w-9"><?php echo e($t); ?></th>
                        <?php endfor; ?>
                        <th class="border border-slate-200 px-2 py-2 bg-emerald-50">Terisi</th>
                        <th class="border border-slate-200 px-2 py-2 bg-slate-100">Seharusnya</th>
                        <th class="border border-slate-200 px-2 py-2 bg-indigo-50">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $rekapGuru; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="border border-slate-200 px-2 py-1.5 sticky left-0 bg-white font-medium whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <?php if (isset($component)) { $__componentOriginal7863338fc871141cde043e16f24c9728 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7863338fc871141cde043e16f24c9728 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.initial-avatar','data' => ['nama' => $r['guru']->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('initial-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($r['guru']->name)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7863338fc871141cde043e16f24c9728)): ?>
<?php $attributes = $__attributesOriginal7863338fc871141cde043e16f24c9728; ?>
<?php unset($__attributesOriginal7863338fc871141cde043e16f24c9728); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7863338fc871141cde043e16f24c9728)): ?>
<?php $component = $__componentOriginal7863338fc871141cde043e16f24c9728; ?>
<?php unset($__componentOriginal7863338fc871141cde043e16f24c9728); ?>
<?php endif; ?>
                                <?php echo e($r['guru']->name); ?>

                            </div>
                        </td>
                        <?php for($t = 1; $t <= $jumlahHari; $t++): ?>
                            <?php $h = $r['harian'][$t]; ?>
                            <td class="border border-slate-200 text-center
                                <?php if($h['seharusnya'] === 0): ?> text-slate-300
                                <?php elseif($h['terisi'] === $h['seharusnya']): ?> text-emerald-600 font-bold bg-emerald-50/40
                                <?php else: ?> text-red-500 font-bold bg-red-50/40
                                <?php endif; ?>"
                                <?php if($h['seharusnya'] > 0): ?> title="<?php echo e($h['terisi']); ?> dari <?php echo e($h['seharusnya']); ?> sesi terisi tanggal <?php echo e($t); ?>" <?php endif; ?>>
                                <?php if($h['seharusnya'] === 0): ?>
                                    &middot;
                                <?php else: ?>
                                    <?php echo e($h['terisi']); ?>/<?php echo e($h['seharusnya']); ?>

                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td class="border border-slate-200 text-center font-bold bg-emerald-50/50"><?php echo e($r['total_terisi']); ?></td>
                        <td class="border border-slate-200 text-center font-bold bg-slate-100"><?php echo e($r['total_seharusnya']); ?></td>
                        <td class="border border-slate-200 text-center font-bold
                            <?php echo e($r['persen'] === null ? 'text-slate-300' : ($r['persen'] >= 90 ? 'text-emerald-600 bg-emerald-50/50' : ($r['persen'] >= 60 ? 'text-amber-600 bg-amber-50/50' : 'text-red-500 bg-red-50/50'))); ?>">
                            <?php echo e($r['persen'] !== null ? $r['persen'].'%' : '-'); ?>

                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e($jumlahHari + 4); ?>" class="text-center text-slate-400 py-8">Belum ada data guru / jadwal.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex gap-6 mt-4 text-xs text-slate-500 flex-wrap">
            <span><b class="text-emerald-600">Hijau</b> = semua sesi hari itu sudah terisi jurnalnya</span>
            <span><b class="text-red-500">Merah</b> = ada sesi yang belum terisi</span>
            <span>&middot; = tidak ada jadwal mengajar hari itu</span>
            <span class="text-slate-400">Hover angka untuk detail tanggal.</span>
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Rekap Per Kelas</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Siswa</th><th>Jurnal Terisi</th><th>Total Alfa</th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $rekapKelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="font-semibold"><?php if (isset($component)) { $__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.kelas-badge','data' => ['nama' => $r['kelas']->nama_kelas]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('kelas-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($r['kelas']->nama_kelas)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935)): ?>
<?php $attributes = $__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935; ?>
<?php unset($__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935)): ?>
<?php $component = $__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935; ?>
<?php unset($__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935); ?>
<?php endif; ?></td>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/rekap/index.blade.php ENDPATH**/ ?>