'use strict';
const fs = require('node:fs');
const path = require('node:path');
const readline = require('node:readline');
const {indiaDay, mergeEvent, csvCell} = require('./activity-audit.cjs');

async function main() {
  const day = process.argv[2] || indiaDay();
  if (!/^\d{4}-\d{2}-\d{2}$/.test(day)) throw new Error('Use a date in YYYY-MM-DD format.');
  const dir = path.join(__dirname, 'data', 'activity-audit');
  const input = path.join(dir, `activity-${day}.jsonl`);
  if (!fs.existsSync(input)) throw new Error(`No activity log for ${day}. Start the updated bridge and wait for selected-group text messages.`);
  const rows = new Map();
  let damagedLines = 0;
  const stream = readline.createInterface({input: fs.createReadStream(input, {encoding: 'utf8'}), crlfDelay: Infinity});
  for await (const line of stream) {
    if (!line.trim()) continue;
    try { mergeEvent(rows, JSON.parse(line)); } catch (_) { damagedLines++; }
  }
  const columns = ['message_id', 'first_seen_utc', 'whatsapp_timestamp', 'last_update_utc', 'group_id', 'group_name', 'system_outcome', 'system_reason', 'message', 'text_truncated', 'review_label', 'expected_pickup', 'expected_drop', 'expected_date', 'expected_time', 'expected_vehicle', 'review_note'];
  const output = path.join(dir, `review-${day}.csv`);
  const lines = [columns.map(csvCell).join(',')];
  const counts = {};
  for (const row of rows.values()) {
    counts[row.outcome] = (counts[row.outcome] || 0) + 1;
    lines.push([row.messageId, row.firstSeenAt, row.whatsappTimestamp, row.at, row.groupId, row.groupName, row.outcome, row.reason, row.message, row.textTruncated, '', '', '', '', '', '', ''].map(csvCell).join(','));
  }
  // Re-running the export creates an independent snapshot and preserves any labels
  // added to an earlier CSV. No live messages or bookings are submitted here.
  const snapshot = output.replace('.csv', `-${Date.now()}.csv`);
  fs.writeFileSync(snapshot, '\uFEFF' + lines.join('\r\n') + '\r\n', {encoding: 'utf8', mode: 0o600, flag: 'wx'});
  console.log(`Saved: ${snapshot}`);
  console.log(JSON.stringify({date: day, uniqueMessages: rows.size, outcomes: counts, damagedLines}, null, 2));
}
main().catch(error => { console.error(error.message); process.exitCode = 1; });
