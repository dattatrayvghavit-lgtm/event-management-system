<?php
// CLI-only view smoke test using explicit fixture data; NOT a database/E2E test.
if(PHP_SAPI!=='cli') exit(1);
date_default_timezone_set('Asia/Kolkata');
$_SESSION=['csrf'=>'fixture-token'];
function e(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function user(): ?array { return null; }
function csrf(): string { return '<input type="hidden" name="csrf" value="fixture-token">'; }
function dateLabel(string $d): string { return date('d M Y, h:i A',strtotime($d)); }
const CATEGORIES=['Workshop','Seminar','Cultural','Competition','Sports'];
final class FixtureStatement {
    public function fetchColumn(): int { return 3; }
    public function fetchAll(): array {
        $out=[];
        foreach(['Build your first web app','Campus cultural evening','Code sprint 2027'] as $i=>$name) $out[]=['id'=>$i+1,'title'=>$name,'category'=>CATEGORIES[[0,2,3][$i]],'starts_at'=>'2026-11-08 10:00:00','venue'=>['Computer Lab 1','College Auditorium','Computer Lab 2'][$i],'capacity'=>60,'registered'=>7];
        return $out;
    }
}
function query(string $sql,array $params=[]): FixtureStatement { return new FixtureStatement; }
foreach(['events','login','signup'] as $page) {
    ob_start(); require __DIR__.'/../app/views.php'; $content=ob_get_clean();
    ob_start(); require __DIR__.'/../app/layout.php'; $html=ob_get_clean();
    if(!str_contains($html,'</html>') || !str_contains($html,'CCSIT')) throw new RuntimeException("Incomplete $page view");
    if($page==='events' && substr_count($html,'class="event-card"')!==3) throw new RuntimeException('Event card rendering failed');
    if($page!=='events' && !str_contains($html,'name="csrf"')) throw new RuntimeException('Auth form missing CSRF');
    if(isset($argv[1]) && $page==='events') file_put_contents($argv[1],$html);
    echo "PASS: $page view rendered using fixtures\n";
}
