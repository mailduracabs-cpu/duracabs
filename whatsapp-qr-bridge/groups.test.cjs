const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const {loadGroups, normalizeGroups} = require('./groups.cjs');
test('normalizes group IDs, excludes personal chats, deduplicates and sorts',()=>{
 assert.deepEqual(normalizeGroups([{id:{_serialized:'2@g.us'},name:'Z'},{id:'1@g.us',name:'A'},{id:'2@g.us',name:'Z'},{id:'3@c.us',name:'Private'},{}]),[{id:'1@g.us',name:'A'},{id:'2@g.us',name:'Z'}]);
});
test('normal getChats route returns groups without browser fallback',async()=>{
 const result=await loadGroups({getChats:async()=>[{id:{_serialized:'1@g.us'},name:'Taxi'}]},30);
 assert.equal(result[0].name,'Taxi');
});
test('broken chat serialization falls back to group-only browser models',async()=>{
 const models=[{id:{_serialized:'2@g.us'},groupMetadata:{subject:'Taxi group'}},{id:{_serialized:'3@c.us'},name:'Personal'}];
 const client={getChats:async()=>{throw Error('Chat serialization failed');},pupPage:{evaluate:fn=>vm.runInNewContext('('+fn.toString()+')()',{window:{require:()=>({Chat:{getModelsArray:()=>models}})}})}};
 const result=await loadGroups(client,50);
 assert.deepEqual(result,[{id:'2@g.us',name:'Taxi group'}]);
});
test('a hanging getChats call times out then falls back',async()=>{
 const result=await loadGroups({getChats:()=>new Promise(()=>{}),pupPage:{evaluate:async()=>[{id:'1@g.us',name:'Taxi'}]}},10);
 assert.equal(result.length,1);
});
test('empty synced collection returns empty, while failing loaders reject visibly',async()=>{
 assert.deepEqual(await loadGroups({getChats:async()=>[],pupPage:{evaluate:async()=>[]}},10),[]);
 await assert.rejects(loadGroups({getChats:async()=>{throw Error('broken');},pupPage:{evaluate:()=>new Promise(()=>{})}},10),/Groups could not load/);
});
