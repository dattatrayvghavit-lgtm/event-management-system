<?php
declare(strict_types=1);
if($_SERVER['REQUEST_METHOD']!=='POST') return;
if(!is_string($_POST['csrf']??null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) { http_response_code(419); exit('Session token expired or invalid. Reload the page and try again.'); }
$action=(string)($_POST['action']??'');
try {
    switch($action) {
    case 'signup':
        $name=field('name',2,100); $email=strtolower(field('email',3,190));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.');
        $password=(string)($_POST['password']??'');
        if(strlen($password)<10 || strlen($password)>72) throw new DomainException('Password must contain 10 to 72 bytes.');
        $roll=field('roll_no',1,30); $dept=field('department',2,100);
        try { query('INSERT INTO users(name,email,password_hash,roll_no,department) VALUES(?,?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),$roll,$dept]); }
        catch(PDOException $ex) { if($ex->getCode()==='23000') throw new DomainException('An account already uses that email or roll number.'); throw $ex; }
        flash('Account created. You can now sign in.'); go('login');
    case 'login':
        $email=strtolower(field('email',3,190)); $password=(string)($_POST['password']??'');
        $attempt=query('SELECT * FROM login_attempts WHERE email=?',[$email])->fetch();
        if($attempt && $attempt['blocked_until'] && strtotime($attempt['blocked_until'])>time()) throw new DomainException('Too many attempts. Try again in 15 minutes.');
        $u=query('SELECT * FROM users WHERE email=?',[$email])->fetch();
        if(!$u || !password_verify($password,$u['password_hash'])) {
            query('INSERT INTO login_attempts(email,failures,updated_at) VALUES(?,1,NOW()) ON DUPLICATE KEY UPDATE failures=IF(updated_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE),1,failures+1),updated_at=NOW()',[$email]);
            query('UPDATE login_attempts SET blocked_until=IF(failures>=5,DATE_ADD(NOW(),INTERVAL 15 MINUTE),NULL) WHERE email=?',[$email]);
            throw new DomainException('Email or password is incorrect.');
        }
        query('DELETE FROM login_attempts WHERE email=?',[$email]);
        session_regenerate_id(true); $_SESSION['uid']=$u['id']; $_SESSION['csrf']=bin2hex(random_bytes(32));
        go($u['role']==='admin'?'admin':'events');
    case 'logout':
        $_SESSION=[]; session_destroy(); go('login');
    case 'profile':
        $u=requireRole(); $name=field('name',2,100); $dept=field('department',2,100);
        $password=(string)($_POST['password']??'');
        if($password!=='') {
            $hash=query('SELECT password_hash FROM users WHERE id=?',[$u['id']])->fetchColumn();
            if(!password_verify((string)($_POST['current_password']??''),$hash)) throw new DomainException('Current password is incorrect.');
            if(strlen($password)<10 || strlen($password)>72) throw new DomainException('New password must contain 10 to 72 bytes.');
            query('UPDATE users SET name=?,department=?,password_hash=? WHERE id=?',[$name,$dept,password_hash($password,PASSWORD_DEFAULT),$u['id']]);
            session_regenerate_id(true);
        } else query('UPDATE users SET name=?,department=? WHERE id=?',[$name,$dept,$u['id']]);
        flash('Profile updated.'); go('profile');
    case 'save_event':
        $u=requireRole('admin');
        $id=empty($_POST['id'])?null:positiveId($_POST['id']);
        $title=field('title',3,150); $desc=field('description',10,10000); $venue=field('venue',2,150); $org=field('organizer',2,100);
        $category=(string)($_POST['category']??''); $status=(string)($_POST['status']??'');
        if(!in_array($category,CATEGORIES,true) || !in_array($status,['draft','published','cancelled'],true)) throw new DomainException('Invalid category or event status.');
        $capacity=filter_var($_POST['capacity']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100000]]);
        if(!$capacity) throw new DomainException('Capacity must be between 1 and 100000.');
        $dates=[];
        foreach(['starts_at','ends_at','registration_deadline'] as $k) {
            $raw=(string)($_POST[$k]??''); $d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$raw);
            if(!$d || $d->format('Y-m-d\TH:i')!==$raw) throw new DomainException('Enter valid event dates and times.');
            $dates[$k]=$d->format('Y-m-d H:i:s');
        }
        if($dates['ends_at']<=$dates['starts_at'] || $dates['registration_deadline']>$dates['starts_at']) throw new DomainException('End must follow start; registration deadline must be on or before start.');
        transaction(function() use($id,$title,$desc,$category,$dates,$venue,$org,$capacity,$status,$u) {
            $values=[$title,$desc,$category,$dates['starts_at'],$dates['ends_at'],$dates['registration_deadline'],$venue,$org,$capacity,$status];
            if($id) {
                eventById($id,true);
                $count=(int)query('SELECT COUNT(*) FROM registrations WHERE event_id=?',[$id])->fetchColumn();
                if($capacity<$count) throw new DomainException('Capacity cannot be less than the current registration count.');
                if($status==='draft' && $count>0) throw new DomainException('An event with registrations cannot become a draft. Cancel it instead.');
                query('UPDATE events SET title=?,description=?,category=?,starts_at=?,ends_at=?,registration_deadline=?,venue=?,organizer=?,capacity=?,status=? WHERE id=?',[...$values,$id]);
            } else query('INSERT INTO events(title,description,category,starts_at,ends_at,registration_deadline,venue,organizer,capacity,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)',[...$values,$u['id']]);
        });
        flash('Event saved.'); go('admin');
    case 'delete_event':
        requireRole('admin'); $id=positiveId($_POST['id']??null);
        transaction(function() use($id) {
            eventById($id,true);
            if((int)query('SELECT COUNT(*) FROM registrations WHERE event_id=?',[$id])->fetchColumn()>0) throw new DomainException('This event has registrations. Set its status to cancelled to preserve attendance records.');
            query('DELETE FROM events WHERE id=?',[$id]);
        });
        flash('Event deleted.'); go('admin');
    case 'register':
        $u=requireRole('student'); $id=positiveId($_POST['id']??null);
        transaction(function() use($id,$u) {
            $event=eventById($id,true);
            if($event['status']!=='published' || strtotime($event['registration_deadline'])<=time() || strtotime($event['starts_at'])<=time()) throw new DomainException('Registration is closed for this event.');
            if(query('SELECT id FROM registrations WHERE event_id=? AND student_id=?',[$id,$u['id']])->fetch()) throw new DomainException('You are already registered for this event.');
            if((int)query('SELECT COUNT(*) FROM registrations WHERE event_id=?',[$id])->fetchColumn()>=(int)$event['capacity']) throw new DomainException('This event is full.');
            query('INSERT INTO registrations(event_id,student_id) VALUES(?,?)',[$id,$u['id']]);
        });
        flash('Registration confirmed. Find it in My registrations.'); go('my');
    case 'cancel_registration':
        $u=requireRole('student'); $id=positiveId($_POST['id']??null);
        transaction(function() use($id,$u) {
            $event=eventById($id,true);
            if(strtotime($event['starts_at'])<=time()) throw new DomainException('You cannot withdraw after the event has started.');
            query('DELETE FROM registrations WHERE event_id=? AND student_id=?',[$id,$u['id']]);
        });
        flash('Registration withdrawn.'); go('my');
    case 'attendance':
        $u=requireRole('admin'); $id=positiveId($_POST['id']??null);
        transaction(function() use($id,$u) {
            $event=eventById($id,true);
            if($event['status']!=='published' || strtotime($event['starts_at'])>time()) throw new DomainException('Attendance opens when a published event starts.');
            $marks=$_POST['marks']??[];
            if(!is_array($marks)) throw new DomainException('Invalid attendance data.');
            foreach($marks as $rid=>$mark) {
                if(!in_array($mark,['present','absent','unmarked'],true)) throw new DomainException('Invalid attendance value.');
                query('UPDATE registrations SET attendance=?,marked_by=?,marked_at=NOW() WHERE id=? AND event_id=?',[$mark,$u['id'],positiveId($rid),$id]);
            }
        });
        flash('Attendance saved.'); go('attendees',['id'=>$id]);
    default: throw new DomainException('Unknown action.');
    }
} catch(DomainException $ex) {
    flash($ex->getMessage(),'error');
    $return=match($action) {'signup'=>'signup','login'=>'login','profile'=>'profile','register'=>'events','cancel_registration'=>'my','attendance'=>'attendees','save_event'=>'edit',default=>'admin'};
    // Keep non-secret form inputs for correction. Never retain passwords or tokens.
    if(in_array($action,['signup','save_event','profile'],true)) $_SESSION['old']=array_diff_key($_POST,array_flip(['password','current_password','csrf','action']));
    go($return, !empty($_POST['id'])?['id'=>(int)$_POST['id']]:[]);
}
