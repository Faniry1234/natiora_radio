<!-- Modern Login Modal -->
<div id="loginModal" class="modal modal-enhanced">
    <div class="modal-content modal-content-enhanced">
        <span class="close close-enhanced">&times;</span>
        <div class="modal-backdrop"></div>
        <div class="modal-body">
            <div class="auth-card-modal">
                <div class="auth-header-modal">
                    <div class="auth-icon-wrapper">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <h2>Connexion Natiora</h2>
                    <p>Accédez à votre compte radio</p>
                </div>

                <?php if (!empty($_SESSION['flash']) && $_SESSION['flash_type'] === 'error'): ?>
                    <div class="alert alert-error alert-enhanced">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['flash']); ?>
                    </div>
                    <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <form method="POST" action="/index.php?route=auth/login_post" class="auth-form auth-form-enhanced">
                    <div class="form-group form-group-enhanced">
                        <label for="login-email">
                            <i class="fas fa-envelope"></i> Adresse email
                        </label>
                        <input type="email" id="login-email" name="email" placeholder="vous@example.com" required>
                        <div class="input-underline"></div>
                    </div>

                    <div class="form-group form-group-enhanced">
                        <label for="login-password">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <input type="password" id="login-password" name="password" placeholder="••••••" required>
                        <div class="input-underline"></div>
                    </div>

                    <div class="form-group form-group-enhanced">
                        <label style="font-weight:600; font-size:0.95em;">
                            <input type="checkbox" name="remember" value="1"> Se souvenir de moi
                        </label>
                    </div>

                    <button type="submit" class="btn-modal-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Se connecter</span>
                    </button>
                </form>

                <div class="auth-divider">ou</div>

                <div class="auth-links-modal">
                    <a href="/index.php?route=auth/register" class="link-secondary">
                        <i class="fas fa-user-plus"></i> Créer un compte
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modal-enhanced {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    animation: fadeInBg 0.3s ease;
    backdrop-filter: blur(4px);
}

.modal-content-enhanced {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    width: 90%;
    max-width: 420px;
    animation: slideInUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    z-index: 1001;
}

.auth-card-modal {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.auth-card-modal::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f5576c);
}

.auth-header-modal {
    text-align: center;
    margin-bottom: 30px;
}

.auth-icon-wrapper {
    width: 60px;
    height: 60px;
    margin: 0 auto 15px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8em;
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.auth-header-modal h2 {
    margin: 0 0 5px;
    color: #333;
    font-size: 1.8em;
}

.auth-header-modal p {
    margin: 0;
    color: #999;
    font-size: 0.95em;
}

.form-group-enhanced {
    margin-bottom: 20px;
    position: relative;
}

.form-group-enhanced label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #555;
    font-size: 0.95em;
}

.form-group-enhanced label i {
    color: #667eea;
}

.form-group-enhanced input {
    width: 100%;
    padding: 12px 0 12px 0;
    background: transparent;
    border: none;
    border-bottom: 2px solid #e0e0e0;
    font-size: 1em;
    color: #333;
    transition: all 0.3s ease;
}

.form-group-enhanced input:focus {
    outline: none;
    border-bottom-color: #667eea;
    background: rgba(102, 126, 234, 0.02);
}

.input-underline {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 2px;
    width: 0;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transition: width 0.3s ease;
}

.form-group-enhanced input:focus ~ .input-underline {
    width: 100%;
}

.btn-modal-login {
    width: 100%;
    padding: 13px 20px;
    margin-top: 25px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 1.05em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.btn-modal-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-modal-login:active {
    transform: translateY(0);
}

.auth-divider {
    text-align: center;
    margin: 20px 0;
    color: #ccc;
    font-size: 0.85em;
    position: relative;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    position: absolute;
    top: 50%;
    width: 40%;
    height: 1px;
    background: #e0e0e0;
}

.auth-divider::before {
    left: 0;
}

.auth-divider::after {
    right: 0;
}

.auth-links-modal {
    text-align: center;
}

.link-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.95em;
}

.link-secondary:hover {
    color: #764ba2;
    gap: 12px;
}

.close-enhanced {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #999;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-enhanced:hover {
    color: #f5576c;
    background: rgba(245, 87, 108, 0.1);
}

.alert-enhanced {
    margin-bottom: 20px;
    padding: 12px 15px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95em;
}

@keyframes fadeInBg {
    from { background: rgba(0,0,0,0); }
    to { background: rgba(0,0,0,0.7); }
}

@keyframes slideInUp {
    from { 
        transform: translate(-50%, -30%) scale(0.9);
        opacity: 0;
    }
    to { 
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}

@media (max-width: 600px) {
    .modal-content-enhanced {
        width: 95%;
        max-width: none;
    }
    
    .auth-card-modal {
        padding: 30px 20px;
        border-radius: 15px;
    }
    
    .auth-header-modal h2 {
        font-size: 1.5em;
    }
}
</style>

<script>
(function() {
    const modal = document.getElementById('loginModal');
    const closeBtn = modal.querySelector('.close-enhanced');
    const openBtn = document.querySelector('.open-login');

    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'block';
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Auto-open if needed
    <?php if (!empty($_SESSION['open_login_modal'])): ?>
        modal.style.display = 'block';
        <?php unset($_SESSION['open_login_modal']); ?>
    <?php endif; ?>
})();
</script>

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
