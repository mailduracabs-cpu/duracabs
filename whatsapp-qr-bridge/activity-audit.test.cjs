'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {indiaDay, createActivityAudit, csvCell, mergeEvent} = require('./activity-audit.cjs');
function dir(t) { const p=fs.mkdtempSync(path.join(os.tmpdir(),'taxi-audit-')); t.after(()=>fs.rmSync(p,{recursive:true,force:true})); return p; }
test('day boundary uses India receipt day', () => {
  assert.equal(indiaDay(new Date('2026-11-05T18:29:59Z')), '2026-11-05');
  assert.equal(indiaDay(new Date('2026-11-05T18:30:00Z')), '2026-11-06');
});
test('Hindi and line breaks survive daily JSONL logging', t => {
  const p=dir(t), audit=createActivityAudit(p), message='अजमेर से किशनगढ़\nTime ready';
  audit.append({messageId:'one', groupId:'1@g.us', message, outcome:'queued'});
  const row=JSON.parse(fs.readFileSync(path.join(p,`activity-${indiaDay()}.jsonl`),'utf8'));
  assert.equal(row.message,message); assert.equal(audit.status.errors,0);
});
test('final outcome replaces queued state in review export', () => {
  const rows=new Map();
  mergeEvent(rows,{messageId:'one',groupId:'1@g.us',at:'first',outcome:'queued'});
  mergeEvent(rows,{messageId:'one',groupId:'1@g.us',at:'last',outcome:'ignored',reason:'duplicate_booking'});
  assert.equal(rows.size,1); const row=[...rows.values()][0];
  assert.equal(row.firstSeenAt,'first'); assert.equal(row.outcome,'ignored');
});
test('log failures and date expiry do not throw into import flow', t => {
  const audit=createActivityAudit(dir(t),{maxBytes:1});
  assert.doesNotThrow(()=>audit.append({message:'sample'})); assert.equal(audit.status.errors,1);
  const past=createActivityAudit(dir(t),{day:'2000-01-01'});
  past.append({message:'old day'}); assert.equal(past.status.events,0);
});
test('spreadsheet formula prefixes are escaped', () => {
  assert.equal(csvCell('=1+1'),'"\'=1+1"');
  assert.equal(csvCell('hello "taxi"'),'"hello ""taxi"""');
});
