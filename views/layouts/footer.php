            </div> <!-- End container-fluid -->
        </div> <!-- End page-content-wrapper -->
    </div> <!-- End d-flex wrapper -->

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-backdrop" class="sidebar-backdrop d-lg-none" aria-hidden="true"></div>

    <!-- Smart Warehouse AI Chatbox Widget -->
    <?php require_once __DIR__ . '/ai_chatbox.php'; ?>

    <!-- Real-time Transaction Pop-up Modal -->
    <?php require_once __DIR__ . '/transaction_popup_modal.php'; ?>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.SMART_BASE_URL = '<?php echo BASE_URL; ?>';
    </script>
    <!-- Custom JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
    <!-- Real-time Live Notifications Engine -->
    <script src="<?php echo BASE_URL; ?>/assets/js/realtime-notifications.js"></script>
</body>
</html>
