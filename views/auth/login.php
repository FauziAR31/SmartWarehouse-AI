<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title><?php echo isset($title) ? $title : APP_NAME; ?> - Login</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0d8abc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            font-family: 'Inter', sans-serif;
        }
        .login-wrapper {
            width: 100%;
            max-width: 440px;
            margin: auto;
        }
        .login-card {
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            overflow: hidden;
            background: #ffffff;
        }
        .login-header {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 2rem 1.5rem 1.75rem;
            text-align: center;
            position: relative;
        }
        .brand-logo-circle {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, #0D8ABC 0%, #0284c7 100%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(13, 138, 188, 0.4);
            margin-bottom: 1rem;
        }
        .login-body {
            padding: 2rem 1.75rem;
            background: #ffffff;
        }
        .demo-chip {
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.76rem;
            padding: 5px 10px;
            border-radius: 8px;
        }
        .demo-chip:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        @media (max-width: 480px) {
            .login-header {
                padding: 1.5rem 1.25rem;
            }
            .login-body {
                padding: 1.5rem 1.25rem;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="card login-card">
        <div class="login-header">
            <div class="brand-logo-circle">
                <i class="fas fa-warehouse fa-2x text-white"></i>
            </div>
            <h3 class="fw-bold mb-1 brand-font tracking-tight"><?php echo APP_NAME; ?></h3>
            <p class="mb-0 text-white-50 small">Warehouse Management System</p>
        </div>
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-3 small" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo BASE_URL; ?>/auth/login" method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::csrfToken(); ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Username / Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control border-start-0 bg-light" id="username" name="username" placeholder="Masukkan username atau email" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold text-secondary small text-uppercase" style="letter-spacing: 0.5px;">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control border-start-0 border-end-0 bg-light" id="password" name="password" placeholder="••••••••" required>
                        <button class="btn btn-light border border-start-0 text-muted" type="button" id="togglePasswordBtn" title="Lihat password">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                        <i class="fas fa-sign-in-alt me-2"></i>Masuk ke Sistem
                    </button>
                </div>
            </form>

            <!-- Quick Demo Helper (Mobile & Friendly) -->
            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted d-block mb-2 fw-semibold" style="font-size: 0.75rem;">
                    <i class="fas fa-bolt text-warning me-1"></i>Klik Akun Demo untuk Isi Otomatis:
                </small>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary demo-chip" onclick="fillLogin('admin', 'admin123')">
                        <i class="fas fa-user-shield me-1"></i>Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning demo-chip text-dark" onclick="fillLogin('supervisor', 'supervisor123')">
                        <i class="fas fa-user-check me-1"></i>Supervisor
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success demo-chip" onclick="fillLogin('operator', 'admin123')">
                        <i class="fas fa-dolly me-1"></i>Operator
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillLogin(u, p) {
    document.getElementById('username').value = u;
    document.getElementById('password').value = p;
    document.getElementById('password').focus();
}

// Toggle password visibility
const toggleBtn = document.getElementById('togglePasswordBtn');
const pwdInput = document.getElementById('password');
const pwdIcon = document.getElementById('togglePasswordIcon');

if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', function() {
        const isPassword = pwdInput.type === 'password';
        pwdInput.type = isPassword ? 'text' : 'password';
        pwdIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
    });
}
</script>
</body>
</html>
