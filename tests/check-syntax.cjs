// Syntax-check vanilla scripts, including scripts embedded in HTML.
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
let checked = 0;
const names = fs.readdirSync(root).concat(fs.readdirSync(path.join(root,'assets','js')).map(name => 'assets/js/' + name));
for (const name of names) {
  if (!/\.(js|html)$/.test(name)) continue;
  const source = fs.readFileSync(path.join(root, name), {encoding:'utf8', flag:'r'});
  if (name.endsWith('.js')) { new vm.Script(source, {filename:name}); checked++; }
  if (name.endsWith('.html')) for (const match of source.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/gi)) {
    if (match[1].trim()) { new vm.Script(match[1], {filename:name}); checked++; }
  }
}
console.log('PASS: ' + checked + ' JavaScript scripts parsed.');
