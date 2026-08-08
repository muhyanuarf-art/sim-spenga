
<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const REFRESH_MS = <?php echo e((int) ($interval ?? 30000)); ?>;
    let timer = null;

    function mulai() {
        berhenti();
        timer = setInterval(function () {
            // Hanya reload kalau tab ini yang sedang dilihat user &
            // tidak ada dropdown/menu Alpine yang sedang terbuka,
            // supaya tidak mengganggu interaksi yang sedang berjalan.
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, REFRESH_MS);
    }

    function berhenti() {
        if (timer) clearInterval(timer);
        timer = null;
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            mulai();
        } else {
            berhenti();
        }
    });

    mulai();
})();
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH C:\laragon\www\sim-spenga\resources\views/partials/auto-refresh.blade.php ENDPATH**/ ?>