/**
 * Static JS/CSS minifier for production builds.
 * Minifies public/js/*.js and public/css/*.css in-place using terser + lightningcss.
 *
 * Usage:
 *   npm run minify:js     — only JS
 *   npm run minify:css    — only CSS
 *   npm run minify:static — both
 *
 * NOTE: Run AFTER npm run build. Git preserves readable originals.
 */

import { readFileSync, writeFileSync, readdirSync, statSync } from 'fs';
import { join, extname, basename } from 'path';
import { minify } from 'terser';

const PROJECT_ROOT = join(new URL('.', import.meta.url).pathname.replace(/^\/([A-Z]:\/)/i, (_, drive) => drive.toUpperCase()).replace(/%20/g, ' '), '..');
const JS_DIR = join(PROJECT_ROOT, 'public', 'js');

// Files to skip (already minified externally or handled by Vite)
const SKIP_FILES = new Set([]);

async function minifyJs() {
    const files = readdirSync(JS_DIR)
        .filter(f => extname(f) === '.js' && !SKIP_FILES.has(f))
        .map(f => join(JS_DIR, f));

    let totalBefore = 0;
    let totalAfter = 0;
    const results = [];

    for (const file of files) {
        const source = readFileSync(file, 'utf-8');
        const before = Buffer.byteLength(source, 'utf-8');

        try {
            const result = await minify(source, {
                compress: {
                    drop_console: false, // keep console.error for debugging
                    passes: 2,
                },
                mangle: true,
                format: {
                    comments: false,
                },
            });

            if (result.code) {
                writeFileSync(file, result.code, 'utf-8');
                const after = Buffer.byteLength(result.code, 'utf-8');
                totalBefore += before;
                totalAfter += after;
                const pct = (((before - after) / before) * 100).toFixed(1);
                results.push({ file: basename(file), before, after, pct });
                process.stdout.write(`  ✓ ${basename(file).padEnd(40)} ${kb(before)} → ${kb(after)} (-${pct}%)\n`);
            }
        } catch (err) {
            console.warn(`  ⚠ Skipped ${basename(file)}: ${err.message}`);
        }
    }

    console.log(`\n  JS total: ${kb(totalBefore)} → ${kb(totalAfter)} (-${(((totalBefore - totalAfter) / totalBefore) * 100).toFixed(1)}%)`);
    console.log(`  Files processed: ${results.length}`);
}

function kb(bytes) {
    return (bytes / 1024).toFixed(1) + ' KB';
}

const mode = process.argv[2] ?? 'js';

console.log('\n--- MentorDE Static Asset Minifier ---');

if (mode === 'js' || mode === 'all') {
    console.log('\nMinifying JS files...');
    await minifyJs();
}

console.log('\nDone.\n');
