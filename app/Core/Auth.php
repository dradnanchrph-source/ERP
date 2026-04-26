<?php
class Auth {
    public static function check(): bool   { return !empty($_SESSION['user_id']); }
    public static function id(): int       { return (int)($_SESSION['user_id'] ?? 0); }
    public static function name(): string  { return $_SESSION['user_name'] ?? 'Guest'; }
    public static function role(): int     { return (int)($_SESSION['role_id'] ?? 0); }
    public static function bizId(): int    { return (int)($_SESSION['business_id'] ?? 1); }
    public static function locId(): int    { return (int)($_SESSION['location_id'] ?? 1); }
    public static function isAdmin(): bool { return self::role() === 1; }

    public static function require(): void {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: /login'); exit;
        }
    }

    public static function login(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_name']   = $user['name'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['role_id']     = $user['role_id'];
        $_SESSION['business_id'] = $user['business_id'] ?? 1;
        $_SESSION['location_id'] = $user['location_id'] ?? 1;
        $_SESSION['avatar']      = $user['avatar'] ?? null;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
        }
        session_destroy();
    }

    public static function can(string $module, string $action='view'): bool {
        if (self::isAdmin()) return true;
        return !empty($_SESSION['permissions'][$module][$action]);
    }

    public static function csrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): bool {
        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
