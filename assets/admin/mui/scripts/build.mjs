import { build } from 'esbuild';
import { existsSync, mkdirSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const root = join(__dirname, '..');
const outdir = join(root, 'build');
if (!existsSync(outdir)) mkdirSync(outdir, { recursive: true });

const isWatch = process.argv.includes('--watch');

await build({
  entryPoints: [join(root, 'src/index.jsx')],
  bundle: true,
  minify: !isWatch,
  sourcemap: isWatch ? 'inline' : false,
  target: 'es2018',
  format: 'iife',
  globalName: 'PWPL_MUI',
  outfile: join(outdir, 'app.js'),
  define: {
    'process.env.NODE_ENV': JSON.stringify(isWatch ? 'development' : 'production'),
  },
  loader: {
    '.js': 'jsx',
    '.jsx': 'jsx',
  },
  watch: isWatch,
});

console.log(`[PWPL] MUI build ${isWatch ? 'watching' : 'completed'} → assets/admin/mui/build/app.js`);

