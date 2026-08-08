<?php $__env->startSection('title', 'Dashboard Guru'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <?php if(!$tahunAjaran): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Hubungi Admin/Kurikulum.
        </div>
    <?php endif; ?>

    <?php if($kelasWali): ?>
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-brand-600 via-brand-600 to-indigo-500 text-white px-5 py-4 flex items-center justify-between flex-wrap gap-3 shadow-lg shadow-brand-500/20">
            <div class="relative z-10">
                <p class="font-bold flex items-center gap-2">🎓 Anda adalah Wali Kelas <?php echo e($kelasWali->nama_kelas); ?></p>
                <p class="text-sm text-white/80">Pantau kehadiran & jurnal mengajar kelas Anda.</p>
            </div>
            <div class="relative z-10 flex gap-2">
                <a href="<?php echo e(route('walikelas.absensi-bulanan')); ?>" class="btn-outline bg-white/95 border-transparent">Rekap Absensi</a>
                <a href="<?php echo e(route('walikelas.jurnal-kelas')); ?>" class="btn-outline bg-white/95 border-transparent">Jurnal Kelas</a>
            </div>
            <div class="absolute -right-6 -bottom-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
        </div>

        <?php if (isset($component)) { $__componentOriginalf2984af19f669ce6d6059461040da801 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf2984af19f669ce6d6059461040da801 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alfa-widget','data' => ['data' => $siswaAlfaHariIni,'title' => 'Siswa Alfa Hari Ini — Kelas '.$kelasWali->nama_kelas,'showKelas' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alfa-widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($siswaAlfaHariIni),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('Siswa Alfa Hari Ini — Kelas '.$kelasWali->nama_kelas),'show-kelas' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf2984af19f669ce6d6059461040da801)): ?>
<?php $attributes = $__attributesOriginalf2984af19f669ce6d6059461040da801; ?>
<?php unset($__attributesOriginalf2984af19f669ce6d6059461040da801); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf2984af19f669ce6d6059461040da801)): ?>
<?php $component = $__componentOriginalf2984af19f669ce6d6059461040da801; ?>
<?php unset($__componentOriginalf2984af19f669ce6d6059461040da801); ?>
<?php endif; ?>
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
                <?php
                    $palet = ['indigo', 'emerald', 'amber', 'sky', 'rose', 'teal', 'violet', 'fuchsia'];
                    $warna = $palet[crc32($sesi['mapel']->nama_mapel ?? '?') % count($palet)];
                    $terisi = $sesi['sudah_diisi'] ?? false;
                ?>
                <a href="<?php echo e(route('mengajar.form', $sesi['ids'])); ?>"
                   class="relative overflow-hidden rounded-xl border p-4 transition block
                        <?php echo e($terisi
                            ? 'border-emerald-200 bg-emerald-50/60'
                            : 'border-'.$warna.'-100 bg-gradient-to-br from-'.$warna.'-50 to-white hover:shadow-md hover:border-'.$warna.'-300'); ?>">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-xs font-bold <?php echo e($terisi ? 'text-emerald-600' : 'text-'.$warna.'-600'); ?>">
                            <?php if($sesi['jam_awal']->id === $sesi['jam_akhir']->id): ?>
                                <?php echo e($sesi['jam_awal']->label); ?>

                            <?php else: ?>
                                Jam ke-<?php echo e($sesi['jam_awal']->jam_ke); ?> s.d ke-<?php echo e($sesi['jam_akhir']->jam_ke); ?>

                            <?php endif; ?>
                        </p>
                        <?php if($terisi): ?>
                            <span class="badge bg-emerald-100 text-emerald-700 shrink-0">Terisi</span>
                        <?php endif; ?>
                    </div>
                    <p class="font-semibold text-slate-800">Kelas <?php echo e($sesi['kelas']->nama_kelas); ?></p>
                    <p class="text-sm text-slate-500"><?php echo e($sesi['mapel']->nama_mapel); ?></p>
                    <?php if(!$terisi): ?>
                        <div class="absolute -right-4 -bottom-6 w-20 h-20 rounded-full bg-<?php echo e($warna); ?>-200/40 blur-xl pointer-events-none"></div>
                    <?php endif; ?>
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
                        <td class="text-slate-500"><?php echo e($j->tanggal->translatedFormat('d M Y')); ?></td>
                        <td><?php if (isset($component)) { $__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.kelas-badge','data' => ['nama' => $j->kelas->nama_kelas]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('kelas-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($j->kelas->nama_kelas)]); ?>
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
                        <td><?php if (isset($component)) { $__componentOriginalb4e46b318ca70209f66149b36ac316c9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb4e46b318ca70209f66149b36ac316c9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.mapel-badge','data' => ['nama' => $j->mapel->nama_mapel]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mapel-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($j->mapel->nama_mapel)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb4e46b318ca70209f66149b36ac316c9)): ?>
<?php $attributes = $__attributesOriginalb4e46b318ca70209f66149b36ac316c9; ?>
<?php unset($__attributesOriginalb4e46b318ca70209f66149b36ac316c9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb4e46b318ca70209f66149b36ac316c9)): ?>
<?php $component = $__componentOriginalb4e46b318ca70209f66149b36ac316c9; ?>
<?php unset($__componentOriginalb4e46b318ca70209f66149b36ac316c9); ?>
<?php endif; ?></td>
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