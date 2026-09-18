// Verify local script/style references after asset organisation. No database writes.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..', 'public');
let checked = 0;
let pages = 0;
for (const file of fs.readdirSync(root).filter(name => /\.(html|php)$/.test(name))) {
  const text = fs.readFileSync(path.join(root,file),'utf8');
  if (/<html\b/i.test(text)) {
    const icons = [...text.matchAll(/<link\b[^>]*rel="icon"[^>]*>/g)];
    assert.equal(icons.length, 1, file + ': expected one browser icon');
    assert.ok(icons[0][0].includes('href="assets/favicon.svg?v=2"'), file + ': inconsistent browser icon');
    assert.ok(fs.existsSync(path.join(root, 'assets/favicon.svg')), 'Missing browser icon asset');
    pages++;
  }
  for (const match of text.matchAll(/(?:src|href)="([^"<>]+\.(?:css|js)(?:\?[^"<>]*)?)"/g)) {
    if (/^(https?:)?\/\//.test(match[1])) continue;
    const target = match[1].split('?')[0];
    assert.ok(fs.existsSync(path.join(root,target)), file + ': missing ' + target);
    checked++;
  }
}
console.log('PASS: '+checked+' local stylesheet/script references resolve.');

console.log('PASS: '+pages+' HTML documents share the café browser icon.');
