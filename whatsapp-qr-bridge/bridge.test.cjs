const {test} = require('node:test');
const assert = require('node:assert/strict');
const {envelope, signature, safeEqual} = require('./bridge.cjs');
test('only selected new incoming group text is forwarded', () => {
 const msg={from:'123@g.us', author:'919999999999@c.us',timestamp:100,type:'chat',body:'required sedan Agra to Delhi tomorrow 5pm 919999999999',id:{_serialized:'message-1'}};
 const groups=new Set(['123@g.us']);
 assert.equal(envelope(msg,groups,100).entry[0].changes[0].value.messages[0].group_id,'123@g.us');
 for(const overrides of [{from:'123@c.us'},{from:'456@g.us'},{fromMe:true},{timestamp:99},{type:'image'},{body:''},{id:{}},{body:'a'.repeat(5001)}])assert.equal(envelope({...msg,...overrides},groups,100),null);
});
test('signatures bind exact payload; password comparison is exact',()=>{
 assert.notEqual(signature('secret','a'),signature('secret','b'));
 assert.equal(signature('secret','a').length,71);
 assert.equal(safeEqual('admin','admin'),true);
 assert.equal(safeEqual('admin','admi'),false);
});

const {messageKey,skipReason}=require('./bridge.cjs');
test('long, string, raw and underlying WhatsApp keys become stable bounded IDs',()=>{
 const base={from:'123@g.us',author:'919999999999@c.us',timestamp:100,type:'chat',body:'required sedan'};
 for(const key of [{_serialized:'x'.repeat(1200)},'serialized-key',{id:'raw-key',remote:'123@g.us',participant:'919999999999@c.us'}]){
  const msg={...base,id:key};
  assert.match(messageKey(msg),/^[a-f0-9]{64}$/);
  assert.equal(skipReason(msg,new Set(['123@g.us']),100),null);
  const payload=envelope(msg,new Set(['123@g.us']),100);
  assert.equal(payload.entry[0].changes[0].value.messages[0].id.length,67);
  assert.equal(messageKey(msg),messageKey({...msg}));
 }
 assert.equal(messageKey({...base,id:{},_data:{id:{_serialized:'raw-serialized'}}}),messageKey({...base,id:{_serialized:'raw-serialized'}}));
 assert.equal(messageKey({...base,id:{}}),null);
 assert.notEqual(messageKey({...base,id:{id:'raw-key'}}),messageKey({...base,from:'456@g.us',id:{id:'raw-key'}}));
});
