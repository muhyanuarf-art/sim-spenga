<?php $__env->startSection('title', 'Dashboard Monitoring Sekolah'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">

    <?php if(!$tahunAjaran): ?>
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Silakan aktifkan di menu <b>Tahun Ajaran</b>.
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['color' => 'indigo','icon' => '🧑‍🎓','label' => 'Total Siswa','value' => $totalSiswa]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'indigo','icon' => '🧑‍🎓','label' => 'Total Siswa','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalSiswa)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['color' => 'amber','icon' => '👨‍🏫','label' => 'Total Guru','value' => $totalGuru]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'amber','icon' => '👨‍🏫','label' => 'Total Guru','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalGuru)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['color' => 'teal','icon' => '🏫','label' => 'Total Kelas','value' => $totalKelas]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'teal','icon' => '🏫','label' => 'Total Kelas','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalKelas)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['color' => 'emerald','icon' => '📝','label' => 'Jurnal Hari Ini','value' => $jurnalHariIni,'suffix' => '/ '.$jadwalHariIni.' jam']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'emerald','icon' => '📝','label' => 'Jurnal Hari Ini','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($jurnalHariIni),'suffix' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('/ '.$jadwalHariIni.' jam')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="card p-5 lg:col-span-1">
            <p class="font-bold text-slate-800 mb-4">Rekap Absensi Hari Ini</p>
            <div class="grid grid-cols-2 gap-3">
                <?php $__currentLoopData = [
                    ['Hadir', 'emerald', '✅'],
                    ['Sakit', 'amber', '🤒'],
                    ['Izin', 'sky', '✋'],
                    ['Alfa', 'rose', '🚩'],
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$status, $color, $icon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded-xl bg-<?php echo e($color); ?>-50 border border-<?php echo e($color); ?>-100 p-3 text-center">
                        <p class="text-xl"><?php echo e($icon); ?></p>
                        <p class="text-xl font-extrabold text-<?php echo e($color); ?>-700 leading-tight mt-0.5"><?php echo e($rekapHariIni[$status] ?? 0); ?></p>
                        <p class="text-xs font-semibold text-<?php echo e($color); ?>-700/70"><?php echo e($status); ?></p>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <div class="lg:col-span-2">
            <?php if (isset($component)) { $__componentOriginalf2984af19f669ce6d6059461040da801 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf2984af19f669ce6d6059461040da801 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alfa-widget','data' => ['data' => $siswaAlfaHariIni]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alfa-widget'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['data' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($siswaAlfaHariIni)]); ?>
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
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Status Pengisian Absensi Per Kelas (Hari Ini)</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th>Status</th></tr></thead>
                <tbody>
                    <?php $__currentLoopData = $rekapPerKelas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="font-semibold">
                            <div class="flex items-center gap-2">
                                <?php if (isset($component)) { $__componentOriginal7863338fc871141cde043e16f24c9728 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7863338fc871141cde043e16f24c9728 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.initial-avatar','data' => ['nama' => $r['kelas']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('initial-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($r['kelas'])]); ?>
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
                                <?php echo e($r['kelas']); ?>

                            </div>
                        </td>
                        <td class="text-slate-500"><?php echo e($r['wali_kelas']); ?></td>
                        <td><?php echo e($r['jumlah_siswa']); ?></td>
                        <td>
                            <?php if($r['sudah_diabsen']): ?>
                                <span class="badge bg-emerald-50 text-emerald-700">✅ Sudah diabsen</span>
                            <?php else: ?>
                                <span class="badge bg-slate-100 text-slate-500">⏳ Belum ada data</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/dashboard/admin.blade.php ENDPATH**/ ?>