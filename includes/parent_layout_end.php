    </main>
    <?php require __DIR__ . '/site_footer.php'; ?>
    <!-- Shared accessible confirmation dialog used for destructive parent actions. -->
    <dialog class="parent-confirm-dialog" data-confirm-dialog aria-labelledby="confirm-dialog-title">
        <form method="dialog">
            <h2 id="confirm-dialog-title">Please confirm</h2>
            <p data-confirm-message></p>
            <div class="d-flex justify-content-end gap-2">
                <button class="parent-btn-secondary btn" value="cancel">Keep it</button>
                <button class="btn btn-primary" type="button" data-confirm-continue>Continue</button>
            </div>
        </form>
    </dialog>
    <script src="<?= e(appUrl('assets/js/parent.js')) ?>" defer></script>
</body>
</html>
