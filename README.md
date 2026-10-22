Manual PowerShell commands

Open the project folder in VS Code, choose Terminal → New Terminal, then run:


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

