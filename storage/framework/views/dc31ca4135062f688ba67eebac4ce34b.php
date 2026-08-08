<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['data', 'title' => 'Siswa Alfa Hari Ini', 'subtitle' => null, 'showKelas' => true]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['data', 'title' => 'Siswa Alfa Hari Ini', 'subtitle' => null, 'showKelas' => true]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="relative overflow-hidden rounded-2xl border border-rose-100 shadow-soft">
    <div class="bg-gradient-to-r from-rose-500 via-rose-500 to-orange-400 px-5 py-4 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="font-bold text-white flex items-center gap-2">
                <span class="text-lg">🚩</span> <?php echo e($title); ?>

            </p>
            <p class="text-xs text-rose-50/90 mt-0.5">
                <?php echo e($subtitle ?? 'Absensi Kelas — status dari guru mapel dengan jam paling akhir hari ini'); ?>

            </p>
        </div>
        <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center text-lg font-extrabold text-white shrink-0">
            <?php echo e($data->count()); ?>

        </div>
    </div>
    <div class="bg-white overflow-x-auto">
        <table class="table-clean w-full">
            <thead>
                <tr>
                    <th>Nama Siswa</th>
                    <?php if($showKelas): ?><th>Kelas</th><?php endif; ?>
                    <th>Menurut Mapel</th>
                    <th>Jam</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="font-medium">
                        <div class="flex items-center gap-2">
                            <?php if (isset($component)) { $__componentOriginal7863338fc871141cde043e16f24c9728 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7863338fc871141cde043e16f24c9728 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.initial-avatar','data' => ['nama' => $a['siswa']->nama ?? '-']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('initial-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($a['siswa']->nama ?? '-')]); ?>
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
                            <?php echo e($a['siswa']->nama ?? '-'); ?>

                        </div>
                    </td>
                    <?php if($showKelas): ?>
                        <td><?php if (isset($component)) { $__componentOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal431ed0c73bd88bcebb6e4c05e61ec935 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.kelas-badge','data' => ['nama' => $a['kelas']->nama_kelas ?? '-']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('kelas-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($a['kelas']->nama_kelas ?? '-')]); ?>
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
                    <?php endif; ?>
                    <td><?php if (isset($component)) { $__componentOriginalb4e46b318ca70209f66149b36ac316c9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb4e46b318ca70209f66149b36ac316c9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.mapel-badge','data' => ['nama' => $a['mapel'] ?? '-']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mapel-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['nama' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($a['mapel'] ?? '-')]); ?>
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
                    <td class="text-slate-500 font-medium"><?php echo e($a['jam_ke'] ? "Jam ke-{$a['jam_ke']}" : '-'); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="<?php echo e($showKelas ? 4 : 3); ?>" class="text-center text-emerald-600 py-8">
                        🎉 Tidak ada siswa Alfa hari ini — kehadiran lancar!
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php /**PATH C:\laragon\www\sim-spenga\resources\views/components/alfa-widget.blade.php ENDPATH**/ ?>