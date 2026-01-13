</main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-3 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <small>Système de Gestion v1.0</small>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    <script src="assets/js/main.js"></script>
    
    <?php if (isset($page_script)): ?>
        <script><?php echo $page_script; ?></script>
    <?php endif; ?>
</body>
</html>
