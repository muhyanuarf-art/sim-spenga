<?php $__env->startSection('title', 'Import Data Siswa'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl mx-auto">
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1">Import Data Siswa dari Excel</p>
        <p class="text-sm text-slate-400 mb-5">
            Format kolom (baris pertama header): <code class="bg-slate-100 px-1.5 py-0.5 rounded">nis</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">nisn</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">nama</code>,
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">jenis_kelamin</code> (L/P),
            <code class="bg-slate-100 px-1.5 py-0.5 rounded">kode_kelas</code>
        </p>

        <a href="<?php echo e(route('siswa.template')); ?>" class="inline-flex items-center gap-1.5 text-brand-600 hover:underline text-sm font-semibold mb-5">
            📄 Download Template Excel
        </a>

        <form method="POST" action="<?php echo e(route('siswa.import')); ?>" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="input">
            <div class="flex gap-3">
                <a href="<?php echo e(route('siswa.index')); ?>" class="btn-outline">Batal</a>
                <button type="submit" class="btn-primary">Upload & Import</button>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\sim-spenga\resources\views/siswa/import.blade.php ENDPATH**/ ?>