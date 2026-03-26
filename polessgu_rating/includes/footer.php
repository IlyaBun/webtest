        </main>
    </div>

    <!-- Flash сообщения -->
    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="flash-message flash-<?php echo $flash['type']; ?>">
        <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
        <span><?php echo e($flash['message']); ?></span>
        <button class="close-flash" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>

    <!-- Подключение скриптов -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
