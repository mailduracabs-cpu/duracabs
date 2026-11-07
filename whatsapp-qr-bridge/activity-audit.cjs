'use strict';
const fs = require('node:fs');
const path = require('node:path');

function indiaDay(date = new Date()) {
  const parts = new Intl.DateTimeFormat('en-GB', {timeZone: 'Asia/Kolkata', year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(date);
  const value = type => parts.find(part => part.type === type).value;
  return `${value('year')}-${value('month')}-${value('day')}`;
}

function createActivityAudit(directory, {enabled = true, day = indiaDay(), maxBytes = 100 * 1024 * 1024} = {}) {
  const status = {enabled, day, events: 0, errors: 0, detail: ''};
  function append(event) {
    if (!enabled || indiaDay() !== day) return;
    try {
      fs.mkdirSync(directory, {recursive: true, mode: 0o700});
      const file = path.join(directory, `activity-${day}.jsonl`);
      const raw = String(event.message || '');
      const body = JSON.stringify({...event, message: raw.slice(0, 100000), textTruncated: raw.length > 100000}) + '\n';
      const current = fs.existsSync(file) ? fs.statSync(file).size : 0;
      if (current + Buffer.byteLength(body) > maxBytes) throw new Error('Daily activity log reached 100 MB limit');
      fs.appendFileSync(file, body, {encoding: 'utf8', mode: 0o600});
      status.events++; status.detail = '';
    } catch (error) {
      status.errors++; status.detail = String(error.message || error);
    }
  }
  return {append, status};
}

function csvCell(value) {
  let text = String(value ?? '');
  if (/^[\s]*[=+@-]/u.test(text)) text = "'" + text;
  return '"' + text.replaceAll('"', '""') + '"';
}

function mergeEvent(rows, event) {
  if (!event.messageId || !event.groupId) return;
  const key = `${event.groupId}|${event.messageId}`;
  const previous = rows.get(key);
  rows.set(key, {...previous, ...event, firstSeenAt: previous?.firstSeenAt || event.at});
}

module.exports = {indiaDay, createActivityAudit, csvCell, mergeEvent};
