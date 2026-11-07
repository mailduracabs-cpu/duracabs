'use strict';
function deadline(work, ms) {
  let timer;
  return Promise.race([Promise.resolve().then(work), new Promise((_, reject) => {
    timer = setTimeout(() => reject(new Error('Group loading timed out.')), ms);
  })]).finally(() => clearTimeout(timer));
}
function normalizeGroups(rows) {
  if (!Array.isArray(rows)) return [];
  const result = new Map();
  for (const row of rows) {
    const id = typeof row?.id === 'string' ? row.id : row?.id?._serialized;
    if (typeof id !== 'string' || !/^\d+(?:-\d+)?@g\.us$/.test(id)) continue;
    const name = String(row.name || row.subject || id).slice(0, 300);
    result.set(id, {id, name});
  }
  return [...result.values()].sort((a, b) => a.name.localeCompare(b.name));
}
async function loadGroups(client, timeout = 12000) {
  let reason = '';
  try {
    const groups = normalizeGroups(await deadline(() => client.getChats(), timeout));
    if (groups.length) return groups;
  } catch (e) { reason = String(e.message).split('\n')[0].slice(0, 200); }
  // Full Chat serialization may fail on an unrelated personal chat after Web updates.
  // Fall back to group IDs/names only from the same linked browser's collection.
  try {
    const rows = await deadline(() => client.pupPage.evaluate(() => {
      let collection;
      try { collection = window.require('WAWebCollections').Chat; } catch (_) {}
      collection = collection || window.Store?.Chat;
      if (!collection) throw new Error('WhatsApp chat collection is not ready.');
      const models = typeof collection.getModelsArray === 'function' ? collection.getModelsArray() : collection.models;
      if (!Array.isArray(models)) throw new Error('WhatsApp chat collection is not supported yet.');
      return models.filter(chat => String(chat.id?._serialized || '').endsWith('@g.us')).map(chat => ({
        id: chat.id._serialized, name: chat.name || chat.formattedTitle || chat.groupMetadata?.subject || chat.id._serialized
      }));
    }), timeout);
    return normalizeGroups(rows);
  } catch (e) {
    const fallback = String(e.message).split('\n')[0].slice(0, 200);
    console.error('WhatsApp group loading failed:', reason || 'No groups in getChats()', '|', fallback);
    throw new Error('Groups could not load from WhatsApp. Restart the bridge, wait for connected and retry. If this repeats, send the CMD group-loading error to support.');
  }
}
module.exports = {loadGroups, normalizeGroups, deadline};
