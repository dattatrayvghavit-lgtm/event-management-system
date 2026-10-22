# College Event Management System

**College of Computer Science and Information Technology, Latur**  
Affiliated to Swami Ramanand Teerth Marathwada University, Nanded  
BCA-TY capstone, academic year **2026–2027**

Students: Khatile Vaishnavi Balasaheb (A-52), Dumane Uma Baliram (A-18), Jadhav Namrata Shiwaji (A-25).  
Project guide: Mr. B. M. Sontakke · HOD: Dr. V. V. Bhosle.

## Start here

This is a complete source-code implementation of the five modules in the supplied synopsis. It uses **HTML, CSS, JavaScript, PHP and MySQL-compatible SQL**. No Angular, Java, .NET, npm build or Composer installation is required. PHP executes the source directly.

**Verification status:** PHP syntax checks and fixture-based rendering checks passed. The live database/HTTP end-to-end tests could not run in the authoring workspace because local network sockets were blocked. Docker was not built or tested here. Run the included E2E test and the checklist below on your computer before presenting the project. See `docs/TEST-REPORT.md` for the exact evidence and limitations.

Choose **one** startup route. Route A is simplest for a Windows college demo. A first-time installation commonly needs about 20–40 minutes; an already configured machine usually needs only a few minutes. These are setup estimates, not a guarantee.

## Route A — Windows with XAMPP (recommended)

1. Install XAMPP from https://www.apachefriends.org/ if needed. Use PHP 8.2 or newer with `pdo_mysql` and `mbstring` enabled. XAMPP includes **MariaDB**, which is MySQL-compatible; this project supports both. If the college specifically requires Oracle MySQL, use Route B or an existing MySQL server.
2. Extract `College-Event-Management-System.zip` to a normal folder such as `C:\Projects`. The project directory should be `C:\Projects\college-events` and contain `README.md`, `START-LOCAL.cmd`, `public`, etc. Do not run from inside the ZIP.
3. Open XAMPP Control Panel and click **Start next to MySQL**. Apache is not needed: this project uses PHP’s local development server.
4. If your local database has a non-default port, username or password, copy `config.example.php` to `config.php`, then edit its values. For an untouched XAMPP installation, the default values usually work: host `127.0.0.1`, port `3306`, database `college_events`, user `root`, password empty.
5. Double-click **START-LOCAL.cmd**. It copies the default configuration if missing, creates the database/tables, adds demo data on a fresh database, and starts the application. If setup fails, read the message; it will not launch the site until setup succeeds.
6. Open **http://127.0.0.1:8000** in your browser. Keep the command window open.
7. Stop the app with **Ctrl+C** in that window. Your data stays in MySQL. Restart by running the same file again.

The launcher assumes PHP is at `C:\xampp\php\php.exe`. If your XAMPP path differs, use the commands below with your actual path.

### Manual PowerShell commands

Open the project folder in VS Code, choose Terminal → New Terminal, then run:

```powershell
cd C:\Projects\college-events
Copy-Item config.example.php config.php
# Edit config.php if your database credentials differ.
& 'C:\xampp\php\php.exe' scripts\setup.php
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8000 -t public
```

Do the copy only on your first setup; do not overwrite your edited config on later runs. Start XAMPP MySQL before these commands. `setup.php` preserves existing data and skips demo seeding if any user already exists. It is not a schema migration system for future application upgrades.

**Do not double-click `index.php` or use VS Code Live Server.** They cannot execute PHP and connect it to MySQL. Always use the URL above. Do not serve the entire project root: only `public` belongs in the web document root, so configuration and setup scripts stay outside it.

## Database connection / “DB string”

The application uses PDO. Equivalent default connection string:

```text
mysql:host=127.0.0.1;port=3306;dbname=college_events;charset=utf8mb4
```

Username and password are separate values, not appended to the string. Local config example:

```php
<?php
return [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'college_events',
    'db_user' => 'root',
    'db_pass' => '',
];
```

Environment variables `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` override the config file. There is no automatic `.env` loader. For example, in the same PowerShell terminal used to run setup and the server:

```powershell
$env:DB_PORT='3307'
$env:DB_PASS='your-local-password'
```

`database/schema.sql` contains the tables and relationships. `scripts/setup.php` creates the named database and imports this SQL automatically; you do **not** also need to manually import it in phpMyAdmin. The setup user needs permission to create the database/tables. For public deployment use a separate restricted runtime database user, not root.

## Route B — Docker with real MySQL

Install and start Docker Desktop with Linux containers. Open a terminal in the project directory:

```powershell
docker compose up -d --build
docker compose exec web php scripts/setup.php
```

Open **http://127.0.0.1:8000**. The database is MySQL 8.4, available to the web container as host `db`. The web container has PHP, `pdo_mysql` and `mbstring`; no local PHP/MySQL installation is needed. Demo credentials below are unchanged. Docker database credentials are in `compose.yaml` and are local-demo values only.

```powershell
docker compose logs web
docker compose logs db
docker compose down
```

`down` stops the containers but keeps data in the named volume. **Do not add `-v` unless you intentionally want to erase the demo database.** Code is copied into the image: after editing PHP/CSS/JS, run `docker compose up -d --build` again. If port 8000 is occupied, change the left side of `127.0.0.1:8000:8000` to 8001 and browse port 8001.

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Administrator | admin@college.test | Admin@12345 |
| Student | student@college.test | Student@12345 |

These are local sample credentials. Change passwords from **Profile** before sharing access. `.test` addresses do not receive email. Students can create their own account; registration never grants admin privileges. There is no email delivery or password-reset module in this version.

Fresh setup creates five future events relative to the setup date and one past event. The demo student is already registered for the past event so you can demonstrate attendance immediately. Events do not automatically move forward on later startups.

