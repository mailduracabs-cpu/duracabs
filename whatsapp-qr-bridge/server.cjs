'use strict';
const http = require('node:http');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const {Client, LocalAuth} = require('whatsapp-web.js');
const QRCode = require('qrcode');
const {sendInterest} = require('./interest-send.cjs');
const {loadGroups} = require('./groups.cjs');
const {safeEqual, signature, envelope, skipReason, messageKey} = require('./bridge.cjs');
const {createActivityAudit} = require('./activity-audit.cjs');
const {createIntakeReview} = require('./intake-review.cjs');
const password = process.env.ADMIN_PASSWORD || '';
const secret = process.env.LIVE_BOOKINGS_QR_SECRET || '';
if (password.length < 20 || password.startsWith('replace-') || secret.length < 32 || secret.startsWith('replace-') || password === secret) {
  throw new Error('Set two different random secrets in .env: ADMIN_PASSWORD (20+ characters) and LIVE_BOOKINGS_QR_SECRET (32+).');
}
const webhook = new URL(process.env.LARAVEL_WEBHOOK_URL || '');
if (webhook.username || webhook.password || webhook.search || webhook.hash ||
    !(webhook.protocol === 'https:' || (webhook.protocol === 'http:' && ['127.0.0.1', 'localhost', '[::1]'].includes(webhook.hostname)))) {
  throw new Error('Use an HTTPS Laravel URL or local HTTP, without credentials/query parameters.');
}
const groupOutbox = process.env.LARAVEL_GROUP_OUTBOX_URL ? new URL(process.env.LARAVEL_GROUP_OUTBOX_URL) : null;
if (groupOutbox && (groupOutbox.username || groupOutbox.password || groupOutbox.search || groupOutbox.hash ||
    !(groupOutbox.protocol === 'https:' || (groupOutbox.protocol === 'http:' && ['127.0.0.1', 'localhost', '[::1]'].includes(groupOutbox.hostname))))) {
  throw new Error('Use an HTTPS group outbox URL or local HTTP, without credentials/query parameters.');
}
const dataDir = path.join(__dirname, 'data');
const outbox = path.join(dataDir, 'outbox');
fs.mkdirSync(outbox, {recursive: true, mode: 0o700});
const groupBroadcastDir = path.join(dataDir, 'group-broadcasts');
fs.mkdirSync(groupBroadcastDir, {recursive: true, mode: 0o700});
const activityAudit = createActivityAudit(path.join(dataDir, 'activity-audit'), {
  enabled: process.env.LIVE_BOOKING_AUDIT_ENABLED !== 'false',
  day: process.env.LIVE_BOOKING_AUDIT_DATE || undefined,
});
const saveIntakeReview = createIntakeReview(path.join(dataDir, 'intake-review'));
const selectionFile = path.join(dataDir, 'groups.json');
let selected = new Set(fs.existsSync(selectionFile) ? JSON.parse(fs.readFileSync(selectionFile, 'utf8')) : []);
let state = 'starting', qrImage = null, qrExpires = 0, detail = '', delivering = false, groups = [];
let accepted = 0, ignored = 0, reviewed = 0, connected = false, stopping = false, groupLoading = null, lifecycleWork = null;
let broadcasting = false, groupBroadcastSent = 0, groupBroadcastFailed = 0, groupBroadcastDetail = '';
const diagnostics = {selected_received:0, own_message:0, old_message:0, not_text:0, invalid_text:0, invalid_id:0, unselected_group:0, not_ready:0, storage_error:0, expired_queue:0, deselected_queue:0, last_selected_at:null, backend_reasons:{}};
const recentActivity = [];
function preview(value, limit = 700) {
  const text = String(value || '').replace(/\s+/g, ' ').trim();
  return text.length > limit ? text.slice(0, limit - 1) + '…' : text;
}
function rememberActivity(message, outcome, reason = '', metadata = {}) {
  const groupId = String(message?.from || message?.group_id || '');
  const group = groups.find(item => item.id === groupId);
  const messagePreview = preview(message?.body || message?.text?.body);
  const rawText = String(message?.body || message?.text?.body || '');
  // Capture only selected-group incoming text; own broadcasts and personal chats
  // are not a parser training sample. The UI preview remains bounded separately.
  if (selected.has(groupId) && rawText && !['own_message', 'not_text', 'old_message'].includes(reason)) {
    activityAudit.append({at: new Date().toISOString(),
      messageId: metadata.messageId || messageKey(message),
      whatsappTimestamp: metadata.timestamp ?? message?.timestamp ?? null,
      groupId, groupName: group?.name || groupId, message: rawText, outcome, reason});
  }
  const pending = outcome !== 'queued' && recentActivity.find(item => item.outcome === 'queued' && item.groupId === groupId && item.message === messagePreview);
  if (pending) {
    pending.at = new Date().toISOString();
    pending.outcome = outcome;
    pending.reason = reason;
    return;
  }
  recentActivity.unshift({
    at: new Date().toISOString(),
    groupId,
    groupName: group?.name || groupId || 'Unknown group',
    message: messagePreview,
    outcome,
    reason
  });
  if (recentActivity.length > 20) recentActivity.length = 20;
}
const seenEvents = new Map();
const startedAt = Math.floor(Date.now() / 1000), csrf = crypto.randomBytes(32).toString('hex');
let client = null;
function newClient() {
  const next = new Client({authStrategy: new LocalAuth({clientId: 'taxi-sathi-admin', dataPath: path.join(dataDir, 'session')}),
    puppeteer: {headless: true, ...(process.env.CHROME_PATH ? {executablePath: process.env.CHROME_PATH} : {})},
    deviceName: 'Duracabs Taxi Sathi', qrMaxRetries: 10});
  next.on('qr', async value => {
    const image = await QRCode.toDataURL(value);
    if (client === next && !connected && !stopping) { qrImage = image; qrExpires = Date.now() + 60000; state = 'scan_qr'; detail = ''; }
  });
  next.on('authenticated', () => { if (client === next) { state = 'loading'; qrImage = null; } });
  next.on('ready', () => { if (client === next) { connected = true; state = 'connected'; qrImage = null; detail = ''; } });
  next.on('auth_failure', () => { if (client === next) { connected = false; state = 'authentication_failed'; qrImage = null; detail = 'Authentication failed. Use Reconnect; if it repeats, use Logout & New QR.'; } });
  next.on('disconnected', () => { if (client === next && !stopping) { connected = false; state = 'disconnected'; qrImage = null; detail = 'WhatsApp disconnected. Press Reconnect to restore the saved session.'; } });
  next.on('message', receiveMessage);
  next.on('message_create', receiveMessage);
  return next;
}
async function connectClient() {
  if (lifecycleWork) return lifecycleWork;
  lifecycleWork = (async () => {
    const previous = client;
    client = null;
    connected = false; qrImage = null; qrExpires = 0; groupLoading = null; groups = [];
    if (previous) { try { await previous.destroy(); } catch (_) {} }
    if (stopping) return;
    state = 'starting'; detail = 'Starting Chrome and restoring WhatsApp session…';
    const next = newClient(); client = next;
    try { await next.initialize(); }
    catch (e) {
      if (client === next) {
        connected = false; state = 'start_failed';
        detail = 'Could not start Chrome. Close other bridge CMD windows, check CHROME_PATH, then press Reconnect.';
      }
      console.error('WhatsApp client start failed:', String(e?.message || e).split('\n')[0]);
    }
  })().finally(() => { lifecycleWork = null; });
  return lifecycleWork;
}
async function disconnectClient() {
  if (lifecycleWork) await lifecycleWork;
  const previous = client; client = null; connected = false; groups = []; groupLoading = null; qrImage = null; qrExpires = 0;
  if (previous) { try { await previous.destroy(); } catch (_) {} }
  state = 'disconnected'; detail = 'Temporarily disconnected. Your saved WhatsApp login is preserved; press Reconnect when ready.';
}
function receiveMessage(message) {
  // whatsapp-web.js can emit both events for the same incoming message.
  // Deduplicate before counting/queuing so the compatibility listener cannot double-import.
  const id = messageKey(message);
  if (typeof id === 'string') {
    if (seenEvents.has(id)) return;
    if (seenEvents.size >= 5000) seenEvents.delete(seenEvents.keys().next().value);
    seenEvents.set(id, Date.now());
  }
  const reason = skipReason(message, selected, startedAt);
  if (reason === 'personal_chat') return;
  if (reason === 'invalid_id') console.error('Invalid WhatsApp ID shape:', JSON.stringify({idType:typeof message.id,idFields:message.id&&typeof message.id==='object'?Object.keys(message.id):[],rawIdType:typeof message._data?.id}));
  if (reason) {
    diagnostics[reason] = (diagnostics[reason] || 0) + 1;
    if (selected.has(String(message.from || ''))) rememberActivity(message, 'ignored', reason);
    return;
  }
  diagnostics.selected_received++;
  diagnostics.last_selected_at = new Date().toISOString();
  rememberActivity(message, 'queued', 'waiting_for_laravel');
  // Connected is a display state; an authenticated incoming event already proves
  // the browser received it. Never discard it solely because ready is still syncing.
  const payload = envelope(message, selected, startedAt);
  const key = id;
  const target = path.join(outbox, key + '.json');
  try {
    if (fs.readdirSync(outbox).length >= 1000) { diagnostics.storage_error++; rememberActivity(message, 'error', 'import_queue_full'); detail = 'Import queue is full. Restore Laravel connectivity before receiving more leads.'; return; }
    fs.writeFileSync(target, JSON.stringify(payload), {encoding: 'utf8', mode: 0o600, flag: 'wx'});
    void deliver();
  } catch (e) { if (e.code !== 'EEXIST') { diagnostics.storage_error++; rememberActivity(message, 'error', 'queue_storage_error'); detail = 'Could not save an incoming message. Check bridge storage.'; } }
}
const intakeRetryAfter = new Map();
async function deliver() {
  if (delivering || stopping) return;
  delivering = true;
  try {
    for (const name of fs.readdirSync(outbox).filter(n => /^[a-f0-9]{64}\.json$/.test(n) && (intakeRetryAfter.get(n) || 0) <= Date.now()).slice(0, 20)) {
      const file = path.join(outbox, name), body = fs.readFileSync(file, 'utf8');
      const parsed = JSON.parse(body);
      if (!selected.has(parsed.entry[0].changes[0].value.messages[0].group_id)) { fs.unlinkSync(file); diagnostics.deselected_queue++; continue; }
      // Keep the original receipt timestamp. Stale messages require review, not a
      // fresh timestamp or a silent deletion when Laravel could not process them.
      const timestamp = Number(parsed.entry[0].changes[0].value.messages[0].timestamp);
      const incoming = parsed.entry[0].changes[0].value.messages[0];
      if (Date.now()/1000 - timestamp > 300) {
        saveIntakeReview(incoming, 'retry_window_expired');
        fs.unlinkSync(file); intakeRetryAfter.delete(name); reviewed++; diagnostics.expired_queue++;
        rememberActivity({group_id: incoming.group_id, text: incoming.text}, 'review', 'saved_for_review_retry_window_expired', {messageId: name.slice(0, -5), timestamp});
        detail = 'An undelivered message was saved for review after its retry window expired.';
        continue;
      }
      // Gemini fallback can take longer than the deterministic parser. Keep the
      // durable outbox request open long enough to receive Laravel's result.
      const res = await fetch(webhook, {method: 'POST', redirect: 'error', signal: AbortSignal.timeout(45000),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Taxi-Sathi-Bridge': 'qr-v1', 'X-Hub-Signature-256': signature(secret, body)}, body});
      if (!res.ok) {
        if (res.status === 503) {
          const failed = await res.json().catch(() => null);
          const reason = failed?.reasons ? Object.keys(failed.reasons).find(key => Number(failed.reasons[key]) > 0) : '';
          if (failed?.reviewed > 0) saveIntakeReview(incoming, reason || 'ai_retry_pending');
          intakeRetryAfter.set(name, Date.now() + 30000);
          detail = 'A technical parser failure is queued for retry and saved for review. Other messages continue.';
          continue;
        }
        detail = `Laravel intake returned HTTP ${res.status}. Message remains queued for retry; technical failures are saved for review.`;
        break;
      }
      const result = await res.json();
      if (result.status !== 'ok' || typeof result.imported !== 'number') { detail = 'Update the Laravel webhook controller before importing.'; break; }
      accepted += result.imported;
      ignored += result.ignored || 0;
      reviewed += result.reviewed || 0;
      const backendReason = result.reasons && typeof result.reasons === 'object'
        ? Object.keys(result.reasons).find(key => Number(result.reasons[key]) > 0) || ''
        : '';
      if (result.reviewed > 0) saveIntakeReview(incoming, backendReason || 'needs_review');
      else if (result.imported > 0 || backendReason === 'duplicate_booking') saveIntakeReview(incoming, backendReason || 'booking_created', result.imported > 0 ? 'imported' : 'duplicate');
      rememberActivity({group_id: incoming.group_id, text: incoming.text}, result.imported > 0 ? 'imported' : result.reviewed > 0 ? 'review' : 'ignored', backendReason || (result.imported > 0 ? 'booking_created' : 'not_imported'), {messageId: name.slice(0, -5), timestamp});
      if (result.reasons && typeof result.reasons === 'object') {
        const permitted = ['source_or_time','invalid_payload','incomplete_booking','missing_required_fields','expired_booking','duplicate','duplicate_booking'];
        for (const key of permitted) if (Number.isSafeInteger(result.reasons[key]) && result.reasons[key] > 0) {
          diagnostics.backend_reasons[key] = (diagnostics.backend_reasons[key] || 0) + result.reasons[key];
        }
      }
      fs.unlinkSync(file); intakeRetryAfter.delete(name);
      detail = '';
    }
  } catch (_) { detail = 'Cannot confirm Laravel intake. Queued messages will retry for up to 5 minutes.'; }
  finally { delivering = false; }
}
setInterval(() => void deliver(), 5000).unref();

function saveBroadcast(file, value) {
  const temp = file + '.tmp';
  fs.writeFileSync(temp, JSON.stringify(value), {encoding: 'utf8', mode: 0o600});
  fs.renameSync(temp, file);
}
function broadcastRequest(url, payload) {
  const body = JSON.stringify(payload);
  return fetch(url, {method: 'POST', redirect: 'error', signal: AbortSignal.timeout(30000),
    headers: {'Content-Type': 'application/json', 'Accept': 'application/json',
      'X-Taxi-Sathi-Bridge': 'group-broadcast-v1', 'X-Hub-Signature-256': signature(secret, body)}, body});
}
async function acknowledgeBroadcast(job, result, error = '') {
  const url = new URL(groupOutbox); url.pathname = url.pathname.replace(/\/$/, '') + '/acknowledge';
  const res = await broadcastRequest(url, {timestamp: Math.floor(Date.now()/1000), job_id: job.id, lease_token: job.lease_token, result, error: preview(error, 900)});
  if (!res.ok) throw new Error(`acknowledge_http_${res.status}`);
}
async function sendGroupBroadcast(job) {
  if (!Number.isSafeInteger(job?.id) || !Number.isSafeInteger(job?.post_id) ||
      typeof job?.message !== 'string' || !job.message.trim() || job.message.length > 4000) {
    throw new Error('invalid_group_broadcast_job');
  }
  const file = path.join(groupBroadcastDir, `${job.id}.json`);
  let record = {jobId: job.id, postId: job.post_id, revision: job.post_revision, groups: {}};
  if (fs.existsSync(file)) {
    try { record = JSON.parse(fs.readFileSync(file, 'utf8')); } catch (_) { throw new Error('invalid_local_broadcast_receipt'); }
  }
  // A crash after dispatch has an uncertain result. Never automatically resend it.
  for (const receipt of Object.values(record.groups || {})) if (receipt.status === 'sending') receipt.status = 'unknown';
  saveBroadcast(file, record);
  const targets = record.targets || [...selected].filter(id => typeof id === 'string' && /^[0-9-]+@g\.us$/.test(id));
  if (!targets.length) {
    await acknowledgeBroadcast(job, 'retry', 'no_selected_groups');
    groupBroadcastDetail = 'A booking broadcast is waiting because no WhatsApp groups are selected.';
    return;
  }
  // Keep the original audience across retries; newly selected groups do not get old jobs.
  record.targets = targets;
  saveBroadcast(file, record);
  for (const groupId of targets) {
    if (!connected || stopping || !client) throw new Error('whatsapp_disconnected_during_broadcast');
    const existing = record.groups[groupId]?.status;
    if (['sent', 'unknown', 'failed'].includes(existing)) continue;
    if (!selected.has(groupId)) {
      record.groups[groupId] = {status: 'skipped', at: new Date().toISOString()};
      saveBroadcast(file, record);
      continue;
    }
    record.groups[groupId] = {status: 'sending', at: new Date().toISOString()};
    saveBroadcast(file, record);
    try {
      let timeout;
      try {
        await Promise.race([
          client.sendMessage(groupId, job.message),
          new Promise((_, reject) => { timeout = setTimeout(() => reject(new Error('send_result_uncertain')), 30000); })
        ]);
      } finally { clearTimeout(timeout); }
      record.groups[groupId] = {status: 'sent', at: new Date().toISOString()};
      groupBroadcastSent++;
    } catch (e) {
      record.groups[groupId] = {status: 'failed', at: new Date().toISOString(), error: preview(e?.message || e, 300)};
      groupBroadcastFailed++;
    }
    saveBroadcast(file, record);
    await new Promise(resolve => setTimeout(resolve, 500));
  }
  const receipts = Object.values(record.groups);
  const sent = receipts.filter(r => r.status === 'sent').length;
  const uncertain = receipts.filter(r => r.status !== 'sent').length;
  await acknowledgeBroadcast(job, uncertain ? 'partial' : 'delivered', uncertain ? `${sent} sent; ${uncertain} failed or uncertain` : '');
  groupBroadcastDetail = uncertain ? `Booking broadcast partially sent (${sent}/${receipts.length}).` : '';
}
async function processGroupBroadcasts() {
  if (!groupOutbox || broadcasting || stopping || !connected || !client) return;
  broadcasting = true;
  try {
    const url = new URL(groupOutbox); url.pathname = url.pathname.replace(/\/$/, '') + '/pull';
    const res = await broadcastRequest(url, {timestamp: Math.floor(Date.now()/1000), limit: 1});
    if (!res.ok) throw new Error(`pull_http_${res.status}`);
    const result = await res.json();
    const jobs = result?.data?.jobs;
    if (!Array.isArray(jobs)) throw new Error('invalid_group_outbox_response');
    for (const job of jobs) {
      if (!connected || stopping || !client) break;
      await sendGroupBroadcast(job);
    }
  } catch (e) {
    groupBroadcastDetail = `Group broadcast unavailable: ${preview(e?.message || e, 180)}`;
  } finally { broadcasting = false; }
}
setInterval(() => void processGroupBroadcasts(), 10000).unref();
function json(res, value, status = 200) { res.writeHead(status, {'Content-Type': 'application/json'}); res.end(JSON.stringify(value)); }
function authenticated(req) {
  const match = /^Basic (.+)$/.exec(req.headers.authorization || '');
  if (!match) return false;
  const decoded = Buffer.from(match[1], 'base64').toString('utf8'), sep = decoded.indexOf(':');
  return sep >= 0 && safeEqual(decoded.slice(0, sep), 'admin') && safeEqual(decoded.slice(sep + 1), password);
}
const server = http.createServer(async (req, res) => {
  res.setHeader('Cache-Control', 'no-store');
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'DENY');
  res.setHeader('Referrer-Policy', 'no-referrer');
  // Loopback only; reject DNS rebinding hosts. Remote access uses an SSH tunnel.
  if (!['127.0.0.1', 'localhost', '[::1]'].includes((req.headers.host || '').replace(/:\d+$/, ''))) { res.writeHead(403); return res.end(); }
  if (req.method === 'POST' && req.url === '/send-interest') {
    try {
      let raw='';for await(const chunk of req){raw+=chunk;if(Buffer.byteLength(raw)>20000)return json(res,{status:'failed'},413);}
      if(!safeEqual(req.headers['x-taxi-sathi-signature']||'',signature(secret,raw)))return json(res,{status:'failed'},403);
      const payload=JSON.parse(raw);
      if(!Number.isInteger(payload.timestamp)||Math.abs(Date.now()/1000-payload.timestamp)>60)return json(res,{status:'failed'},403);
      if(!connected||stopping||!client)return json(res,{status:'not_connected'},409);
      return json(res,await sendInterest(payload,client,path.join(dataDir,'interest-sends')));
    }catch(_){return json(res,{status:'unknown'},500);}
  }
  if (!authenticated(req)) { res.writeHead(401, {'WWW-Authenticate': 'Basic realm="Taxi Sathi admin"'}); return res.end('Admin login required'); }
  try {
    const url = new URL(req.url, 'http://127.0.0.1');
    if (req.method === 'GET' && url.pathname === '/') {
      res.setHeader('Content-Type', 'text/html; charset=utf-8');
      return res.end(fs.readFileSync(path.join(__dirname, 'index.html'), 'utf8'));
    }
    if (req.method === 'GET' && url.pathname === '/status') return json(res, {
      state, detail, qr: Date.now() < qrExpires ? qrImage : null, qrExpires,
      serverTime: Date.now(), startedAt: startedAt * 1000, csrf,
      selected: [...selected], diagnostics, recentActivity, activityAudit: activityAudit.status,
      accepted, ignored, reviewed, queued: fs.readdirSync(outbox).length,
      groupBroadcast: {enabled: !!groupOutbox, running: broadcasting, sent: groupBroadcastSent, failed: groupBroadcastFailed, detail: groupBroadcastDetail}
    });
    if (req.method === 'GET' && url.pathname === '/groups') {
      if (!connected) return json(res, {error: 'Connect WhatsApp first.'}, 409);
      try {
        if (!groupLoading) groupLoading = loadGroups(client).finally(() => { groupLoading = null; });
        const result = await groupLoading;
        if (!connected) return json(res, {error: 'WhatsApp disconnected. Restart and reconnect before loading groups.'}, 409);
        groups = result;
        return json(res, {groups, message: groups.length ? '' : 'No groups are available in this linked session yet. Check the linked account, open a group on your phone, wait for sync and try Load groups again.'});
      } catch (e) { return json(res, {error: e.message}, 503); }
    }
    if (req.method === 'POST' && ['/groups', '/logout', '/connection'].includes(url.pathname)) {
      if (!safeEqual(req.headers['x-csrf-token'] || '', csrf)) return json(res, {error: 'Refresh the page.'}, 403);
      let raw = '';
      for await (const chunk of req) { raw += chunk; if (raw.length > 20000) return json(res, {error: 'Request too large.'}, 413); }
      if (url.pathname === '/connection') {
        const input = JSON.parse(raw || '{}');
        if (input.action === 'disconnect') await disconnectClient();
        else if (input.action === 'reconnect') await connectClient();
        else return json(res, {error: 'Choose disconnect or reconnect.'}, 422);
        return json(res, {ok: true, state});
      }
      if (url.pathname === '/logout') {
        const current = client;
        connected = false; state = 'disconnecting'; qrImage = null; qrExpires = 0;
        if (current) { try { await current.logout(); } catch (_) { try { await current.destroy(); } catch (_) {} } }
        client = null; state = 'disconnected'; groups = []; groupLoading = null;
        detail = 'Logged out. Press Reconnect to generate a new QR.';
        return json(res, {ok: true});
      }
      const input = JSON.parse(raw);
      if (!Array.isArray(input.ids) || input.ids.length > 200 || input.ids.some(id => !groups.some(g => g.id === id))) return json(res, {error: 'Load groups and select valid groups.'}, 422);
      const ids = [...new Set(input.ids)];
      const temp = selectionFile + '.tmp';
      fs.writeFileSync(temp, JSON.stringify(ids), {mode: 0o600}); fs.renameSync(temp, selectionFile);
      selected = new Set(ids);
      return json(res, {ok: true});
    }
    json(res, {error: 'Not found'}, 404);
  } catch (_) { json(res, {error: 'Could not complete this action. Check bridge connection and retry.'}, 500); }
});
server.listen(Number(process.env.PORT || 3210), '127.0.0.1', () => console.log('Taxi Sathi WhatsApp: open http://127.0.0.1:' + (process.env.PORT || 3210) + ' (user: admin)'));
void connectClient();
async function stop() { stopping = true; server.close(); const current = client; client = null; try { if (current) await current.destroy(); } catch (_) {} process.exit(0); }
process.on('SIGINT', stop); process.on('SIGTERM', stop);
