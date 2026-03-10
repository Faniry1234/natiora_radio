<!-- Login Page -->
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Se connecter</h2>
            <p>À Natiora Radio 98.2</p>
        </div>

        <?php if (!empty($_SESSION['flash']) && $_SESSION['flash_type'] === 'error'): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['flash']); ?>
            </div>
            <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <form method="POST" action="/index.php?route=auth/login_post" class="auth-form">
            <div class="form-group">
                <label for="login-email">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <input type="email" id="login-email" name="email" placeholder="votre@email.com" required>
            </div>

            <div class="form-group">
                <label for="login-password">
                    <i class="fas fa-lock"></i> Mot de passe
                </label>
                <input type="password" id="login-password" name="password" placeholder="••••••" required>
            </div>

            <button type="submit" class="btn-primary-full">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>

        <div class="auth-footer">
            <p>Pas encore de compte? <a href="/index.php?route=auth/register" class="link-accent">S'inscrire</a></p>
        </div>
    </div>
</div>
<script>
(function(){
    const forms = document.querySelectorAll('.auth-form');
    forms.forEach(function(f){
        f.addEventListener('submit', function(){
            const em = f.querySelector('input[name="email"]');
            const pw = f.querySelector('input[name="password"]');
            if (em) em.value = em.value.trim();
            if (pw) pw.value = pw.value.trim();
        });
    });
})();
</script>
