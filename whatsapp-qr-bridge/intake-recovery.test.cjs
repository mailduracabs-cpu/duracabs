'use strict';
const test=require('node:test'),assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const {skipReason,envelope,messageKey}=require('./bridge.cjs');
const {createIntakeReview}=require('./intake-review.cjs');
const group='120363123456789@g.us',selected=new Set([group]);
const base={from:group,author:'919999999999',fromMe:false,timestamp:1001,id:{_serialized:'fixture-message-id'},type:'chat',body:'Required Pickup Noida Drop Jaipur Dzire 6 Nov 8 AM 9999999999'};
for(const type of ['chat','image','video','document'])test(type+' text/caption reaches signed text intake',()=>{
 const message={...base,type};assert.equal(skipReason(message,selected,1000),null);
 const m=envelope(message,selected,1000).entry[0].changes[0].value.messages[0];assert.equal(m.type,'text');assert.equal(m.text.body,base.body);assert.equal(m.timestamp,'1001');
});
test('empty media caption rejected',()=>assert.equal(skipReason({...base,type:'image',body:''},selected,1000),'invalid_text'));
test('audio is not treated as a caption',()=>assert.equal(skipReason({...base,type:'ptt'},selected,1000),'not_text'));
test('own messages remain excluded',()=>assert.equal(skipReason({...base,type:'image',fromMe:true},selected,1000),'own_message'));
test('unselected groups remain excluded',()=>assert.equal(skipReason({...base,type:'image'},new Set(),1000),'unselected_group'));
test('old events remain excluded',()=>assert.equal(skipReason({...base,type:'image',timestamp:999},selected,1000),'old_message'));
test('ID is independent of media type',()=>assert.equal(messageKey(base),messageKey({...base,type:'image'})));
test('review keeps the full source and original timestamp, then tracks resolution',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'intake-review-'));
 try{
  const save=createIntakeReview(dir),m={id:'qr:test',group_id:group,timestamp:'1001',text:{body:base.body}};
  save(m,'ai_incomplete_json');const files=fs.readdirSync(dir);assert.equal(files.length,1);
  const file=path.join(dir,files[0]),r=JSON.parse(fs.readFileSync(file));assert.equal(r.message,base.body);assert.equal(r.whatsapp_timestamp,'1001');assert.equal(r.status,'needs_review');
  save(m,'booking_created','imported');const resolved=JSON.parse(fs.readFileSync(file));assert.equal(resolved.status,'imported');assert.equal(resolved.first_saved_at,r.first_saved_at);assert.equal(fs.readdirSync(dir).length,1);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
test('ordinary successful imports do not create review records',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'intake-review-'));
 try{createIntakeReview(dir)({id:'qr:success',group_id:group,text:{body:'test'}},'booking_created','imported');assert.deepEqual(fs.readdirSync(dir),[]);}
 finally{fs.rmSync(dir,{recursive:true,force:true});}
});
const vm=require('node:vm');
const serverSource=fs.readFileSync(path.join(__dirname,'server.cjs'),'utf8');
const deliverySource=serverSource.slice(serverSource.indexOf('const intakeRetryAfter ='),serverSource.indexOf('setInterval(() => void deliver()'));
function deliveryHarness(t,reply){
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'intake-delivery-'));t.after(()=>fs.rmSync(dir,{recursive:true,force:true}));
 const outbox=path.join(dir,'outbox');fs.mkdirSync(outbox);const events=[],calls=[];
 const review=path.join(dir,'review');
 const context=vm.createContext({fs,path,Date,AbortSignal,console,delivering:false,stopping:false,outbox,selected,
  accepted:0,ignored:0,reviewed:0,diagnostics:{expired_queue:0,deselected_queue:0,backend_reasons:{}},detail:'',
  webhook:'http://localhost.test',secret:'test',signature:()=> 'test',saveIntakeReview:createIntakeReview(review),
  rememberActivity:(...args)=>events.push(args),fetch:async (_url,request)=>{calls.push(JSON.parse(request.body));return reply(calls.length);},
 });
 vm.runInContext(deliverySource,context);
 function queue(letter,age=0){const name=letter.repeat(64)+'.json';const message={id:'qr:'+letter,group_id:group,from:'test',type:'text',text:{body:base.body},timestamp:String(Math.floor(Date.now()/1000)-age)};fs.writeFileSync(path.join(outbox,name),JSON.stringify({entry:[{changes:[{value:{messages:[message]}}]}]}));return name;}
 return {context,outbox,review,events,calls,queue,run:()=>context.deliver()};
}
test('technical failure does not block other incoming bookings',async t=>{
 const h=deliveryHarness(t,n=>n===1?{ok:false,status:503,json:async()=>({reviewed:1,reasons:{needs_review_ai_incomplete_json:1}})}:{ok:true,json:async()=>({status:'ok',imported:1,ignored:0,reviewed:0,reasons:{}})});
 const first=h.queue('a');h.queue('b');await h.run();assert.equal(h.calls.length,2);assert.equal(h.context.accepted,1);assert.deepEqual(fs.readdirSync(h.outbox),[first]);assert.equal(JSON.parse(fs.readFileSync(path.join(h.review,fs.readdirSync(h.review)[0]))).status,'needs_review');
});
test('expired retry queue is archived instead of silently discarded',async t=>{
 const h=deliveryHarness(t,()=>{throw Error('must not call backend');});h.queue('c',301);await h.run();assert.equal(h.calls.length,0);assert.equal(h.context.reviewed,1);assert.equal(h.context.ignored,0);assert.deepEqual(fs.readdirSync(h.outbox),[]);assert.equal(h.events[0][1],'review');assert.equal(fs.readdirSync(h.review).length,1);
});
test('incomplete booking gets a review outcome and keeps full source',async t=>{
 const h=deliveryHarness(t,()=>({ok:true,json:async()=>({status:'ok',imported:0,ignored:0,reviewed:1,reasons:{needs_review_missing_car_type:1}})}));h.queue('d');await h.run();assert.equal(h.context.reviewed,1);assert.equal(h.context.ignored,0);assert.equal(h.events[0][1],'review');const record=JSON.parse(fs.readFileSync(path.join(h.review,fs.readdirSync(h.review)[0])));assert.equal(record.message,base.body);
});
test('archive storage failure leaves original queue file intact',async t=>{
 const h=deliveryHarness(t,()=>{throw Error('must not call backend');});const file=h.queue('e',301);h.context.saveIntakeReview=()=>{throw Error('disk full');};await h.run();assert.deepEqual(fs.readdirSync(h.outbox),[file]);assert.equal(h.context.reviewed,0);
});
