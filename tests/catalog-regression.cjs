'use strict';
const assert = require('node:assert/strict');
const catalog = require('../assets/js/catalog-model.js');
const dish = {name:'Avocado Toast',description:'Sourdough with poached eggs',category:'Breakfast'};
assert.equal(catalog.matches(dish, 'toast eggs'), true);
assert.equal(catalog.matches(dish, ' BREAKFAST   avocado '), true);
assert.equal(catalog.matches(dish, 'avocado soup'), false);
assert.equal(catalog.matches(dish, ''), true);
assert.deepEqual(catalog.restoreCart(null), []);
assert.deepEqual(catalog.restoreCart({cart:[null, {}, {id:1,name:'Toast',price:'12',quantity:2}]}), []);
const item = {id:1,name:'Toast',price:12.5,quantity:2,image:'photo.jpg',specialRequest:'No salt'};
assert.deepEqual(catalog.restoreCart({cart:[item]}), [item]);
assert.equal(catalog.restoreCart({cart:[item,{...item,quantity:30}]} )[0].quantity,20);
for(const patch of [{price:NaN},{price:Infinity},{price:-1},{quantity:1.5},{quantity:0},{id:-1}]) {
  assert.deepEqual(catalog.restoreCart({cart:[{...item,...patch}]}), []);
}
console.log('PASS multi-word search, whitespace, empty query, malformed cart recovery, valid cart preservation and quantity bounds.');
