#!/usr/bin/env node
// Zero-dependency static preview server. Mirrors GitHub Pages path resolution
// closely enough to catch link and MIME mistakes before pushing.
const http = require('http');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const PORT = Number(process.env.PORT) || 8000;

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8',
  '.txt': 'text/plain; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.ico': 'image/x-icon',
  '.webm': 'video/webm',
  '.mp4': 'video/mp4',
  '.woff2': 'font/woff2',
};

function resolve(urlPath) {
  // Strip query/hash, decode, and refuse to escape ROOT.
  const clean = decodeURIComponent(urlPath.split('?')[0].split('#')[0]);
  const target = path.normalize(path.join(ROOT, clean));
  if (target !== ROOT && !target.startsWith(ROOT + path.sep)) return null;
  if (fs.existsSync(target) && fs.statSync(target).isDirectory()) {
    const index = path.join(target, 'index.html');
    return fs.existsSync(index) ? index : null;
  }
  if (fs.existsSync(target) && fs.statSync(target).isFile()) return target;
  // Allow /about -> /about.html, matching Pages' extensionless behaviour.
  const withExt = target + '.html';
  if (fs.existsSync(withExt)) return withExt;
  return null;
}

const server = http.createServer((req, res) => {
  const file = resolve(req.url);
  if (!file) {
    const notFound = path.join(ROOT, '404.html');
    const body = fs.existsSync(notFound) ? fs.readFileSync(notFound) : 'Not found';
    res.writeHead(404, { 'Content-Type': 'text/html; charset=utf-8' });
    return res.end(body);
  }
  const type = TYPES[path.extname(file).toLowerCase()] || 'application/octet-stream';
  res.writeHead(200, { 'Content-Type': type, 'Cache-Control': 'no-store' });
  fs.createReadStream(file).pipe(res);
});

server.listen(PORT, () => {
  console.log(`Spark of Chaos - preview on http://localhost:${PORT}`);
});
