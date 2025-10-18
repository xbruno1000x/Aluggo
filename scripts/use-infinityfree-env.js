import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const repoRoot = path.resolve(__dirname, '..');
const envSrc = path.join(repoRoot, '.env.infinityfree');
const envDest = path.join(repoRoot, '.env');

if (!fs.existsSync(envSrc)) {
  console.error('.env.infinityfree not found, aborting');
  process.exit(1);
}

try {
  if (fs.existsSync(envDest)) {
    const backupName = `${envDest}.bak.${Date.now()}`;
    fs.copyFileSync(envDest, backupName);
    console.log('Backed up existing .env to', backupName);
  }

  fs.copyFileSync(envSrc, envDest);
  console.log('Replaced .env with .env.infinityfree');
  process.exit(0);
} catch (error) {
  console.error('Failed to replace .env:', error.message);
  process.exit(1);
}
