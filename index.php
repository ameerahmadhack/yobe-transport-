<?php
// ============================================================
//  index.php  — YTC Admin Dashboard  (v3)
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (!file_exists(__DIR__ . '/config.php')) {
    die('<h2 style="font-family:monospace;color:red">ERROR: config.php not found. Upload all files to the same folder.</h2>');
}
require_once __DIR__ . '/config.php';
requireLogin();

$admin_user = htmlspecialchars($_SESSION['ytc_user'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Dashboard — Yobe Transport Service</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --green:#16a34a;--green-l:#dcfce7;--green-d:#15803d;
  --blue:#2563eb;--blue-l:#dbeafe;
  --yellow:#d97706;--yellow-l:#fef3c7;
  --red:#dc2626;--red-l:#fee2e2;
  --gray-50:#f9fafb;--gray-100:#f3f4f6;--gray-200:#e5e7eb;
  --gray-400:#9ca3af;--gray-500:#6b7280;--gray-600:#4b5563;
  --gray-700:#374151;--gray-900:#111827;--white:#ffffff;
  --shadow-sm:0 1px 3px rgba(0,0,0,.08);
  --shadow-md:0 4px 16px rgba(0,0,0,.08);
  --shadow-lg:0 10px 32px rgba(0,0,0,.10);
  --r:14px;--rs:8px;
}
body{font-family:'DM Sans',sans-serif;background:var(--gray-50);color:var(--gray-900);min-height:100vh}
/* ── Header ────────────────────────────────────────────── */
header{background:var(--white);border-bottom:1px solid var(--gray-200);position:sticky;top:0;z-index:100;padding:0 24px}
.hi{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:64px;gap:12px}
.brand{display:flex;align-items:center;gap:10px;font-weight:700;font-size:17px;color:var(--gray-900);text-decoration:none}
.b-icon{width:34px;height:34px;border-radius:10px;background:var(--green);display:flex;align-items:center;justify-content:center;font-size:17px}
.ha{display:flex;align-items:center;gap:8px}
.nav-a{padding:7px 12px;border-radius:var(--rs);text-decoration:none;font-size:13px;font-weight:500;color:var(--gray-600);transition:all .15s}
.nav-a:hover{background:var(--green-l);color:var(--green-d)}
.chip{display:flex;align-items:center;gap:5px;padding:6px 11px;border-radius:var(--rs);background:var(--gray-100);font-size:13px;color:var(--gray-700);font-weight:500}
.btn-ref{display:flex;align-items:center;gap:5px;padding:7px 13px;border-radius:var(--rs);border:1.5px solid var(--gray-200);background:var(--white);color:var(--gray-700);font-family:inherit;font-size:13px;font-weight:500;cursor:pointer;transition:all .15s}
.btn-ref:hover{border-color:var(--green);color:var(--green)}
.btn-ref svg{width:14px;height:14px}
.btn-out{padding:7px 13px;border-radius:var(--rs);border:1.5px solid var(--gray-200);background:var(--white);font-family:inherit;font-size:13px;font-weight:500;cursor:pointer;color:var(--gray-600);text-decoration:none;transition:all .15s}
.btn-out:hover{border-color:var(--red);color:var(--red);background:#fff1f2}
#ldot{width:8px;height:8px;border-radius:50%;background:var(--green);animation:pulse 2s ease-in-out infinite;flex-shrink:0}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}
/* ── Stats ──────────────────────────────────────────────── */
.stats{max-width:1280px;margin:24px auto 0;padding:0 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px}
.sc{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--r);padding:15px 17px;box-shadow:var(--shadow-sm)}
.sc .lbl{font-size:11px;font-weight:600;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em}
.sc .val{font-size:27px;font-weight:700;margin-top:3px}
.sc.total .val{color:var(--gray-900)}.sc.pending .val{color:var(--yellow)}.sc.approved .val{color:var(--blue)}
.sc.completed .val{color:var(--green)}.sc.rejected .val{color:var(--gray-400)}.sc.emergency .val{color:var(--red)}
/* ── Toolbar ────────────────────────────────────────────── */
.tb{max-width:1280px;margin:18px auto 0;padding:0 24px;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.fb{padding:6px 15px;border-radius:99px;border:1.5px solid var(--gray-200);background:var(--white);color:var(--gray-600);font-family:inherit;font-size:13px;font-weight:500;cursor:pointer;transition:all .15s}
.fb.active,.fb:hover{border-color:var(--green);background:var(--green-l);color:var(--green-d)}
.srch{margin-left:auto;position:relative}
.srch input{padding:8px 13px 8px 34px;border:1.5px solid var(--gray-200);border-radius:var(--rs);font-family:inherit;font-size:13px;color:var(--gray-900);background:var(--white);width:200px;outline:none;transition:border-color .15s}
.srch input:focus{border-color:var(--green)}
.srch .si{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--gray-400);pointer-events:none}
/* ── Cards ──────────────────────────────────────────────── */
.grid{max-width:1280px;margin:18px auto 48px;padding:0 24px;display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:15px}
.card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--r);box-shadow:var(--shadow-sm);overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s,transform .2s;animation:fi .3s ease both}
.card:hover{box-shadow:var(--shadow-md);transform:translateY(-2px)}
@keyframes fi{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.ch{display:flex;align-items:center;justify-content:space-between;padding:12px 15px 10px;border-bottom:1px solid var(--gray-100)}
.cid{font-family:'DM Mono',monospace;font-size:11px;color:var(--gray-400)}
.badges{display:flex;gap:5px;align-items:center}
.badge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:99px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.badge.pending{background:var(--yellow-l);color:var(--yellow)}.badge.approved{background:var(--blue-l);color:var(--blue)}
.badge.completed{background:var(--green-l);color:var(--green-d)}.badge.rejected{background:var(--gray-100);color:var(--gray-500)}
.tbadge{padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase}
.tbadge.normal{background:var(--gray-100);color:var(--gray-500)}.tbadge.vip{background:#fdf4ff;color:#7e22ce}.tbadge.emergency{background:var(--red-l);color:var(--red)}
.cb{padding:13px 15px;flex:1}
.pname{font-size:15px;font-weight:700}.pphone{font-size:12px;color:var(--gray-500);margin-top:1px;font-family:'DM Mono',monospace}
.route{display:flex;align-items:flex-start;gap:7px;margin-top:11px;padding:9px 11px;background:var(--gray-50);border-radius:var(--rs);border:1px solid var(--gray-100)}
.rlabels{display:flex;flex-direction:column;gap:4px;min-width:0}
.ri{font-size:12px;color:var(--gray-700);display:flex;gap:5px}
.ri span:first-child{color:var(--gray-400);font-size:10px;text-transform:uppercase;font-weight:700;width:30px;flex-shrink:0}
.note{margin-top:9px;font-size:12px;color:var(--gray-500);display:flex;gap:5px;align-items:flex-start;line-height:1.5}
.dbox{margin-top:9px;padding:7px 11px;background:var(--blue-l);border-radius:var(--rs);font-size:12px;color:var(--blue)}
.dbox strong{font-weight:600}
.ctime{margin-top:9px;font-size:10px;color:var(--gray-400);font-family:'DM Mono',monospace}
/* ── Buttons ────────────────────────────────────────────── */
.ca{padding:11px 15px;border-top:1px solid var(--gray-100);display:flex;gap:6px;flex-wrap:wrap}
.btn{padding:5px 12px;border-radius:var(--rs);border:1.5px solid transparent;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:3px}
.approve{background:var(--blue-l);color:var(--blue);border-color:#bfdbfe}
.reject{background:var(--gray-100);color:var(--gray-600);border-color:var(--gray-200)}
.driver{background:var(--green-l);color:var(--green-d);border-color:#bbf7d0}
.complete{background:var(--green);color:var(--white);border-color:var(--green-d)}
.del{background:var(--red-l);color:var(--red);border-color:#fecaca;margin-left:auto}
.btn:hover{filter:brightness(.93);transform:translateY(-1px)}.btn:active{transform:none}
.btn:disabled{opacity:.4;cursor:not-allowed;transform:none}
/* ── Loading / Empty ────────────────────────────────────── */
#loading{display:flex;align-items:center;justify-content:center;padding:80px 24px;flex-direction:column;gap:14px}
.spinner{width:36px;height:36px;border:3px solid var(--gray-200);border-top-color:var(--green);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
#loading p{color:var(--gray-500);font-size:14px}
.empty{grid-column:1/-1;text-align:center;padding:80px 24px;color:var(--gray-400)}
.empty div{font-size:48px;margin-bottom:12px}
/* ── Toast ──────────────────────────────────────────────── */
#tc{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none}
.toast{padding:11px 16px;border-radius:var(--rs);font-size:13px;font-weight:500;box-shadow:var(--shadow-lg);animation:su .25s ease both;pointer-events:all}
.toast.success{background:var(--gray-900);color:var(--white)}.toast.error{background:var(--red);color:var(--white)}
@keyframes su{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
/* ── Modal ──────────────────────────────────────────────── */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center;z-index:500;padding:16px;backdrop-filter:blur(3px)}
.modal{background:var(--white);border-radius:var(--r);box-shadow:var(--shadow-lg);width:100%;max-width:400px;animation:mi .2s ease both}
@keyframes mi{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}
.mh{padding:18px 22px 14px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;justify-content:space-between}
.mh h3{font-size:15px;font-weight:700}
.mc-btn{background:none;border:none;cursor:pointer;color:var(--gray-400);font-size:20px;line-height:1;padding:4px;border-radius:6px}
.mc-btn:hover{background:var(--gray-100);color:var(--gray-700)}
.mb{padding:18px 22px;display:flex;flex-direction:column;gap:13px}
.mf label{display:block;font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.05em;margin-bottom:5px}
.mf input{width:100%;padding:9px 12px;border:1.5px solid var(--gray-200);border-radius:var(--rs);font-family:inherit;font-size:14px;color:var(--gray-900);outline:none;transition:border-color .15s}
.mf input:focus{border-color:var(--green)}
.mfoot{padding:14px 22px;border-top:1px solid var(--gray-100);display:flex;gap:8px;justify-content:flex-end}
.m-cancel{padding:8px 16px;border-radius:var(--rs);border:1.5px solid var(--gray-200);background:var(--white);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;color:var(--gray-700)}
.m-save{padding:8px 16px;border-radius:var(--rs);border:none;background:var(--green);color:var(--white);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s}
.m-save:hover{background:var(--green-d)}
/* ── Responsive ─────────────────────────────────────────── */
@media(max-width:600px){
  .hi{gap:6px}
  .stats,.tb,.grid{padding:0 12px}
  .grid{grid-template-columns:1fr}
  .srch input{width:150px}
  #tc{bottom:12px;right:12px;left:12px}
}
</style>
</head>
<body>

<header>
  <div class="hi">
    <a href="index.php" class="brand">
      <div class="b-icon">🚗</div>
      YTC Dashboard
    </a>
    <div class="ha">
      <a href="api_keys.php" class="nav-a">🔑 API Keys</a>
      <div class="chip">👤 <?= $admin_user ?></div>
      <div id="ldot" title="Live auto-refresh"></div>
      <button class="btn-ref" onclick="loadAll()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
          <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
        </svg>
        Refresh
      </button>
      <a href="logout.php" class="btn-out">Logout</a>
    </div>
  </div>
</header>

<!-- Stats -->
<div class="stats">
  <div class="sc total">   <div class="lbl">Total</div>    <div class="val" id="s-total">—</div></div>
  <div class="sc pending"> <div class="lbl">Pending</div>  <div class="val" id="s-pending">—</div></div>
  <div class="sc approved"><div class="lbl">Approved</div> <div class="val" id="s-approved">—</div></div>
  <div class="sc completed"><div class="lbl">Done</div>    <div class="val" id="s-completed">—</div></div>
  <div class="sc rejected"><div class="lbl">Rejected</div> <div class="val" id="s-rejected">—</div></div>
  <div class="sc emergency"><div class="lbl">Emergency</div><div class="val" id="s-emergency">—</div></div>
</div>

<!-- Toolbar -->
<div class="tb">
  <button class="fb active" onclick="setF('all',this)">All</button>
  <button class="fb" onclick="setF('pending',this)">⏳ Pending</button>
  <button class="fb" onclick="setF('approved',this)">✅ Approved</button>
  <button class="fb" onclick="setF('completed',this)">🏁 Done</button>
  <button class="fb" onclick="setF('rejected',this)">❌ Rejected</button>
  <button class="fb" onclick="setF('emergency',this)">🚨 Emergency</button>
  <div class="srch">
    <svg class="si" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input type="text" id="q" placeholder="Search name, phone…" oninput="render()" />
  </div>
</div>

<!-- Content -->
<div id="loading"><div class="spinner"></div><p>Loading requests…</p></div>
<div class="grid" id="grid" style="display:none"></div>

<!-- Toast -->
<div id="tc"></div>

<!-- Driver Modal -->
<div class="overlay" id="dmodal" style="display:none" onclick="if(event.target===this)closeM()">
  <div class="modal">
    <div class="mh">
      <h3>🚗 Assign Driver</h3>
      <button class="mc-btn" onclick="closeM()">×</button>
    </div>
    <div class="mb">
      <div class="mf"><label>Driver Name</label><input id="m-dn" placeholder="e.g. Musa Aliyu" /></div>
      <div class="mf"><label>Driver Phone</label><input id="m-dp" placeholder="e.g. 08012345678" /></div>
      <div class="mf"><label>Vehicle</label><input id="m-v"  placeholder="e.g. Toyota Corolla — ABC 123 XY" /></div>
    </div>
    <div class="mfoot">
      <button class="m-cancel" onclick="closeM()">Cancel</button>
      <button class="m-save" onclick="submitDriver()">Assign Driver</button>
    </div>
  </div>
</div>

<script>
var all = [], filter = 'all', modalId = null;

async function api(action, body) {
  try {
    var opts = { method: body ? 'POST' : 'GET', headers: {} };
    if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    var res = await fetch('api.php?action=' + action + '&api_key=__dashboard__', opts);
    var data = await res.json();
    return data;
  } catch(e) {
    return { status: 'error', message: 'Network error: ' + e.message };
  }
}

async function loadAll() {
  try {
    var r = await api('get_all');
    var s = await api('stats');
    if (r.status === 'success') { all = r.data || []; }
    else { toast('Error loading: ' + r.message, 'error'); }
    if (s.status === 'success') updateStats(s.data);
    render();
    document.getElementById('loading').style.display = 'none';
    document.getElementById('grid').style.display = 'grid';
  } catch(e) {
    toast('Failed to connect to API', 'error');
  }
}

function updateStats(s) {
  document.getElementById('s-total').textContent     = s.total     || 0;
  document.getElementById('s-pending').textContent   = s.pending   || 0;
  document.getElementById('s-approved').textContent  = s.approved  || 0;
  document.getElementById('s-completed').textContent = s.completed || 0;
  document.getElementById('s-rejected').textContent  = s.rejected  || 0;
  document.getElementById('s-emergency').textContent = s.emergency || 0;
}

function setF(f, el) {
  filter = f;
  document.querySelectorAll('.fb').forEach(function(b){ b.classList.remove('active'); });
  el.classList.add('active');
  render();
}

function render() {
  var q = (document.getElementById('q').value || '').toLowerCase();
  var grid = document.getElementById('grid');
  var items = all.filter(function(r) {
    if (filter === 'emergency') return r.type === 'emergency';
    if (filter !== 'all') return r.status === filter;
    return true;
  });
  if (q) items = items.filter(function(r) {
    return [r.name,r.phone,r.pickup,r.destination].some(function(v){ return (v||'').toLowerCase().indexOf(q) > -1; });
  });
  if (!items.length) {
    grid.innerHTML = '<div class="empty"><div>📭</div><p>No requests found</p></div>';
    return;
  }
  grid.innerHTML = items.map(cardHTML).join('');
}

function cardHTML(r) {
  var appBtn = r.status==='pending'
    ? '<button class="btn approve" onclick="doStatus('+r.id+',\'approved\')">✅ Approve</button>' : '';
  var rejBtn = (r.status!=='rejected'&&r.status!=='completed')
    ? '<button class="btn reject"  onclick="doStatus('+r.id+',\'rejected\')">❌ Reject</button>' : '';
  var drvBtn = (r.status!=='completed'&&r.status!=='rejected')
    ? '<button class="btn driver"  onclick="openM('+r.id+')">🚗 Driver</button>' : '';
  var doneBtn = r.status==='approved'
    ? '<button class="btn complete" onclick="doStatus('+r.id+',\'completed\')">✔ Done</button>' : '';
  var delBtn = '<button class="btn del" onclick="doDel('+r.id+')">🗑</button>';
  var dbox = r.driver_name
    ? '<div class="dbox">🚗 <strong>'+esc(r.driver_name)+'</strong> · '+esc(r.driver_phone)+' · '+esc(r.vehicle)+'</div>' : '';
  var note = r.note
    ? '<div class="note"><span>📝</span><span>'+esc(r.note)+'</span></div>' : '';
  var dt = r.created_at
    ? new Date(r.created_at.replace(' ','T')).toLocaleString('en-NG',{dateStyle:'medium',timeStyle:'short'}) : '';
  return '<div class="card" id="c-'+r.id+'">'
    +'<div class="ch"><span class="cid">#'+r.id+'</span><div class="badges"><span class="tbadge '+r.type+'">'+r.type+'</span><span class="badge '+r.status+'">'+r.status+'</span></div></div>'
    +'<div class="cb"><div class="pname">'+esc(r.name)+'</div><div class="pphone">'+esc(r.phone)+'</div>'
    +'<div class="route"><div style="flex-shrink:0;margin-top:1px">📍</div><div class="rlabels"><div class="ri"><span>From</span><span>'+esc(r.pickup)+'</span></div><div class="ri"><span>To</span><span>'+esc(r.destination)+'</span></div></div></div>'
    +note+dbox+'<div class="ctime">🕐 '+dt+'</div></div>'
    +'<div class="ca">'+appBtn+rejBtn+drvBtn+doneBtn+delBtn+'</div></div>';
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function doStatus(id, status) {
  var res = await api('update_status', {id:id, status:status});
  if (res.status==='success') { toast('Status → '+status); loadAll(); }
  else toast(res.message||'Error', 'error');
}

async function doDel(id) {
  if (!confirm('Delete request #'+id+'?')) return;
  var res = await api('delete', {id:id});
  if (res.status==='success') { toast('Deleted #'+id); loadAll(); }
  else toast(res.message||'Error', 'error');
}

function openM(id) {
  modalId = id;
  ['m-dn','m-dp','m-v'].forEach(function(i){ document.getElementById(i).value=''; });
  document.getElementById('dmodal').style.display = 'flex';
  document.getElementById('m-dn').focus();
}
function closeM() { document.getElementById('dmodal').style.display='none'; modalId=null; }

async function submitDriver() {
  var dn = document.getElementById('m-dn').value.trim();
  var dp = document.getElementById('m-dp').value.trim();
  var v  = document.getElementById('m-v').value.trim();
  if (!dn||!dp||!v) { toast('All fields required','error'); return; }
  var res = await api('assign_driver',{id:modalId,driver_name:dn,driver_phone:dp,vehicle:v});
  if (res.status==='success') { toast('Driver assigned ✅'); closeM(); loadAll(); }
  else toast(res.message||'Error','error');
}

function toast(msg, type) {
  type = type||'success';
  var c = document.getElementById('tc');
  var t = document.createElement('div');
  t.className='toast '+type;
  t.textContent=msg;
  c.appendChild(t);
  setTimeout(function(){t.remove();},3500);
}

loadAll();
setInterval(loadAll, 5000);
</script>
</body>
</html>
