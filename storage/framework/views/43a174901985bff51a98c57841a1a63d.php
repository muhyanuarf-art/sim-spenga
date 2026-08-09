<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['nama', 'size' => 'w-7 h-7 text-xs']));

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

foreach (array_filter((['nama', 'size' => 'w-7 h-7 text-xs']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
    $palet = ['indigo', 'emerald', 'amber', 'sky', 'rose', 'teal', 'violet', 'fuchsia'];
    $warna = $palet[crc32($nama ?? '?') % count($palet)];
?>
<span class="<?php echo e($size); ?> rounded-full bg-<?php echo e($warna); ?>-100 text-<?php echo e($warna); ?>-700 font-bold flex items-center justify-center shrink-0">
    <?php echo e(strtoupper(substr($nama ?? '?', 0, 1))); ?>

</span>
<?php /**PATH C:\laragon\www\sim-spenga\resources\views/components/initial-avatar.blade.php ENDPATH**/ ?>