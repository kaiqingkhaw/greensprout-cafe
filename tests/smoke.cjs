const assert = require('node:assert/strict');
const base = process.env.TEST_BASE_URL || 'http://localhost/GreenSproutCafe-Portfolio/public/';
if (!process.env.TEST_USERNAME || !process.env.TEST_PASSWORD || process.env.ALLOW_TEST_WRITES !== '1') {
 console.error('Use a disposable local database and set TEST_USERNAME, TEST_PASSWORD and ALLOW_TEST_WRITES=1.');
 process.exit(1);
}
let cookie='', token='';
async function req(path, options={}) {
 const r=await fetch(base+path,{redirect:'manual',...options,headers:{...options.headers,Cookie:cookie}});
 if(r.headers.get('set-cookie')) cookie=r.headers.get('set-cookie').split(';')[0];
 const text=await r.text(); let body; try {body=JSON.parse(text);} catch {body=text;}
 return {status:r.status,body,location:r.headers.get('location')};
}
async function post(path,data,json=true) {
 token=(await req('csrf.php')).body.csrfToken;
 return req(path,{method:'POST',headers:{'X-CSRF-Token':token,...(json?{'Content-Type':'application/json'}:{})},body:json?JSON.stringify(data):new URLSearchParams(data)});
}
(async()=>{
 assert.equal((await req('create_order.php',{method:'POST',body:'{}'})).status,403); console.log('PASS missing CSRF rejected');
 assert.equal((await post('login.php',{username:process.env.TEST_USERNAME,password:process.env.TEST_PASSWORD},false)).status,200); console.log('PASS login with CSRF');
 const products=(await req('get_products.php')).body;
 const p=products[0];
 const order={items:[{id:p.id,quantity:1,price:0.01}],delivery_info:{fullName:'QA Checkout',email:'qa@example.com',phone:'0123456789',address:'123 Test Road, Kuala Lumpur',specialInstructions:'QA test order'},request_key:'audit-'+Date.now()+'-checkout',discount_code:''};
 assert.equal((await post('create_order.php',{...order,delivery_info:{}})).status,422); console.log('PASS invalid delivery');
 const saved=await post('create_order.php',order); assert.equal(saved.status,201,JSON.stringify(saved));
 const repeated=await post('create_order.php',order); assert.equal(repeated.body.order_number,saved.body.order_number);console.log('PASS idempotent checkout '+saved.body.order_number);
 const price=Math.round(p.price*(p.discounted?1-p.discountPercent/100:1)*100)/100;
 assert.equal(saved.body.total,Math.round((price+Math.round(price*5)/100+5)*100)/100); console.log('PASS server-authoritative price');
 const details=(await req('get_order_details.php?order_number='+saved.body.order_number)).body;
 assert.equal(details.deliveryInfo.fullName,'QA Checkout');assert.equal(JSON.parse(details.items).length,1);assert.equal(details.breakdown.total,saved.body.total); console.log('PASS saved receipt and line items');
 assert.equal((await req('admin_home.php')).location,'login.html'); console.log('PASS customer denied admin');
 assert.equal((await post('submit_review.php',{rating:9,review:'Test'})).status,422);console.log('PASS review validation');
 assert.equal((await post('contact.php',{name:'QA',email:'qa@example.com',subject:'Test',message:'Automated contact persistence test.'},false)).status,201);console.log('PASS contact submission');
 await post('logout.php',{},false); assert.equal((await req('check_session.php')).body.loggedIn,false);console.log('PASS logout');
 assert.equal((await req('get_order_details.php?order_number='+saved.body.order_number)).status,401);console.log('PASS private order denied after logout');
})().catch(e=>{console.error(e);process.exitCode=1;});
