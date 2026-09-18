'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname,'../public/assets/js/home-menu.js'),'utf8');
function element(tag) {
    return {tag,children:[],attributes:{},events:{},append(...nodes){this.children.push(...nodes);},replaceChildren(){this.children=[];},setAttribute(k,v){this.attributes[k]=v;},addEventListener(k,v){this.events[k]=v;}};
}
async function render(products, ok=true) {
    const container=element('div');
    const context={document:{getElementById:()=>container,createElement:element},GS:{fetch:async()=>({ok,json:async()=>products})},URL,location:{href:'http://localhost/GreenSproutCafe-Portfolio/MainMenu.html'}};
    vm.runInNewContext(source,context);
    await new Promise(resolve=>setImmediate(resolve));
    return container;
}
(async()=>{
    const product={id:42,name:'Database name <safe>',description:'Database description',image:'https://example.invalid/dish.jpg',price:19.9,discounted:true,discountPercent:10,featured:true};
    const result=await render([product]);
    const card=result.children[0], content=card.children[1], footer=content.children[2];
    assert.equal(card.children[0].children[0].src,product.image);
    card.children[0].children[0].events.error();
    assert.equal(card.children[0].children[0].src,'assets/images/dish-placeholder.svg');
    const withoutPhoto=await render([{...product,image:''}]);
    assert.equal(withoutPhoto.children[0].children[0].children[0].src,'assets/images/dish-placeholder.svg');
    assert.equal(content.children[0].children[0].textContent,product.name);
    assert.equal(content.children[1].textContent,product.description);
    assert.equal(footer.children[0].textContent,'RM17.91');
    assert.equal(footer.children[1].href,'menu.html?product=42');
    const updated=await render([{...product,name:'Changed by admin',price:30}]);
    assert.equal(updated.children[0].children[1].children[0].children[0].textContent,'Changed by admin');
    assert.equal(updated.children[0].children[1].children[2].children[0].textContent,'RM27.00');
    assert.match((await render([])).textContent,/being refreshed/);
    const failed=await render([],false);
    assert.equal(failed.children[0].textContent,'Try again');
    assert.equal(typeof failed.children[0].events.click,'function');
    console.log('PASS homepage live names, images, descriptions, discounted prices, detail links, admin changes, empty state and retry.');
})().catch(error=>{console.error(error);process.exitCode=1;});
