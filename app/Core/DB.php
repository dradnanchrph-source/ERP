<?php
class DB {
    private static ?PDO $pdo = null;
    private static array $cfg = [];

    public static function config(array $c): void { self::$cfg = $c; }

    public static function pdo(): PDO {
        if (self::$pdo) return self::$pdo;
        $c = self::$cfg;
        if (!$c) $c = require ROOT . '/app/config.php';
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
            $c['db_host'], $c['db_name']);
        self::$pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return self::$pdo;
    }

    public static function q(string $sql, array $p = []): \PDOStatement {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        return $st;
    }
    public static function all(string $sql, array $p = []): array  { return self::q($sql,$p)->fetchAll(); }
    public static function row(string $sql, array $p = []): ?object { return self::q($sql,$p)->fetch() ?: null; }
    public static function val(string $sql, array $p = []): mixed   { return self::q($sql,$p)->fetchColumn(); }
    public static function id(): string { return self::pdo()->lastInsertId(); }

    public static function insert(string $t, array $d): string {
        $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($d)));
        $vals = implode(',', array_fill(0, count($d), '?'));
        self::q("INSERT INTO `$t` ($cols) VALUES ($vals)", array_values($d));
        return self::id();
    }

    public static function update(string $t, array $d, string $w, array $wp = []): void {
        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($d)));
        self::q("UPDATE `$t` SET $set WHERE $w", [...array_values($d), ...$wp]);
    }

    public static function delete(string $t, string $w, array $p = []): void {
        self::q("DELETE FROM `$t` WHERE $w", $p);
    }

    public static function page(string $sql, array $p, int $pg, int $pp = 25): array {
        $total  = (int)self::val("SELECT COUNT(*) FROM ($sql) _cnt", $p);
        $offset = ($pg - 1) * $pp;
        $rows   = self::all("$sql LIMIT $pp OFFSET $offset", $p);
        return [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $pg,
            'per_page' => $pp,
            'pages'    => (int)ceil($total / $pp),
            'from'     => $offset + 1,
            'to'       => min($offset + $pp, $total),
        ];
    }
}
