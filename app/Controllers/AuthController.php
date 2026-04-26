<?php
class AuthController extends Controller {

    public function loginForm(): void {
        if (Auth::check()) { $this->redirect('/dashboard'); }
        $error   = get_flash('error');
        $success = get_flash('success');
        include APP . '/Views/auth/login.php';
        exit;
    }

    public function login(): void {
        $email    = trim($this->post('email',''));
        $password = $this->post('password','');

        if (!$email || !$password) {
            flash('error','Email and password are required.');
            $this->redirect('/login');
        }

        $user = DB::row(
            "SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1",
            [$email]
        );

        if (!$user) {
            flash('error','Invalid email or password.');
            $this->redirect('/login');
        }

        // Check lockout
        if (!empty($user->locked_until) && strtotime($user->locked_until) > time()) {
            flash('error','Account locked. Try again at '.date('H:i',strtotime($user->locked_until)));
            $this->redirect('/login');
        }

        if (!password_verify($password, $user->password)) {
            $attempts = ($user->login_attempts ?? 0) + 1;
            $upd = ['login_attempts' => $attempts];
            if ($attempts >= 5) $upd['locked_until'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            DB::update('users', $upd, 'id=?', [$user->id]);
            flash('error','Invalid email or password.');
            $this->redirect('/login');
        }

        // Success
        DB::update('users', [
            'last_login'     => date('Y-m-d H:i:s'),
            'last_ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
            'login_attempts' => 0,
            'locked_until'   => null,
        ], 'id=?', [$user->id]);

        Auth::login((array)$user);

        // Load permissions
        $perms = DB::all(
            "SELECT p.module, p.action FROM role_permissions rp
             JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=?",
            [$user->role_id]
        );
        $permMap = [];
        foreach ($perms as $p) $permMap[$p->module][$p->action] = true;
        $_SESSION['permissions'] = $permMap;

        $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
        unset($_SESSION['redirect_after_login']);
        $this->redirect($redirect);
    }

    public function logout(): void {
        Auth::logout();
        flash('success','You have been logged out.');
        $this->redirect('/login');
    }
}
