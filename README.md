Manual PowerShell commands

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

