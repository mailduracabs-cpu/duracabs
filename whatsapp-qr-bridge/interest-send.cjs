'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
function waitFor(work,ms){let timer;return Promise.race([Promise.resolve().then(work),new Promise((_,reject)=>{timer=setTimeout(()=>reject(Error('timeout')),ms);})]).finally(()=>clearTimeout(timer));}
async function sendInterest(payload,client,directory,{lookupTimeout=8000,sendTimeout=15000}={}){
 if(!/^live-interest:\d+$/.test(payload.id||'')||!/^\d{8,15}$/.test(payload.phone||'')||typeof payload.text!=='string'||!payload.text.trim()||payload.text.length>6000)return {status:'failed'};
 fs.mkdirSync(directory,{recursive:true,mode:0o700});
 const key=crypto.createHash('sha256').update(payload.id).digest('hex');
 const digest=crypto.createHash('sha256').update(JSON.stringify([payload.phone,payload.text])).digest('hex');
 const file=path.join(directory,key+'.json');
 try{fs.writeFileSync(file,JSON.stringify({digest,status:'unknown'}),{flag:'wx',mode:0o600});}
 catch(e){if(e.code!=='EEXIST')throw e;const saved=JSON.parse(fs.readFileSync(file,'utf8'));return saved.digest===digest?{status:saved.status,message_id:saved.message_id}:{status:'failed'};}
 let result={status:'unknown'};
 try{
  const recipient=await waitFor(()=>client.getNumberId(payload.phone),lookupTimeout);
  if(!recipient?._serialized)result={status:'failed'};
  else{
   const sent=await waitFor(()=>client.sendMessage(recipient._serialized,payload.text,{sendSeen:false}),sendTimeout);
   const id=sent?.id?._serialized;
   result=id?{status:'sent',message_id:String(id).slice(0,191)}:{status:'unknown'};
  }
 }catch(_){/* Preserve uncertain outcome; never send again automatically. */}
 fs.writeFileSync(file,JSON.stringify({digest,...result}),{mode:0o600});
 return result;
}
module.exports={sendInterest};
