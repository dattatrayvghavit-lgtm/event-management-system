<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');
$local = is_file(__DIR__.'/../config.php') ? require __DIR__.'/../config.php' : [];
function setting(string $key, string $default): string {
    global $local;
    $v = getenv(strtoupper($key));
    return $v !== false ? $v : (string)($local[$key] ?? $default);
}
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = 'mysql:host='.setting('db_host','127.0.0.1').';port='.setting('db_port','3306').';dbname='.setting('db_name','college_events').';charset=utf8mb4';
        $pdo = new PDO($dsn, setting('db_user','root'), setting('db_pass',''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
        $pdo->exec("SET time_zone = '+05:30'");
    }
    return $pdo;
}
function query(string $sql, array $params=[]): PDOStatement {
    $s=db()->prepare($sql); $s->execute($params); return $s;
}
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $route='events', array $params=[]): never { header('Location: index.php?'.http_build_query(['page'=>$route]+$params)); exit; }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function user(): ?array {
    static $loaded=false, $u=null;
    if (!$loaded) { $u=isset($_SESSION['uid']) ? (query('SELECT id,name,email,role,roll_no,department FROM users WHERE id=?',[$_SESSION['uid']])->fetch() ?: null) : null; $loaded=true; }
    return $u;
}
function requireRole(?string $role=null): array {
    $u=user(); if (!$u) go('login');
    if ($role && $u['role']!==$role) { http_response_code(403); exit('Access denied. This action is not available for your role.'); }
    return $u;
}
function flash(string $message, string $type='success'): void { $_SESSION['flash']=[$message,$type]; }
function field(string $key, int $min, int $max): string {
    $v=trim((string)($_POST[$key]??''));
    if(mb_strlen($v)<$min || mb_strlen($v)>$max) throw new DomainException(ucwords(str_replace('_',' ',$key))." must contain $min to $max characters.");
    return $v;
}
function positiveId(mixed $value): int {
    $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if($id===false) throw new DomainException('Invalid record ID.'); return $id;
}
function eventById(int $id, bool $lock=false): array {
    $event=query('SELECT * FROM events WHERE id=?'.($lock?' FOR UPDATE':''),[$id])->fetch();
    if(!$event) throw new DomainException('Event not found.'); return $event;
}
function transaction(callable $fn): void {
    db()->beginTransaction(); try { $fn(); db()->commit(); } catch(Throwable $ex) { if(db()->inTransaction()) db()->rollBack(); throw $ex; }
}
function dateLabel(string $date): string { return date('d M Y, h:i A',strtotime($date)); }
const CATEGORIES=['Workshop','Seminar','Cultural','Competition','Sports'];
if(PHP_SAPI!=='cli') {
    ini_set('session.use_strict_mode','1');
    session_name('college_events');
    session_set_cookie_params(['httponly'=>true,'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off'),'samesite'=>'Lax','path'=>'/']);
    session_start();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    header('Cache-Control: no-store');
}
