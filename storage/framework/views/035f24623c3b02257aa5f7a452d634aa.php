<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['color' => 'brand', 'icon' => '📊', 'label', 'value', 'suffix' => null]));

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

foreach (array_filter((['color' => 'brand', 'icon' => '📊', 'label', 'value', 'suffix' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>

<div class="relative overflow-hidden rounded-2xl border border-<?php echo e($color); ?>-100 bg-gradient-to-br from-<?php echo e($color); ?>-50 to-white p-5">
    <div class="relative z-10 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-bold text-<?php echo e($color); ?>-600 uppercase tracking-wide mb-1"><?php echo e($label); ?></p>
            <p class="text-2xl lg:text-3xl font-extrabold text-slate-800 truncate">
                <?php echo e($value); ?>

                <?php if($suffix): ?><span class="text-sm font-semibold text-slate-400"><?php echo e($suffix); ?></span><?php endif; ?>
            </p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-<?php echo e($color); ?>-500 text-white flex items-center justify-center text-xl shrink-0 shadow-lg shadow-<?php echo e($color); ?>-500/30">
            <?php echo e($icon); ?>

        </div>
    </div>
    <div class="absolute -right-6 -bottom-8 w-28 h-28 rounded-full bg-<?php echo e($color); ?>-200/40 blur-2xl pointer-events-none"></div>
</div>
<?php /**PATH C:\laragon\www\sim-spenga\resources\views/components/stat-card.blade.php ENDPATH**/ ?>