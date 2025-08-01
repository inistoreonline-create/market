
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Update user online status
        function updateOnlineStatus() {
            <?php if (isset($_SESSION['user_id'])): ?>
            $.post('ajax/update_status.php', {action: 'update_online'});
            <?php endif; ?>
        }
        
        // Update status every 30 seconds
        setInterval(updateOnlineStatus, 30000);
        updateOnlineStatus();
        
        // Mark user offline when leaving
        window.addEventListener('beforeunload', function() {
            <?php if (isset($_SESSION['user_id'])): ?>
            navigator.sendBeacon('ajax/update_status.php', new FormData(Object.assign(document.createElement('form'), {action: 'update_offline'})));
            <?php endif; ?>
        });
    </script>
</body>
</html>
