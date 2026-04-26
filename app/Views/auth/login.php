<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — AIRMan ERP</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:'Inter',system-ui,sans-serif;
  background:linear-gradient(135deg,#1e1b4b 0%,#312e81 50%,#4c1d95 100%);
  display:flex;align-items:center;justify-content:center;padding:20px;}
.login-card{background:#fff;border-radius:20px;padding:40px;width:100%;max-width:420px;box-shadow:0 25px 60px rgba(0,0,0,.35);}
.brand{text-align:center;margin-bottom:32px;}
.brand-logo{width:64px;height:64px;background:linear-gradient(135deg,#4f46e5,#7c3aed);
  border-radius:18px;display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:1.8rem;font-weight:900;margin:0 auto 14px;box-shadow:0 10px 24px rgba(79,70,229,.4);}
.brand h1{font-size:1.5rem;font-weight:800;color:#1e1b4b;margin:0;}
.brand p{color:#6b7280;font-size:.85rem;margin:4px 0 0;}
.form-label{font-size:.82rem;font-weight:600;color:#374151;}
.form-control{border:1.5px solid #e5e7eb;border-radius:10px;padding:10px 14px;font-size:.9rem;transition:.2s;}
.form-control:focus{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.12);outline:none;}
.input-group-text{border:1.5px solid #e5e7eb;border-radius:10px 0 0 10px;background:#f8fafc;color:#6b7280;}
.input-group .form-control{border-radius:0 10px 10px 0;}
.btn-login{background:linear-gradient(135deg,#4f46e5,#7c3aed);border:none;border-radius:10px;
  padding:12px;font-size:.95rem;font-weight:700;color:#fff;width:100%;transition:.2s;cursor:pointer;}
.btn-login:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(79,70,229,.4);}
.alert{border-radius:10px;font-size:.85rem;border:none;}
.version-badge{text-align:center;margin-top:20px;font-size:.75rem;color:rgba(255,255,255,.5);}
</style>
</head><body>
<div>
  <div class="login-card">
    <div class="brand">
      <div class="brand-logo">A</div>
      <h1>AIRMan ERP</h1>
      <p>Enterprise Suite v3.1</p>
    </div>

    <?php if ($error = $error ?? null): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success = $success ?? null): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="/login">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-envelope"></i></span>
          <input type="email" name="email" class="form-control" placeholder="admin@example.com" required autofocus value="<?= e($_POST['email']??'') ?>">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="fas fa-lock"></i></span>
          <input type="password" name="password" class="form-control" id="pwdInput" placeholder="••••••••" required>
          <button type="button" class="btn btn-outline-secondary" onclick="togglePwd()" style="border-radius:0 10px 10px 0;border:1.5px solid #e5e7eb;border-left:0"><i class="fas fa-eye" id="pwdIcon"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i>Sign In</button>
    </form>
  </div>
  <div class="version-badge">AIRMan ERP v3.1 · AirPharma Group</div>
</div>
<script>
function togglePwd() {
  const inp = document.getElementById('pwdInput');
  const ico = document.getElementById('pwdIcon');
  inp.type = inp.type==='password' ? 'text' : 'password';
  ico.className = inp.type==='password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body></html>
