// Read-only checks for the Apache/XAMPP project layout.
'use strict';
const assert = require('node:assert/strict');
const base = process.env.TEST_PROJECT_URL || 'http://localhost/GreenSproutCafe-Portfolio/';
if (!['localhost', '127.0.0.1'].includes(new URL(base).hostname)) throw Error('Use a local test installation.');
(async () => {
  let checks = 0;
  async function check(route, status, redirect) {
    const response = await fetch(new URL(route, base), {redirect:'manual'});
    assert.equal(response.status, status, route || 'Project entry point');
    if (redirect) assert.ok(new URL(response.headers.get('location'), response.url).pathname.endsWith(redirect), route + ' redirect');
    await response.body?.cancel();
    checks++;
  }
  await check('', 302, '/public/');
  await check('MainMenu.html', 307, '/public/MainMenu.html');
  await check('admin_profile.php', 307, '/public/admin_profile.php');
  await check('public/MainMenu.html', 200);
  await check('public/login.html', 200);
  for (const route of ['app/config.php','database/schema.sql','scripts/create_admin.php','tests/full-regression.cjs','docs/ARCHITECTURE.md','.github/workflows/checks.yml']) await check(route, 403);
  for (const name of ['cafe-atmosphere.mp4','cafe-hero.mp4','menu-feature.mp4']) await check('public/assets/videos/' + name, 200);
  await check('public/assets/favicon.svg?v=2', 200);
  console.log(`PASS: ${checks} entry-point, bookmark, private-directory and media HTTP checks.`);
})().catch(error => {console.error(error);process.exitCode=1;});
