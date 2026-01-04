<?php
/**
 * DiabetaCare - Main Layout Footer
 * 
 * Closing tags and scripts
 */
?>
            <?php if (isAuthenticated()): ?>
            </div><!-- .content-wrapper -->
            <?php endif; ?>
        </main>
    </div>
    
    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    
    <?php if (isset($scripts)): ?>
    <?php echo $scripts; ?>
    <?php endif; ?>
</body>
</html>
