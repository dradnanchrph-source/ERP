<?php
class Controller {
    protected int    $bizId;
    protected int    $locId;
    protected int    $userId;

    public function __construct() {
        $this->bizId  = Auth::bizId();
        $this->locId  = Auth::locId();
        $this->userId = Auth::id();
    }

    protected function view(string $tpl, array $data=[]): void {
        extract($data);
        $contentFile = APP . "/Views/modules/$tpl.php";
        ob_start();
        if (file_exists($contentFile)) include $contentFile;
        else echo "<div class=\"alert alert-danger\">View not found: $tpl</div>";
        $content = ob_get_clean();
        include APP . '/Views/layout.php';
    }

    protected function json(bool $ok, string $msg='', mixed $data=null, int $code=200): never {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success'=>$ok,'message'=>$msg,'data'=>$data,'csrf'=>Auth::csrf()]);
        exit;
    }

    protected function redirect(string $url): never {
        header("Location: $url"); exit;
    }

    protected function requireAuth(): void { Auth::require(); }

    protected function post(string $key, mixed $default=null): mixed {
        return $_POST[$key] ?? $default;
    }
    protected function get(string $key, mixed $default=null): mixed {
        return $_GET[$key] ?? $default;
    }
    protected function isPost(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
    protected function isAjax(): bool { return !empty($_SERVER['HTTP_X_REQUESTED_WITH']); }

    protected function log(string $module, string $action, mixed $id=null): void {
        try {
            DB::insert('activity_logs', [
                'business_id' => $this->bizId,
                'user_id'     => $this->userId,
                'module'      => $module,
                'action'      => $action,
                'record_id'   => $id,
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch(Exception $e) {}
    }

    protected function paginate(int $default=25): array {
        return [
            'page'     => max(1, (int)($this->get('page', 1))),
            'per_page' => min(200, max(10, (int)($this->get('per_page', $default)))),
        ];
    }
}
