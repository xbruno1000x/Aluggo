import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const repoRoot = path.resolve(__dirname, '..');
const envSrc = path.join(repoRoot, '.env.localhost');
const envDest = path.join(repoRoot, '.env');

if (!fs.existsSync(envSrc)) {
  console.error('.env.localhost not found, aborting');
  process.exit(1);
}

try {
  fs.copyFileSync(envSrc, envDest);
  console.log('Replaced .env with .env.localhost');
  process.exit(0);
} catch (error) {
  console.error('Failed to replace .env:', error.message);
  process.exit(1);
}
