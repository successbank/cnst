    </main>
    
    <footer class="admin-footer" style="background: #1A237E; color: white; padding: 20px 0; margin-top: 60px;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px; text-align: center;">
            <p style="font-size: 14px; margin: 0;">
                &copy; <?php echo date('Y'); ?> 충남스틸. All rights reserved. | 
                <a href="../privacy.php" style="color: white; text-decoration: none;" target="_blank">개인정보처리방침</a> | 
                <a href="../terms.php" style="color: white; text-decoration: none;" target="_blank">이용약관</a>
            </p>
        </div>
    </footer>
    
    <?php if (isset($additionalScripts)): ?>
    <script>
    <?php echo $additionalScripts; ?>
    </script>
    <?php endif; ?>
</body>
</html>