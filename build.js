const esbuild = require('esbuild');
const fs = require('fs');
const path = require('path');

const isWatch = process.argv.includes('--watch');

const jsFiles = [
  'assets/cmb-script.js',
  'assets/cmb-admin.js',
  'assets/cmb-gutenberg.js',
];

const cssFiles = [
  'assets/cmb-style.css',
  'assets/cmb-admin.css',
];

async function build() {
  // Minify JS files
  for (const file of jsFiles) {
    const outfile = file.replace('.js', '.min.js');
    await esbuild.build({
      entryPoints: [file],
      outfile,
      minify: true,
      sourcemap: true,
      target: ['es2015'],
      bundle: false,
    });
    console.log(`Built ${outfile}`);
  }

  // Minify CSS files
  for (const file of cssFiles) {
    const outfile = file.replace('.css', '.min.css');
    await esbuild.build({
      entryPoints: [file],
      outfile,
      minify: true,
      sourcemap: true,
      bundle: false,
      loader: { '.css': 'css' },
    });
    console.log(`Built ${outfile}`);
  }

  console.log('Build complete.');
}

build().catch((err) => {
  console.error(err);
  process.exit(1);
});