## End-to-end manual demonstration (about 10–15 minutes)

1. Sign in as administrator. Open **Dashboard → Create event**. Enter a title, description, category, venue, organizer and capacity 2. Choose tomorrow for start, a later end, and a registration deadline after now but before the start. Set **Published**, then Save.
2. Open a different browser or an incognito window. Choose **Join campus**, create a student with a unique email and roll number, then sign in.
3. Search for the event, open it and click **Register**. Check **My registrations**. Reload or return to the event: it should show that you are already registered.
4. Return to the admin browser. Open **Attendees** for the event. The student must appear with attendance **Unmarked**. CSV should download their registration.
5. Before the event starts, use the student browser to **Withdraw**, verify the admin attendee list is empty, and register again.
6. For an immediate attendance demo, use the seeded **Introduction to open source** past event, which already has the demo student. Open Attendees, choose **Present**, and Save. Sign in with the demo student in the student browser and confirm the result in My registrations.
7. Open **Reports**. Verify registered/present/absent/unmarked counts. Download CSV and open it in Excel. Print report uses the browser print dialog; choose Save as PDF if needed.
8. Edit your future event and set status **Cancelled**. It disappears from public discovery, but its registered students retain its cancelled history.
9. Create a draft without registrations, then delete it. Trying to delete an event with registrations must be blocked.
10. As a student, visit `http://127.0.0.1:8000/index.php?page=admin`. It must show **Access denied**.

All displayed and entered event times use **India Standard Time (Asia/Kolkata)**.

## Automated tests

Use a **disposable local demo database**. Tests create two students and an event with attendance; those records are retained for inspection. Tests assume demo account passwords are unchanged. They never delete existing user data.

Install Node.js 18+ if you want to run the HTTP test suite. Node is only a test dependency, not an application dependency. Keep the PHP server running, open another terminal in the project folder and run:

```powershell
node tests\e2e.mjs
```

For another port:

```powershell
$env:BASE_URL='http://127.0.0.1:8001'
node tests\e2e.mjs
```

A successful run prints numbered `PASS` lines and a final count. Any failure exits with a nonzero code. The suite exercises accounts, role restrictions, CSRF, event creation/update/deletion, duplicate and full-capacity registration, withdrawal, attendance, CSV, reports and password changes.

Additional checks:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
& 'C:\xampp\php\php.exe' tests\render-smoke.php
```

The renderer uses fixture data for public and authentication templates. It does **not** establish that the real database workflow works. See `docs/TEST-REPORT.md`.

## What is implemented

| Synopsis module | Implementation |
|---|---|
| Admin management | Login, role checks, dashboard, event CRUD, student directory |
| Event management | Category, description, date/time, organizer, venue, capacity, draft/published/cancelled status, search |
| Student management | Signup, login/logout, profile, password change |
| Event registration | Registration, unique student/event constraint, capacity check, deadline check, withdrawal, personal history |
| Attendance and reports | Per-student attendance, attendance status in student account, totals, CSV exports, print report |

## Project structure

```text
app/bootstrap.php        Database, sessions, shared helpers
app/actions.php          Validated POST workflows and permission checks
app/views.php            Page templates and read queries
app/layout.php           Shared navigation, footer and notification layout
public/index.php        Request entry point and CSV responses
public/assets/          Responsive CSS and small JavaScript helpers
database/schema.sql     MySQL/MariaDB table definitions
scripts/setup.php       CLI-only schema installation and demo seed
tests/                  HTTP E2E suite and fixture renderer
docs/                   Architecture, presentation guide and test report
config.example.php      Configuration template
START-LOCAL.cmd         Windows first-run launcher
Dockerfile/compose.yaml Optional container setup
```

## Troubleshooting

| Problem | What to do |
|---|---|
| Connection refused / cannot load app | Start MySQL, confirm port and `config.php`, then run setup again. Read the PHP terminal log. |
| Access denied for database user | Correct `db_user`/`db_pass`; these are database credentials, not website account credentials. |
| Unknown database / missing table | Run `scripts/setup.php` against the same configuration used by the server. |
| Could not find driver | Enable `extension=pdo_mysql` in the PHP CLI `php.ini`; restart PHP. Use `php --ini` to identify the loaded file. |
| Undefined mb_strlen | Enable PHP `mbstring`; restart. Dockerfile includes it. |
| Address already in use | Stop the old development server or run on 8001 and open the matching URL. |
| Styles missing | Start with `-t public` and open the server URL, not a file URL. |
| Session token invalid | Refresh the form. Signing out in another tab invalidates earlier forms. |
| Registration closed | Check deadline, event start, status and machine clock. |
| Attendance unavailable | Event must be published and already started. Use the seeded past event. |
| Too many sign-in attempts | Wait 15 minutes after five incorrect attempts for that email. |
| E2E test cannot connect | Confirm BASE_URL and keep the app running in another terminal. |

## Scope and deployment notes

This delivery targets a **local academic demonstration**. There are no paid services, external fonts, uploads, QR codes, SMS/email, payment, certificates or separate organizer role. Admins manually mark attendance. Server-side validation, prepared statements, hashed passwords, CSRF tokens, output escaping, session regeneration and login throttling are included.

Before public hosting, use a supported patched PHP/MySQL installation, HTTPS, an Apache/Nginx/PHP-FPM deployment, restricted database credentials, managed secrets, backups, monitoring and an additional security review. PHP’s built-in server and the included demo passwords are for local development. Public signup currently accepts any valid email and unique roll number; real college deployment needs a student verification process.

Reference documentation: https://www.php.net/manual/en/ · https://dev.mysql.com/doc/ · https://developer.mozilla.org/ · https://www.apachefriends.org/
