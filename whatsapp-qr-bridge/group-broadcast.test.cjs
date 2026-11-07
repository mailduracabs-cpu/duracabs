'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const vm = require('node:vm');

// Exercise the production sender without starting Chrome, HTTP or WhatsApp.
const source = fs.readFileSync(path.join(__dirname, 'server.cjs'), 'utf8');
const sender = source.slice(source.indexOf('function saveBroadcast('), source.indexOf('async function processGroupBroadcasts('));
function harness(t, send = async () => {}) {
  const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'taxi-group-test-'));
  t.after(() => fs.rmSync(directory, {recursive: true, force: true}));
  const selected = new Set(['111@g.us', '222@g.us']);
  const calls = [], acknowledgements = [];
  const context = vm.createContext({
    fs, path, URL, Promise, Date, AbortSignal, clearTimeout,
    setTimeout: (fn, ms) => ms === 500 ? (fn(), 0) : setTimeout(fn, ms),
    selected, connected: true, stopping: false,
    groupBroadcastDir: directory, groupOutbox: new URL('https://example.test/outbox'),
    groupBroadcastSent: 0, groupBroadcastFailed: 0, groupBroadcastDetail: '',
    client: {sendMessage: async (id, message) => {calls.push(id); await send(id, message);}},
    preview: String, secret: 'test-secret', signature: () => 'sha256=test',
    fetch: async (_url, request) => {
      acknowledgements.push(JSON.parse(request.body));
      return {ok: true};
    },
  });
  vm.runInContext(sender, context);
  return {context, selected, calls, acknowledgements, directory, run: job => context.sendGroupBroadcast(job)};
}
const job = {id: 9, post_id: 20, post_revision: 1, lease_token: 'a'.repeat(64), message: 'New public booking'};

test('repeated delivery uses durable per-group receipts and the current lease', async t => {
  const h = harness(t);
  await h.run(job);
  await h.run({...job, lease_token: 'b'.repeat(64)});
  assert.deepEqual(h.calls, ['111@g.us', '222@g.us']);
  assert.equal(h.acknowledgements.at(-1).lease_token, 'b'.repeat(64));
});
test('a crash during send is treated as unknown and is not replayed', async t => {
  const h = harness(t);
  fs.writeFileSync(path.join(h.directory, '9.json'), JSON.stringify({groups: {'111@g.us': {status: 'sending'}}}));
  await h.run(job);
  assert.deepEqual(h.calls, ['222@g.us']);
  assert.equal(h.acknowledgements.at(-1).result, 'partial');
});
test('failed or uncertain sends are recorded and never blindly repeated', async t => {
  const h = harness(t, async id => {if (id === '111@g.us') throw new Error('connection lost');});
  await h.run(job);
  await h.run(job);
  assert.deepEqual(h.calls, ['111@g.us', '222@g.us']);
  assert.equal(h.acknowledgements.at(-1).result, 'partial');
});
test('empty selection retries without dispatching a message', async t => {
  const h = harness(t); h.selected.clear();
  await h.run(job);
  assert.equal(h.calls.length, 0);
  assert.equal(h.acknowledgements.at(-1).result, 'retry');
});
test('deselecting a group during a broadcast prevents its send', async t => {
  let h;
  h = harness(t, async () => h.selected.delete('222@g.us'));
  await h.run(job);
  assert.deepEqual(h.calls, ['111@g.us']);
});
test('invalid jobs are rejected before sending', async t => {
  const h = harness(t);
  await assert.rejects(h.run({...job, id: '../file'}));
  assert.equal(h.calls.length, 0);
});
