        </div><!-- /.container -->
    </main>

    <?php if (!isset($active_page) || $active_page !== 'index'): ?>
    <!-- Global Footer (non-index pages) -->
    <footer class="global-footer">
        <div class="container">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="footer-brand">UniClubs</span>
                    <span style="color:var(--text-dim);font-size:0.8rem;">—</span>
                    <p style="font-size:0.82rem;">Plateforme de gestion de clubs universitaires</p>
                </div>
                <p style="font-size:0.78rem;">© <?= date('Y') ?> UniClubs · Fait avec ❤️ pour les étudiants</p>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $base_url ?>js/global.js"></script>
    <?php if (isset($page_js)): ?>
        <script src="<?= $base_url . htmlspecialchars($page_js) ?>"></script>
    <?php endif; ?>
</body>
</html>
