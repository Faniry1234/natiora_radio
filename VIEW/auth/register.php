<!-- Register Page -->
<div class="auth-container">
    <div class="auth-card wide">
        <div class="auth-header">
            <h2>Créer un compte</h2>
            <p>Rejoignez Natiora Radio 98.2</p>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type'] === 'error' ? 'error' : 'success'; ?>">
                <i class="fas fa-<?php echo $_SESSION['flash_type'] === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                <?php echo htmlspecialchars($_SESSION['flash']); ?>
            </div>
            <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <form method="POST" action="/index.php?route=auth/register_post" class="auth-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="reg-name">
                        <i class="fas fa-user"></i> Nom complet
                    </label>
                    <input type="text" id="reg-name" name="name" placeholder="Votre nom" required>
                </div>

                <div class="form-group">
                    <label for="reg-email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="reg-email" name="email" placeholder="votre@email.com" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="reg-password">
                        <i class="fas fa-lock"></i> Mot de passe
                    </label>
                    <input type="password" id="reg-password" name="password" placeholder="Minimum 6 caractères" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="reg-password2">
                        <i class="fas fa-lock"></i> Confirmer
                    </label>
                    <input type="password" id="reg-password2" name="password2" placeholder="Répétez le mot de passe" required minlength="6">
                </div>
            </div>

            <button type="submit" class="btn-primary-full">
                <i class="fas fa-user-plus"></i> S'inscrire
            </button>
        </form>

        <div class="auth-footer">
            <p>Vous avez déjà un compte? <a href="/index.php?route=auth/login" class="link-accent">Se connecter</a></p>
        </div>
    </div>
</div>
