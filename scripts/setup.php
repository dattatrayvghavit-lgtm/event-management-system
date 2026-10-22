<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../app/bootstrap.php';
try {
    $name=setting('db_name','college_events');
    if(!preg_match('/^[a-zA-Z0-9_]+$/',$name)) throw new RuntimeException('Use letters, numbers and underscores for DB_NAME.');
    $server=new PDO('mysql:host='.setting('db_host','127.0.0.1').';port='.setting('db_port','3306').';charset=utf8mb4',setting('db_user','root'),setting('db_pass',''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $server->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    db()->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
    if((int)query('SELECT COUNT(*) FROM users')->fetchColumn()>0) { echo "Schema checked. Existing data preserved; demo seed skipped.\n"; exit; }
    transaction(function() {
        $s=db()->prepare('INSERT INTO users(name,email,password_hash,role,roll_no,department) VALUES(?,?,?,?,?,?)');
        $s->execute(['Event Administrator','admin@college.test',password_hash('Admin@12345',PASSWORD_DEFAULT),'admin',null,'Administration']);
        $admin=(int)db()->lastInsertId();
        $s->execute(['Demo Student','student@college.test',password_hash('Student@12345',PASSWORD_DEFAULT),'student','DEMO-01','BCA']);
        $student=(int)db()->lastInsertId();
        $events=[['Build your first web app','Workshop',7,'Computer Lab 1','Computer Science Department','A hands-on introduction to HTML, CSS and PHP. Bring your curiosity and build a working web page.',60],['Campus cultural evening','Cultural',14,'College Auditorium','Cultural Committee','Celebrate student talent with music, dance and theatre. Join an evening created by our campus community.',200],['Code sprint 2027','Competition',21,'Computer Lab 2','Coding Club','Solve programming challenges, collaborate with classmates and put your problem-solving skills to the test.',40],['Career conversations','Seminar',10,'Seminar Hall','Placement Cell','Explore careers in technology with an interactive session on portfolios, interviews and industry expectations.',100],['Interclass badminton','Sports',18,'Sports Complex','Sports Committee','Represent your class in a friendly badminton tournament. Open to students across all departments.',32],['Introduction to open source','Seminar',-2,'Seminar Hall','Coding Club','A completed sample event for demonstrating attendance and reports.',50]];
        foreach($events as [$title,$cat,$days,$venue,$org,$desc,$cap]) {
            $day=(new DateTimeImmutable("$days days"))->setTime(10,0);
            query('INSERT INTO events(title,description,category,starts_at,ends_at,registration_deadline,venue,organizer,capacity,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?)',[$title,$desc,$cat,$day->format('Y-m-d H:i:s'),$day->modify('+3 hours')->format('Y-m-d H:i:s'),$day->modify('-1 day')->format('Y-m-d H:i:s'),$venue,$org,$cap,'published',$admin]);
            if($days===-2) query('INSERT INTO registrations(event_id,student_id) VALUES(?,?)',[db()->lastInsertId(),$student]);
        }
    });
    echo "Database ready. Demo accounts: admin@college.test / Admin@12345 and student@college.test / Student@12345\n";
} catch(Throwable $ex) { fwrite(STDERR,"Setup failed: ".$ex->getMessage()."\n"); exit(1); }
