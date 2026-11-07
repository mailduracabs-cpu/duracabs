'use strict';
const crypto = require('node:crypto');
function safeEqual(a, b) {
  const x = Buffer.from(String(a)), y = Buffer.from(String(b));
  return x.length === y.length && crypto.timingSafeEqual(x, y);
}
function signature(secret, body) {
  return 'sha256=' + crypto.createHmac('sha256', secret).update(body).digest('hex');
}
function messageKey(message) {
  // WhatsApp Web IDs can be strings, serialized objects or raw key objects.
  // Hash the stable key; the database never needs the potentially long wire ID.
  const candidates = [message.id, message._data?.id];
  for (const candidate of candidates) {
    const serialized = typeof candidate === 'string' ? candidate : candidate?._serialized;
    if (typeof serialized === 'string' && serialized.trim()) {
      return crypto.createHash('sha256').update(serialized).digest('hex');
    }
  }
  for (const candidate of candidates) {
    if (candidate && typeof candidate.id === 'string' && candidate.id.trim()) {
      const remote = typeof candidate.remote === 'string' ? candidate.remote : candidate.remote?._serialized;
      const participant = typeof candidate.participant === 'string' ? candidate.participant : candidate.participant?._serialized;
      return crypto.createHash('sha256').update(JSON.stringify([
        'raw-key-v1', remote || String(message.from || ''), participant || String(message.author || ''), candidate.id
      ])).digest('hex');
    }
  }
  return null;
}
function skipReason(message, selected, startedAt) {
  const group = String(message.from || '');
  if (!group.endsWith('@g.us')) return 'personal_chat';
  if (!selected.has(group)) return 'unselected_group';
  if (message.fromMe) return 'own_message';
  if (!Number.isFinite(Number(message.timestamp)) || Number(message.timestamp) < startedAt) return 'old_message';
  // Captions are text evidence too; never download media or infer text from pixels.
  if (!['chat', 'image', 'video', 'document'].includes(message.type)) return 'not_text';
  if (typeof message.body !== 'string' || !message.body.trim() || message.body.length > 5000) return 'invalid_text';
  if (!messageKey(message)) return 'invalid_id';
  return null;
}
function envelope(message, selected, startedAt) {
  if (skipReason(message, selected, startedAt)) return null;
  const group = String(message.from), id = messageKey(message);
  return {entry: [{changes: [{value: {messages: [{
    id: 'qr:' + id, group_id: group, from: String(message.author || ''), type: 'text',
    text: {body: message.body}, timestamp: String(message.timestamp)
  }]}}]}]};
}
module.exports = {safeEqual, signature, envelope, skipReason, messageKey};
