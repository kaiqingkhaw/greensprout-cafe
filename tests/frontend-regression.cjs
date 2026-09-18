'use strict';
const assert=require('node:assert/strict'), fs=require('node:fs'), path=require('node:path'), vm=require('node:vm');
const root=path.resolve(__dirname,'..');
const nodes=new Map();
function element(id){if(!nodes.has(id)) nodes.set(id,{value:'',hidden:false,attrs:{},events:{},addEventListener(type,fn){this.events[type]=fn;},setAttribute(k,v){this.attrs[k]=v;},removeAttribute(k){delete this.attrs[k];},focus(){}});return nodes.get(id);}
const context={window:{addEventListener(){}},document:{getElementById:element},Date};
vm.createContext(context); vm.runInContext(fs.readFileSync(path.join(root,'assets/js/demo-payment.js'),'utf8'),context);
const payment=context.window.DemoPayment;
assert.equal(payment.validate('cash'),true);
assert.equal(payment.validate('card_demo'),false);
element('fill-test-card').events.click();assert.equal(payment.validate('card_demo'),true);
element('demo-card-number').value='4111 1111 1111 1111';assert.equal(payment.validate('card_demo'),false);
element('fill-test-card').events.click();payment.select('ewallet_demo');
for(const name of ['name','number','expiry','cvc']) assert.equal(element('demo-card-'+name).value,'');
assert.equal(element('card-panel').hidden,true);assert.equal(element('wallet-panel').hidden,false);
payment.setContext('RM24.85','toast');
assert.equal(payment.validate('ewallet_demo'),false);
element('approve-demo-wallet').events.click();
assert.equal(payment.validate('ewallet_demo'),true);
payment.setContext('RM24.85','toast');
assert.equal(payment.validate('ewallet_demo'),true);
payment.setContext('RM24.85','different dish');
assert.equal(payment.validate('ewallet_demo'),false);
element('approve-demo-wallet').events.click();
payment.select('cash');
assert.equal(payment.validate('ewallet_demo'),false);
console.log('PASS wallet approval, cart-change invalidation and clearing on method change.');
console.log('PASS test-card validation, sample fill, non-test rejection and clearing on method change (no network/storage APIs available).');
const tracking={};vm.createContext(tracking);vm.runInContext(fs.readFileSync(path.join(root,'assets/js/tracking-model.js'),'utf8'),tracking);
for(const [status,index] of [['pending',0],['preparing',1],['ready',2],['completed',3],['cancelled',-1]]) assert.equal(vm.runInContext(`GreenSproutTracking.view('${status}').index`,tracking),index);
assert.equal(vm.runInContext("GreenSproutTracking.view('unrecognised').label",tracking),'Status unavailable');
console.log('PASS tracking states, cancellation and unknown status.');
for(const name of fs.readdirSync(root).filter(name=>name.endsWith('.php'))){
 const text=fs.readFileSync(path.join(root,name),'utf8');
 for(const script of text.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)) {
  const source=script[1].replace(/<\?(?:php|=)[\s\S]*?\?>/g,'null');if(source.trim()) new vm.Script(source,{filename:name});
 }
}
console.log('PASS embedded PHP-page JavaScript syntax.');
// Run the actual admin status handler against success, API failure and network failure.
(async()=>{
 const page=fs.readFileSync(path.join(root,'admin_panel.php'),'utf8');
 const handler=page.slice(page.indexOf('function updateOrderStatus('),page.indexOf('function deleteOrder('));
 for(const mode of ['success','rejected','offline']) {
  const select={disabled:false,value:'ready',dataset:{savedStatus:'pending'}};
  const ctx={document:{querySelector:()=>select},csrfToken:'test-only',URLSearchParams,showMessage(){},GS:{fetch:async()=>{if(mode==='offline')throw Error('offline');return {json:async()=>({success:mode==='success',error:'Test rejection'})};}}};
  vm.createContext(ctx);vm.runInContext(handler+'; updateOrderStatus(1,"ready");',ctx);
  await new Promise(resolve=>setImmediate(resolve));
  assert.equal(select.disabled,false);
  assert.equal(select.value,mode==='success'?'ready':'pending');
  assert.equal(select.dataset.savedStatus,mode==='success'?'ready':'pending');
 }
 console.log('PASS admin status success, rejection rollback and network-failure rollback.');
})().catch(error=>{console.error(error);process.exitCode=1;});
