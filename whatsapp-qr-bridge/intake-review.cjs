'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto');
function createIntakeReview(directory){
 fs.mkdirSync(directory,{recursive:true,mode:0o700});
 return function save(message,reason,status='needs_review'){
  const key=crypto.createHash('sha256').update(`${message.group_id||''}|${message.id||''}`).digest('hex');
  const target=path.join(directory,key+'.json');
  if(status!=='needs_review'&&!fs.existsSync(target))return;
  const previous=fs.existsSync(target)?JSON.parse(fs.readFileSync(target,'utf8')):null;
  const now=new Date().toISOString();
  const value={message_id:message.id,group_id:message.group_id,whatsapp_timestamp:message.timestamp,message:message.text?.body||'',reason,status,first_saved_at:previous?.first_saved_at||now,updated_at:now};
  const temp=target+'.'+crypto.randomBytes(8).toString('hex')+'.tmp';
  try{fs.writeFileSync(temp,JSON.stringify(value),{encoding:'utf8',mode:0o600,flag:'wx'});fs.renameSync(temp,target);}
  finally{if(fs.existsSync(temp))fs.unlinkSync(temp);}
 };
}
module.exports={createIntakeReview};
