<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['nama']));

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

foreach (array_filter((['nama']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars); ?>
<?php
    $palet = ['violet', 'teal', 'amber', 'sky', 'rose', 'indigo', 'emerald', 'fuchsia'];
    $warna = $palet[crc32($nama ?? '?') % count($palet)];
?>
<span class="badge bg-<?php echo e($warna); ?>-50 text-<?php echo e($warna); ?>-700"><?php echo e($nama ?? '-'); ?></span>
<?php /**PATH C:\laragon\www\sim-spenga\resources\views/components/kelas-badge.blade.php ENDPATH**/ ?>