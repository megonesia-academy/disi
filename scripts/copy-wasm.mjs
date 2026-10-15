import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve } from 'node:path';

const source = resolve(
    'wasm/signage_core/target/wasm32-unknown-unknown/release/signage_core.wasm',
);
const destination = resolve('public/wasm/signage_core.wasm');

mkdirSync(dirname(destination), { recursive: true });
copyFileSync(source, destination);

console.log(`Copied ${source} -> ${destination}`);
