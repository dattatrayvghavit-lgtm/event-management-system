<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title??'Campus Events')?> · CCSIT Latur</title><link rel="stylesheet" href="assets/style.css"><script defer src="assets/app.js"></script></head>
<body><a class="skip" href="#main">Skip to content</a><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">C.</span><span>Campus<span class="brand-light">Events</span><small>CCSIT · LATUR</small></span></a><nav aria-label="Main navigation">
<a class="<?=$page==='events'?'active':''?>" href="index.php">Discover</a>
<?php if(user()): ?>
<?php if(user()['role']==='admin'): ?><a class="<?=in_array($page,['admin','edit','attendees','students'])?'active':''?>" href="index.php?page=admin">Dashboard</a><a class="<?=$page==='reports'?'active':''?>" href="index.php?page=reports">Reports</a>
<?php else: ?><a class="<?=$page==='my'?'active':''?>" href="index.php?page=my">My registrations</a><?php endif ?>
<a href="index.php?page=profile" class="profile-link"><?=e(explode(' ',user()['name'])[0])?></a><form method="post" class="inline"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="text-button">Sign out</button></form>
<?php else: ?><a href="index.php?page=login">Sign in</a><a class="button small" href="index.php?page=signup">Join campus →</a><?php endif ?></nav></header>
<main id="main" class="container"><?php if(isset($_SESSION['flash'])): [$msg,$type]=$_SESSION['flash']; unset($_SESSION['flash']); ?><div role="status" class="notice <?=e($type)?>"><?=e($msg)?></div><?php endif ?><?=$content?></main>
<footer><div><strong>College of Computer Science and Information Technology, Latur.</strong><p>Affiliated to Swami Ramanand Teerth Marathwada University, Nanded.</p></div><span>BCA · Capstone Project<br>2026–2027</span></footer></body></html>
