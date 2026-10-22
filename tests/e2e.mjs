// Node.js 18+; run ONLY against your local demo/test database.
// Creates two student accounts and temporary events. Retains the accounts and
// a cancelled event with attendance as evidence; run against disposable data.
import assert from 'node:assert/strict';
const base = (process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '') + '/index.php';
const suffix = Date.now().toString();
let passed = 0;
function check(value, message) { assert.ok(value, message); console.log(`PASS ${++passed}: ${message}`); }
class Client {
  cookie = '';
  async request(page = 'events', data = null, extra = '') {
    const response = await fetch(`${base}?page=${page}${extra}`, {
      method: data ? 'POST' : 'GET', redirect: 'manual',
      headers: { ...(this.cookie ? {Cookie: this.cookie} : {}), ...(data ? {'Content-Type':'application/x-www-form-urlencoded'} : {}) },
      ...(data ? {body: new URLSearchParams(data)} : {})
    });
    const cookie = response.headers.get('set-cookie');
    if (cookie) this.cookie = cookie.split(';')[0];
    const text = await response.text();
    return {status: response.status, text, location: response.headers.get('location')};
  }
  async post(page, action, data = {}, extra = '') {
    const get = await this.request(page, null, extra);
    const csrf = get.text.match(/name="csrf" value="([^"]+)"/)?.[1];
    assert.ok(csrf, `Missing CSRF on ${page}: HTTP ${get.status}. Is setup complete?`);
    const result = await this.request(page, {csrf, action, ...data}, extra);
    if (result.status === 302) {
      const url = new URL(result.location, base);
      return this.request(url.searchParams.get('page') || 'events', null, url.searchParams.has('id') ? `&id=${url.searchParams.get('id')}` : '');
    }
    return result;
  }
}
const admin = new Client(), student = new Client(), other = new Client(), guest = new Client();
const time = (offsetHours) => {
  // datetime-local input represents India time. Explicitly shift UTC for IST.
  return new Date(Date.now() + (offsetHours + 5.5) * 3600000).toISOString().slice(0,16);
};
try {
  let r = await guest.request(); check(r.status === 200 && r.text.includes('What’s happening'), 'Public event listing loads');
  r = await guest.request('admin'); check(r.status === 302 && r.location.includes('login'), 'Anonymous admin access redirects to login');
  r = await guest.request('login', {action:'login', csrf:'invalid'}); check(r.status === 419, 'Invalid CSRF rejected');
  r = await admin.post('login','login',{email:'admin@college.test',password:'Admin@12345'}); check(r.text.includes('Event dashboard'), 'Administrator login succeeds');
  for (const [client, n] of [[student,1],[other,2]]) {
    r = await client.post('signup','signup',{name:`Test Student ${n}`,email:`e2e-${suffix}-${n}@college.test`,password:'TestPass@12345',roll_no:`T-${suffix}-${n}`,department:'BCA',role:'admin'});
    check(r.text.includes('Account created'), `Student ${n} signup succeeds`);
    r = await client.post('login','login',{email:`e2e-${suffix}-${n}@college.test`,password:'TestPass@12345'});
    check(r.text.includes('My registrations'), `Student ${n} login succeeds`);
  }
  r = await student.request('admin'); check(r.status === 403, 'Student cannot access dashboard even with forged signup role');
  r = await student.request('export',null,'&id=1'); check(r.status === 403, 'Student cannot export private attendee data');
  const eventTitle = `E2E-${suffix}`;
  const payload = {title:eventTitle,description:'Automated end-to-end workflow verification.',category:'Workshop',status:'published',starts_at:time(48),ends_at:time(50),registration_deadline:time(24),venue:'Test Lab',organizer:'Test Team',capacity:'1'};
  r = await admin.post('edit','save_event',payload); check(r.text.includes('Event saved.'), 'Admin creates published event');
  r = await guest.request('events',null,`&q=${eventTitle}`);
  const id = r.text.match(/page=event&amp;id=(\d+)/)?.[1]; check(Boolean(id),'Published event is discoverable');
  r = await student.post('event','save_event',{...payload,id},`&id=${id}`); check(r.status===403,'Student cannot submit administrator write action');
  r = await student.post('event','register',{id},`&id=${id}`); check(r.text.includes('Registration confirmed'), 'Student registers');
  r = await student.post('event','register',{id},`&id=${id}`); check(r.text.includes('already registered'), 'Duplicate registration blocked');
  r = await other.post('event','register',{id},`&id=${id}`); check(r.text.includes('event is full'), 'Capacity limit enforced');
  r = await admin.post('admin','delete_event',{id}); check(r.text.includes('has registrations'), 'Deletion preserves registration history');
  r = await admin.post('edit','save_event',{...payload,id,status:'draft'},`&id=${id}`); check(r.text.includes('cannot become a draft'), 'Registered event cannot be hidden as draft');
  r = await student.post('my','cancel_registration',{id}); check(r.text.includes('withdrawn'), 'Student withdraws before start');
  r = await other.post('event','register',{id},`&id=${id}`); check(r.text.includes('Registration confirmed'), 'Released seat can be booked');
  r = await admin.request('attendees',null,`&id=${id}`);
  const rid = r.text.match(/name="marks\[(\d+)\]"/)?.[1]; check(Boolean(rid),'Administrator sees attendee');
  r = await admin.post('attendees','attendance',{id,[`marks[${rid}]`]:'present'},`&id=${id}`); check(r.text.includes('Attendance opens'), 'Attendance cannot be recorded before start');
  r = await admin.post('edit','save_event',{...payload,id,starts_at:time(-2),ends_at:time(-1),registration_deadline:time(-3)},`&id=${id}`); check(r.text.includes('Event saved.'), 'Admin updates event schedule');
  r = await admin.post('attendees','attendance',{id,[`marks[${rid}]`]:'present'},`&id=${id}`); check(r.text.includes('Attendance saved'), 'Administrator saves attendance');
  r = await other.request('my'); check(r.text.includes('<strong>present</strong>'), 'Student sees recorded attendance');
  r = await admin.request('export',null,`&id=${id}`); check(r.status===200 && r.text.includes('present') && r.text.includes(`e2e-${suffix}-2`), 'CSV export contains attendance');
  r = await admin.request('reports'); check(r.status===200 && r.text.includes(eventTitle), 'Reports include event');
  r = await other.post('my','cancel_registration',{id}); check(r.text.includes('cannot withdraw'), 'Withdrawal blocked after start');
  r = await admin.post('edit','save_event',{...payload,id,status:'cancelled'},`&id=${id}`); check(r.text.includes('Event saved.'),'Admin cancels event');
  r = await guest.request('events',null,`&q=${eventTitle}`); check(r.text.includes('No events found'),'Cancelled event excluded from discovery');
  r = await other.request('my'); check(r.text.includes(eventTitle) && r.text.includes('Cancelled'),'Existing student retains cancelled event history');
  r = await student.post('profile','profile',{name:'Updated Test Student',department:'BCA TY',current_password:'TestPass@12345',password:'ChangedPass@12345'}); check(r.text.includes('Profile updated'),'Profile and password updated');
  r = await student.post('profile','logout'); check(r.text.includes('Sign in with your'),'Logout succeeds');
  r = await student.post('login','login',{email:`e2e-${suffix}-1@college.test`,password:'ChangedPass@12345'}); check(r.text.includes('My registrations'),'Changed password works');
  r = await admin.post('edit','save_event',{...payload,title:`Delete-${suffix}`,status:'draft'}); check(r.text.includes('Event saved.'),'Draft event created');
  const row=r.text.split('<tr>').find(s=>s.includes(`Delete-${suffix}`));
  const draftId=row?.match(/page=edit&amp;id=(\d+)/)?.[1]; assert.ok(draftId);
  r = await guest.request('event',null,`&id=${draftId}`); check(r.status===404,'Draft details hidden from anonymous users');
  r = await admin.post('admin','delete_event',{id:draftId}); check(r.text.includes('Event deleted.'),'Empty draft can be deleted');
  console.log(`\n${passed} checks passed. Test run ${suffix}.`);
} catch (error) { console.error(`FAIL after ${passed} checks:`, error.message); process.exitCode=1; }
