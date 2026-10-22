<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
try {
    require __DIR__.'/../app/actions.php';
    $page=(string)($_GET['page']??'events');
    $adminPages=['admin','edit','attendees','reports','export','students'];
    if(in_array($page,$adminPages,true)) requireRole('admin');
    if(in_array($page,['my','profile'],true)) requireRole();
    if($page==='export') {
        $id=positiveId($_GET['id']??null); eventById($id);
        $rows=query('SELECT u.name,u.email,u.roll_no,u.department,r.registered_at,r.attendance,r.marked_at FROM registrations r JOIN users u ON u.id=r.student_id WHERE r.event_id=? ORDER BY u.name',[$id])->fetchAll();
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="event-'.$id.'-attendance.csv"');
        $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
        fputcsv($out,['Name','Email','Roll number','Department','Registered at (IST)','Attendance','Marked at (IST)']);
        foreach($rows as $row) { foreach($row as &$cell) { $cell=(string)($cell??''); if(preg_match('/^[\s]*[=+@-]/u',$cell) || preg_match('/^[\t\r\n]/',$cell)) $cell="'".$cell; } unset($cell); fputcsv($out,$row); } fclose($out); exit;
    }
    ob_start(); require __DIR__.'/../app/views.php'; $content=ob_get_clean();
    require __DIR__.'/../app/layout.php';
} catch(DomainException $ex) {
    if(ob_get_level()) ob_end_clean(); http_response_code(404); echo '<h1>Record unavailable</h1><p>'.e($ex->getMessage()).'</p><a href="index.php">Back to events</a>';
} catch(Throwable $ex) {
    if(ob_get_level()) ob_end_clean(); error_log((string)$ex); http_response_code(500);
    echo '<h1>Unable to load the application</h1><p>Check the database service and config.php, then run scripts/setup.php as described in README.md. Detailed errors are in the PHP server log.</p>';
}
