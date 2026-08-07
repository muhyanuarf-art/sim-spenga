<?php $__env->startSection('title', 'Import Data Kelas'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl mx-auto">
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1">Import Data Kelas dari Excel</p>
        <p class="text-sm text-slate-400 mb-5">
            Format kolom (baris pertama header): <code class="bg-slate-100 px-1.5 py-0.5 rounded">nama_kelas</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">tingkat</code> (7/8/9),
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">nip_wali_kelas</code> (opsional)
        </p>

        <a href="<?php echo e(route('kelas.template')); ?>" class="inline-flex items-center gap-1.5 text-brand-600 hover:underline text-sm font-semibold mb-5">
            📄 Download Template Excel
        </a>

        <form method="POST" action="<?php echo e(route('kelas.import')); ?>" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="input">
            <div class="flex gap-3">
                <a href="<?php echo e(route('kelas.index')); ?>" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/kelas/import.blade.php ENDPATH**/ ?>