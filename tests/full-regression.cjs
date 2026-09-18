/* Opt-in localhost regression. Creates only uniquely named QA records and cleans them up. */
'use strict';
const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const {randomBytes} = require('node:crypto');
const path = require('node:path');
if (process.env.ALLOW_TEST_WRITES !== '1') { console.error('Set ALLOW_TEST_WRITES=1 to create and clean up temporary QA records.'); process.exit(1); }
const base = process.env.TEST_BASE_URL || 'http://localhost/GreenSproutCafe-Portfolio/public/';
if (!['localhost','127.0.0.1'].includes(new URL(base).hostname)) throw Error('This fixture suite is localhost-only.');
const root = path.resolve(__dirname,'..');
const tag = 'gsaudit-' + randomBytes(6).toString('hex');
const password = randomBytes(18).toString('hex');
const names = ['admin','customer','other'].map(role => tag+'-'+role);
let checks=0, uploadPaths=[], seeded=false;
function php(source) {
 const run = spawnSync(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', ['-r', "require 'app/config.php'; "+source], {cwd:root,encoding:'utf8',env:{...process.env,QA_NAMES:JSON.stringify(names),QA_PASSWORD:password,QA_TAG:tag,QA_UPLOADS:JSON.stringify(uploadPaths)}});
 if (run.status !== 0) throw Error(run.stderr || 'Fixture command failed');
 return run.stdout.trim();
}
const pass = label => {checks++; console.log('PASS '+label);};
const status = (result,expected,label) => {assert.equal(result.status,expected,label+' '+JSON.stringify(result.body).slice(0,250)); pass(label);};
function session() {
 let cookie='';
 async function get(route,options={}) {
  const response=await fetch(base+route,{redirect:'manual',...options,headers:{Cookie:cookie,...options.headers}});
  if(response.headers.get('set-cookie')) cookie=response.headers.get('set-cookie').split(';')[0];
  const text=await response.text(); let body; try {body=JSON.parse(text);} catch {body=text;}
  return {status:response.status,body,location:response.headers.get('location')};
 }
 async function post(route,data={},json=false) {
  const token=(await get('csrf.php')).body.csrfToken;
  let body;
  if(json) body=JSON.stringify(data);
  else if(data instanceof FormData) {data.set('csrf_token',token); body=data;}
  else body=new URLSearchParams({...data,csrf_token:token});
  return get(route,{method:'POST',headers:{'X-CSRF-Token':token,...(json?{'Content-Type':'application/json'}:{})},body});
 }
 return {get,post};
}
const guest=session(), customer=session(), other=session(), admin=session();
const info = who => ({name:who,username:who,email:who+'@example.invalid',phone:'0123456789',password,confirm_password:password});
const profile = {update_profile:'1',name:'QA Customer',email:names[1]+'@example.invalid',phone:'0123456789'};
function upload(bytes,type,name) {const f=new FormData(); Object.entries(profile).forEach(([k,v])=>f.set(k,v));f.set('profile_image',new Blob([bytes],{type}),name);return f;}
const gif=Buffer.from('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7','base64');
(async()=>{
 php(`$names=json_decode(getenv('QA_NAMES'),true);$u=$names[0];$e=$u.'@example.invalid';$p=password_hash(getenv('QA_PASSWORD'),PASSWORD_DEFAULT);$s=db()->prepare("INSERT INTO users(username,name,email,phone,password,role) VALUES(?,?,?,'0123456789',?,'admin')");$s->bind_param('ssss',$u,$u,$e,$p);$s->execute();`);
 seeded=true;
 for(const page of ['GreenSproutCafe.html','MainMenu.html','About.us.html','menu.html','login.html','signup.html']) status(await guest.get(page),200,'public '+page);
 for(const route of ['profile.php','admin_home.php','admin_panel.php','admin_profile.php','admin_manage_menu.php','admin_overview.php','admin_edit_product.php?mode=add','admin_export.php?type=orders']) status(await guest.get(route),302,'guest denied '+route);
 for(const route of ['get_latest_order.php','get_order_details.php?order_number=invalid']) status(await guest.get(route),401,'private '+route);
 status(await guest.get('create_order.php'),405,'checkout requires POST');
 status(await guest.get('login.php',{method:'POST',body:new URLSearchParams({username:names[1],password})}),403,'login rejects missing CSRF');
 status(await guest.post('signup.php',{...info(names[1]),password:'short',confirm_password:'short'},true),422,'signup password validation');
 status(await guest.post('signup.php',info(names[1]),true),201,'customer signup');
 status(await guest.post('signup.php',info(names[2]),true),201,'second customer signup');
 status(await guest.post('signup.php',info(names[1]),true),409,'duplicate signup');
 status(await customer.post('login.php',{username:names[1],password:'incorrect'}),401,'incorrect login');
 status(await customer.post('login.php',{username:names[1],password}),200,'customer login');
 status(await other.post('login.php',{username:names[2],password}),200,'second customer login');
 status(await admin.post('login.php',{username:names[0],password}),200,'administrator login');
 status(await customer.get('admin_panel.php'),302,'customer denied admin');
 status(await customer.get('get_latest_order.php'),404,'new customer empty orders');
 for(const route of ['admin_home.php','admin_panel.php','admin_profile.php','admin_manage_menu.php','admin_overview.php','admin_edit_product.php?mode=add']) status(await admin.get(route),200,'admin '+route);
 let result=await admin.get('admin_overview.php?from=2026-09-05&to=2026-09-01');assert.equal(result.status,200);assert.match(result.body,/Showing 1 Sep 2026/);pass('insights validates and normalises date range');
 result=await admin.get('admin_overview.php?from=2026-01-01&to=2026-03-31');assert.match(result.body,/Jan 26/);assert.match(result.body,/Mar 26/);pass('insights chart follows selected months');
 status(await admin.get('admin_overview.php?from[]=invalid'),200,'malformed insight date handled');
 status(await admin.get('admin_export.php?type=orders&from[]=invalid'),200,'malformed export date handled');
 status(await admin.get('admin_export.php?type=unknown'),400,'reject unknown export');
 result=await admin.get('admin_export.php?type=orders&from=2026-01-01&to=2026-12-31');assert.equal(result.status,200);assert.match(result.body,/"Order number",Date,Customer/);pass('admin order CSV export');
 result=await admin.get('admin_export.php?type=customers');assert.equal(result.status,200);assert.match(result.body,/Username,Name,Email,Phone/);pass('admin customer CSV export');
 result=await admin.get('admin_export.php?type=reviews');assert.equal(result.status,200);assert.match(result.body,/Customer,Rating,Review/);pass('admin review CSV export');
 result=await customer.post('profile.php',upload(Buffer.from('<?php echo 1; ?>'),'image/gif','bad.gif'));
 assert.match(result.body,/Choose a valid JPG/); pass('reject executable disguised as photo');
 result=await customer.post('profile.php',upload(gif,'image/gif','test.gif'));assert.match(result.body,/Profile updated successfully/);pass('photo upload');
 uploadPaths=JSON.parse(php(`$u=json_decode(getenv('QA_NAMES'),true)[1];$s=db()->prepare('SELECT profile_image FROM users WHERE username=?');$s->bind_param('s',$u);$s->execute();echo json_encode([$s->get_result()->fetch_assoc()['profile_image']]);`));
 result=await customer.post('profile.php',{...profile,email:names[2]+'@example.invalid'});assert.match(result.body,/already registered/);assert.ok(result.body.includes(uploadPaths[0]));pass('duplicate profile email retains photo');
 result=await customer.post('profile.php',{...profile,remove_photo:'1'});assert.match(result.body,/Profile updated successfully/);assert.ok(!(await customer.get('profile.php')).body.includes(uploadPaths[0]));pass('photo removal persists');
 const product={name:tag,description:'QA product for isolated regression',price:'12.50',category:'Mains',image:'',discount_percent:'20',discounted:'on',calories:'100',protein:'5',carbs:'10',fats:'2',fiber:'3'};
 status(await admin.post('admin_edit_product.php?mode=add',{...product,price:'-1'}),422,'invalid product price');
 status(await admin.post('admin_edit_product.php?id=2147483647',{...product,id:2147483647}),404,'missing product cannot report a successful update');
 status(await admin.post('admin_edit_product.php?mode=add',product),302,'create product');
 let products=(await guest.get('get_products.php')).body;let p=products.find(p=>p.name===tag);assert.ok(p);pass('created product visible to customers');
 status(await admin.post('admin_edit_product.php?id='+p.id,{...product,id:p.id,price:'15.00'}),302,'edit product');
 products=(await guest.get('get_products.php')).body;p=products.find(item=>item.id===p.id);assert.equal(p.price,15);pass('edited product saved');
 const delivery={fullName:'QA Customer',email:profile.email,phone:profile.phone,address:'123 QA Street, Kuala Lumpur',specialInstructions:'QA only'};
 const payload={items:[{id:p.id,quantity:2,price:0.01,specialRequest:'No salt'}],delivery_info:delivery,discount_code:'ORGANIC10',request_key:tag+'-cash',payment_method:'cash',save_address:true};
 const invalid=[['quantity zero',{items:[{id:p.id,quantity:0}]}],['fractional quantity',{items:[{id:p.id,quantity:1.5}]}],['too many',{items:[{id:p.id,quantity:21}]}],['missing product',{items:[{id:2147483647,quantity:1}]}],['invalid payment',{payment_method:'real_card'}],['invalid discount',{discount_code:'NOPE'}],['non-text discount',{discount_code:[]}],['invalid address',{delivery_info:{...delivery,address:'Elsewhere'}}],['invalid phone',{delivery_info:{...delivery,phone:'abcdefgh'}}]];
 for(const [label,patch] of invalid) status(await customer.post('create_order.php',{...payload,...patch},true),422,label);
 result=await customer.post('create_order.php',payload,true);status(result,201,'create cash order');const number=result.body.order_number;assert.equal(result.body.total,20.2);pass('server discount, price, service and delivery total');
 assert.equal((await customer.post('create_order.php',payload,true)).body.order_number,number);pass('identical retry deduplicated');
 status(await customer.post('create_order.php',{...payload,payment_method:'card_demo'},true),409,'changed retry rejected');
 let receipt=(await customer.get('get_order_details.php?order_number='+number)).body;assert.equal(receipt.payment_method,'cash');assert.match(JSON.parse(receipt.items)[0].specialRequest,/No salt/);pass('saved invoice and special request');
 assert.equal((await customer.get('check_session.php')).body.user.address,delivery.address);pass('saved delivery address');
 status(await other.get('get_order_details.php?order_number='+number),404,'cross-account order blocked');
 for(const method of ['card_demo','ewallet_demo']) {const r=await customer.post('create_order.php',{...payload,payment_method:method,request_key:tag+'-'+method.replace('_','-')},true);status(r,201,method+' checkout');assert.equal((await customer.get('get_order_details.php?order_number='+r.body.order_number)).body.payment_method,method);pass(method+' invoice persists');}
 const ids=JSON.parse(php(`$names=json_decode(getenv('QA_NAMES'),true);$s=db()->prepare('SELECT id,username FROM users WHERE username IN (?,?,?)');$s->bind_param('sss',...$names);$s->execute();echo json_encode(array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'id','username'));`));
 status(await admin.post('admin_panel.php',{action:'delete_user',user_id:ids[names[1]]}),409,'protect customer order history');
 status(await admin.post('admin_panel.php',{action:'update_user',user_id:ids[names[1]],name:'QA Revised',email:profile.email,phone:profile.phone,username:names[1]}),200,'admin update customer');
 status(await admin.post('admin_panel.php',{action:'update_user',user_id:ids[names[1]],name:'QA Revised',email:names[2]+'@example.invalid',phone:profile.phone,username:names[1]}),409,'admin duplicate email conflict');
 status(await admin.post('admin_panel.php',{action:'update_order_status',order_id:receipt.id,status:'invalid'}),422,'invalid order status');
 status(await admin.post('admin_panel.php',{action:'update_order_status',order_id:0,status:'ready'}),404,'missing order status');
 for(const state of ['preparing','ready','completed','cancelled']) {status(await admin.post('admin_panel.php',{action:'update_order_status',order_id:receipt.id,status:state}),200,'set '+state);assert.equal((await customer.get('get_order_details.php?order_number='+number)).body.status,state);pass('customer reads '+state);}
 status(await customer.post('submit_review.php',{rating:6,review:'Invalid'},true),422,'invalid review rating');
 status(await customer.post('submit_review.php',{rating:5,review:tag+' review'},true),201,'submit review');
 assert.ok((await guest.get('get_reviews.php')).body.reviews.some(r=>r.review===tag+' review'));pass('review published');
 const reviewId=Number(php(`$v=getenv('QA_TAG').' review';$s=db()->prepare('SELECT id FROM reviews WHERE review=?');$s->bind_param('s',$v);$s->execute();echo $s->get_result()->fetch_assoc()['id'];`));
 status(await admin.post('admin_panel.php',{action:'delete_review',review_id:reviewId}),200,'moderate review');
 status(await guest.post('contact.php',{name:'QA',email:profile.email,subject:'QA',message:'tiny'}),422,'contact field validation');
 status(await guest.post('contact.php',{name:'QA',email:profile.email,subject:'QA',message:tag+' test contact'}),201,'save contact message');
 status(await admin.post('admin_profile.php',{action:'update_account',name:'QA Admin',username:names[0],email:names[0]+'@example.invalid'}),200,'admin account update');
 result=await admin.post('admin_profile.php',{action:'change_password',current_password:password,new_password:'            ',confirm_password:'            '});assert.match(result.body,/cannot contain only spaces/);pass('reject blank admin password');
 result=await admin.post('admin_profile.php',{action:'change_password',current_password:'bad',new_password:password+'a',confirm_password:password+'a'});assert.match(result.body,/current password is incorrect/);pass('reject wrong current admin password');
 result=await admin.post('admin_profile.php',{action:'change_password',current_password:password,new_password:password+'a',confirm_password:password+'a'});assert.match(result.body,/Password changed successfully/);pass('change temporary admin password');
 await admin.post('logout.php');status(await admin.post('login.php',{username:names[0],password}),401,'old password rejected');status(await admin.post('login.php',{username:names[0],password:password+'a'}),200,'new password works');
 status(await admin.post('admin_manage_menu.php',{action:'delete_product',product_id:p.id}),302,'delete temporary product');
 assert.equal(JSON.parse((await customer.get('get_order_details.php?order_number='+number)).body.items)[0].name,tag);pass('invoice retained after product deletion');
 status(await admin.post('admin_panel.php',{action:'delete_order',order_id:receipt.id}),200,'delete temporary order');status(await customer.get('get_order_details.php?order_number='+number),404,'deleted order absent');
 status(await admin.post('admin_panel.php',{action:'delete_user',user_id:ids[names[2]]}),200,'delete empty temporary customer');assert.equal((await other.get('check_session.php')).body.loggedIn,false);pass('deleted account session revoked');
 status(await admin.post('admin_panel.php',{action:'unrecognised'}),422,'unknown admin action');
 await customer.post('logout.php');assert.equal((await customer.get('check_session.php')).body.loggedIn,false);pass('logout');status(await customer.get('get_order_details.php?order_number='+number),401,'private invoice after logout');
 console.log(`COMPLETE: ${checks} checks passed.`);
})().catch(error=>{console.error(error);process.exitCode=1}).finally(()=>{
 if(!seeded) return;
 php(`$names=json_decode(getenv('QA_NAMES'),true);foreach($names as $u){$e=$u.'@example.invalid';$s=db()->prepare('DELETE FROM users WHERE username=? AND email=?');$s->bind_param('ss',$u,$e);$s->execute();$s=db()->prepare('DELETE FROM contact_submissions WHERE email=?');$s->bind_param('s',$e);$s->execute();}$tag=getenv('QA_TAG');$s=db()->prepare('DELETE FROM products WHERE name=?');$s->bind_param('s',$tag);$s->execute();foreach(json_decode(getenv('QA_UPLOADS'),true) as $p){if(preg_match('~^uploads/profile_[0-9]+_[a-f0-9]{32}\\.gif$~',$p)&&is_file(__DIR__.'/public/'.$p))unlink(__DIR__.'/public/'.$p);}`);
 console.log('Cleaned up this run’s temporary accounts, orders, reviews, contact and upload.');
});
