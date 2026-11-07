const {test}=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const {sendInterest}=require('./interest-send.cjs');
const payload={id:'live-interest:1',phone:'919876543210',text:'Interested in booking #1. Agra to Delhi.'};
function dir(t){const d=fs.mkdtempSync(path.join(os.tmpdir(),'taxi-interest-'));t.after(()=>fs.rmSync(d,{recursive:true,force:true}));return d;}
test('one send per notification across repeats and altered payload rejection',async t=>{
 const d=dir(t);let sends=0;const client={getNumberId:async()=>({_serialized:'919876543210@c.us'}),sendMessage:async()=>{sends++;return{id:{_serialized:'sent-1'}};}};
 assert.equal((await sendInterest(payload,client,d)).status,'sent');
 assert.equal((await sendInterest(payload,client,d)).status,'sent');
 assert.equal((await sendInterest({...payload,text:'different'},client,d)).status,'failed');assert.equal(sends,1);
});
test('simultaneous attempts claim the persistent send slot once',async t=>{
 const d=dir(t);let sends=0;const client={getNumberId:async()=>({_serialized:'phone@c.us'}),sendMessage:async()=>{sends++;return{id:{_serialized:'sent'}};}};
 await Promise.all([sendInterest(payload,client,d),sendInterest(payload,client,d)]);assert.equal(sends,1);
});
test('unknown delivery is never automatically resent',async t=>{
 const d=dir(t);let sends=0;const client={getNumberId:async()=>({_serialized:'phone@c.us'}),sendMessage:()=>{sends++;return new Promise(()=>{});}};
 assert.equal((await sendInterest(payload,client,d,{sendTimeout:5})).status,'unknown');
 assert.equal((await sendInterest(payload,client,d)).status,'unknown');assert.equal(sends,1);
});
test('invalid payload and unregistered phone do not send',async t=>{
 const d=dir(t);let sends=0;const client={getNumberId:async()=>null,sendMessage:()=>sends++};
 assert.equal((await sendInterest({...payload,phone:'bad'},client,d)).status,'failed');
 assert.equal((await sendInterest(payload,client,d)).status,'failed');assert.equal(sends,0);
});
