import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'fs';
import path from 'path';

// monta dinamicamente o array de entradas a partir de resources/ts/*.ts
const tsDir = path.resolve(__dirname, 'resources', 'ts');
const tsFiles = [];
if (fs.existsSync(tsDir)) {
    for (const file of fs.readdirSync(tsDir)) {
        if (file.endsWith('.ts') || file.endsWith('.js')) {
            tsFiles.push('resources/ts/' + file);
        }
    }
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/ts/app.ts', ...tsFiles],
            refresh: true,
        }),
    ],
});
