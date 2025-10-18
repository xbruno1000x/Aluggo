const fs = require('fs');
const path = require('path');

const repoRoot = path.resolve(__dirname, '..');
const envSrc = path.join(repoRoot, '.env.infinityfree');
const envDest = path.join(repoRoot, '.env');

if (!fs.existsSync(envSrc)) {
  console.error('.env.infinityfree not found, aborting');
  process.exit(1);
}

try {
  if (fs.existsSync(envDest)) {
    const bak = envDest + '.bak.' + Date.now();
    fs.copyFileSync(envDest, bak);
    console.log('Backed up existing .env to', bak);
  }
  fs.copyFileSync(envSrc, envDest);
  console.log('Replaced .env with .env.infinityfree');
  process.exit(0);
} catch (e) {
  console.error('Failed to replace .env:', e.message);
  process.exit(1);
}
