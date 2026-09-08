const fs = require('fs');
const path = require('path');

const root = process.cwd();
const inter = path.join(root, '.understand-anything', 'intermediate');

const scan = JSON.parse(fs.readFileSync(path.join(inter, 'scan-result.json'), 'utf8'));
const importMap = scan.importMap || {};
const batchFiles = fs.readdirSync(inter)
  .filter((name) => /^batch-\d+-structure\.json$/.test(name))
  .sort();

const structures = [];
for (const file of batchFiles) {
  const batch = JSON.parse(fs.readFileSync(path.join(inter, file), 'utf8'));
  structures.push(...(batch.results || []));
}

const structureByPath = new Map(structures.map((item) => [item.path, item]));
const nodes = [];
const edges = [];
const nodeIds = new Set();
const fileNodeIds = new Map();
const functionNameIndex = new Map();

function addNode(node) {
  if (!node.id || nodeIds.has(node.id)) return;
  node.tags = Array.from(new Set((node.tags || []).filter(Boolean))).slice(0, 12);
  if (!node.tags.length) node.tags = ['untagged'];
  if (!node.summary) node.summary = 'Chua co tom tat.';
  nodes.push(node);
  nodeIds.add(node.id);
}

function addEdge(edge) {
  if (!edge.source || !edge.target || edge.source === edge.target) return;
  edges.push({
    weight: edge.weight ?? defaultWeight(edge.type),
    ...edge,
  });
}

function defaultWeight(type) {
  return {
    contains: 1.0,
    inherits: 0.9,
    implements: 0.9,
    calls: 0.8,
    exports: 0.8,
    defines_schema: 0.8,
    imports: 0.7,
    migrates: 0.7,
    depends_on: 0.6,
    configures: 0.6,
    triggers: 0.6,
    tested_by: 0.5,
    documents: 0.5,
    routes: 0.5,
  }[type] || 0.5;
}

function toPosix(value) {
  return value.replace(/\\/g, '/');
}

function nodeTypeFor(file) {
  const p = file.path;
  if (file.fileCategory === 'docs' || /\.(md|rst|txt)$/i.test(p)) return 'document';
  if (file.fileCategory === 'config' || /(^|\/)(composer|package|vite\.config|phpunit|pint|boost)\./.test(p) || p.startsWith('config/')) return 'config';
  if (file.fileCategory === 'infra' || /Dockerfile|docker-compose|nginx|apache/i.test(p)) return 'service';
  if (/\.(sql)$/i.test(p)) return 'schema';
  return 'file';
}

function fileNodeId(file) {
  if (fileNodeIds.has(file.path)) return fileNodeIds.get(file.path);
  const type = nodeTypeFor(file);
  const id = `${type}:${file.path}`;
  fileNodeIds.set(file.path, id);
  return id;
}

function tagsFor(file, extra = []) {
  const tags = [file.language, file.fileCategory, ...extra];
  const segments = file.path.split('/');
  tags.push(segments[0]);
  if (segments[1]) tags.push(segments[1]);
  if (file.path.includes('haidangtravel')) tags.push('haidangtravel');
  if (file.path.includes('Travel') || file.path.includes('Tour')) tags.push('travel');
  if (file.path.includes('Admin')) tags.push('admin');
  if (file.path.includes('Livewire')) tags.push('livewire');
  if (file.path.includes('themes/haidangtravel')) tags.push('theme');
  return tags;
}

function summarizeFile(file, detail) {
  const p = file.path;
  const classes = detail?.classes?.map((item) => item.name).filter(Boolean) || [];
  const functions = detail?.functions?.map((item) => item.name).filter(Boolean) || [];
  let base = `File ${p} trong Haidang Travel CMS.`;
  if (p === 'README.md') return 'Tai lieu tong quan ve Haidang Travel CMS, public routes, admin routes, theme va cach khoi chay local.';
  if (p === 'AGENTS.md') return 'Quy tac van hanh repo, domain travel hien tai va cac boundary khong dua lai logic construction/SEO AI cu.';
  if (p === 'docs/AGENTS.md') return 'Huong dan chuyen sau cho agent khi lam viec voi travel CMS, seeder, frontend, backend va validation.';
  if (p.startsWith('routes/')) base = 'Dinh nghia route Laravel cho public frontsite, admin CMS hoac endpoint lien quan.';
  else if (p.startsWith('app/Http/Controllers')) base = 'Controller Laravel dieu phoi request va uy quyen business logic cho layer phu hop.';
  else if (p.startsWith('app/Livewire') || p.includes('/Livewire/')) base = 'Component Livewire dieu khien man hinh va tuong tac trong admin CMS.';
  else if (p.startsWith('app/Models') || p.includes('/Models/')) base = 'Model Eloquent dai dien entity/domain state trong travel CMS.';
  else if (p.startsWith('app/Services') || p.includes('/Services/')) base = 'Service chua business logic dung chung cho travel CMS.';
  else if (p.startsWith('src/Domains/Cms')) base = 'Module domain CMS chua model, action, service hoac concern cua Haidang Travel.';
  else if (p.startsWith('resources/views/themes/haidangtravel')) base = 'Blade view thuoc theme frontsite active haidangtravel.';
  else if (p.startsWith('resources/views/livewire')) base = 'Blade view gan voi component Livewire trong admin CMS.';
  else if (p.startsWith('resources/js') || p.startsWith('resources/css')) base = 'Tai san frontend duoc build qua Vite.';
  else if (p.startsWith('database/migrations')) base = 'Migration dinh nghia schema du lieu cho Laravel app.';
  else if (p.startsWith('database/seeders')) base = 'Seeder/bootstrap du lieu cho Haidang Travel CMS.';
  else if (p.startsWith('tests/')) base = 'Test bao ve hanh vi cua travel CMS.';
  else if (p.startsWith('config/')) base = 'Cau hinh Laravel hoac package cho runtime.';
  else if (p.startsWith('docs/')) base = 'Tai lieu noi bo mo ta architecture, frontsite, backend, design system hoac yeu cau ky thuat.';

  const parts = [base];
  if (classes.length) parts.push(`Class: ${classes.slice(0, 4).join(', ')}.`);
  if (functions.length) parts.push(`Functions/methods: ${functions.slice(0, 4).join(', ')}.`);
  return parts.join(' ');
}

for (const file of scan.files) {
  const detail = structureByPath.get(file.path);
  const type = nodeTypeFor(file);
  const id = fileNodeId(file);
  addNode({
    id,
    type,
    name: file.path.split('/').pop(),
    filePath: file.path,
    summary: summarizeFile(file, detail),
    tags: tagsFor(file),
    metadata: {
      language: file.language,
      fileCategory: file.fileCategory,
      sizeLines: file.sizeLines,
      totalLines: detail?.totalLines,
      nonEmptyLines: detail?.nonEmptyLines,
    },
  });
}

for (const detail of structures) {
  const file = scan.files.find((item) => item.path === detail.path);
  if (!file) continue;
  const sourceFileId = fileNodeId(file);

  for (const cls of detail.classes || []) {
    const id = `class:${detail.path}:${cls.name}`;
    addNode({
      id,
      type: 'class',
      name: cls.name,
      filePath: detail.path,
      summary: `Class ${cls.name} trong ${detail.path}, gom ${cls.methods?.length || 0} method va ${cls.properties?.length || 0} property.`,
      tags: tagsFor(file, ['class']),
      metadata: {
        startLine: cls.startLine,
        endLine: cls.endLine,
        methods: cls.methods || [],
        properties: cls.properties || [],
      },
    });
    addEdge({ source: sourceFileId, target: id, type: 'contains' });
  }

  for (const fn of detail.functions || []) {
    const id = `function:${detail.path}:${fn.name}`;
    addNode({
      id,
      type: 'function',
      name: fn.name,
      filePath: detail.path,
      summary: `Function/method ${fn.name} trong ${detail.path}.`,
      tags: tagsFor(file, ['function']),
      metadata: {
        startLine: fn.startLine,
        endLine: fn.endLine,
        params: fn.params || [],
      },
    });

    const ownerClass = (detail.classes || []).find((cls) => (cls.methods || []).includes(fn.name));
    if (ownerClass) addEdge({ source: `class:${detail.path}:${ownerClass.name}`, target: id, type: 'contains' });
    else addEdge({ source: sourceFileId, target: id, type: 'contains' });

    if (!functionNameIndex.has(fn.name)) functionNameIndex.set(fn.name, []);
    functionNameIndex.get(fn.name).push(id);
  }
}

for (const [sourcePath, targetPaths] of Object.entries(importMap)) {
  const sourceFile = scan.files.find((item) => item.path === sourcePath);
  if (!sourceFile) continue;
  const source = fileNodeId(sourceFile);
  for (const targetPath of targetPaths || []) {
    const normalized = toPosix(targetPath);
    const targetFile = scan.files.find((item) => item.path === normalized);
    if (!targetFile) continue;
    addEdge({ source, target: fileNodeId(targetFile), type: 'imports' });
  }
}

for (const detail of structures) {
  for (const call of detail.callGraph || []) {
    const caller = `function:${detail.path}:${call.caller}`;
    if (!nodeIds.has(caller)) continue;
    const calleeName = String(call.callee || '').split(/->|::|\(/).pop().trim();
    const candidates = functionNameIndex.get(calleeName);
    if (candidates?.length === 1) addEdge({ source: caller, target: candidates[0], type: 'calls', weight: 0.65 });
  }
}

function readTextIfExists(rel) {
  const abs = path.join(root, rel);
  if (!fs.existsSync(abs)) return '';
  return fs.readFileSync(abs, 'utf8');
}

for (const routePath of ['routes/web.php', 'routes/auth.php', 'routes/console.php']) {
  const text = readTextIfExists(routePath);
  if (!text) continue;
  const file = scan.files.find((item) => item.path === routePath);
  if (!file) continue;
  const routeFileId = fileNodeId(file);
  const routeRegex = /Route::(get|post|put|patch|delete|middleware|prefix|redirect|view)\(([^;\n]+)/g;
  let match;
  let index = 0;
  while ((match = routeRegex.exec(text)) !== null) {
    const method = match[1].toUpperCase();
    const uriMatch = match[2].match(/['"]([^'"]+)['"]/);
    const uri = uriMatch ? uriMatch[1] : `${method.toLowerCase()}-${index + 1}`;
    const safe = uri.replace(/[^a-zA-Z0-9{}_-]+/g, '-').replace(/^-|-$/g, '') || `${method.toLowerCase()}-${index + 1}`;
    const id = `endpoint:${routePath}:${method}:${safe}`;
    addNode({
      id,
      type: 'endpoint',
      name: `${method} ${uri}`,
      filePath: routePath,
      summary: `Endpoint Laravel ${method} ${uri} duoc khai bao trong ${routePath}.`,
      tags: ['route', 'laravel', method.toLowerCase()],
      metadata: { method, uri },
    });
    addEdge({ source: routeFileId, target: id, type: 'routes' });
    index += 1;
  }
}

for (const file of scan.files.filter((item) => item.path.startsWith('database/migrations/'))) {
  const text = readTextIfExists(file.path);
  const matches = [...text.matchAll(/Schema::(create|table)\(['"]([^'"]+)['"]/g)];
  const source = fileNodeId(file);
  for (const [, action, table] of matches) {
    const id = `table:${file.path}:${table}`;
    addNode({
      id,
      type: 'table',
      name: table,
      filePath: file.path,
      summary: `Bang database ${table} duoc migration ${action} trong ${file.path}.`,
      tags: ['database', 'migration', table],
      metadata: { action, table },
    });
    addEdge({ source, target: id, type: 'migrates' });
  }
}

const edgeKeys = new Set();
const dedupedEdges = [];
for (const edge of edges) {
  if (!nodeIds.has(edge.source) || !nodeIds.has(edge.target)) continue;
  const key = `${edge.source}|${edge.target}|${edge.type}`;
  if (edgeKeys.has(key)) continue;
  edgeKeys.add(key);
  dedupedEdges.push(edge);
}

function layerId(name) {
  return `layer:${name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}`;
}

const layerDefs = [
  ['Project Guidance & Docs', 'Tai lieu repo, huong dan agent va ghi chu architecture.', (n) => n.type === 'document' || n.filePath === 'README.md' || n.filePath === 'AGENTS.md' || n.filePath?.startsWith('docs/')],
  ['Laravel Bootstrap & Config', 'Bootstrap Laravel, package manifests va cau hinh runtime/build.', (n) => n.type === 'config' || ['artisan', 'laravel'].includes(n.filePath) || n.filePath?.startsWith('bootstrap/')],
  ['HTTP Routes & Middleware', 'Route Laravel, endpoint va middleware HTTP.', (n) => n.type === 'endpoint' || n.filePath?.startsWith('routes/') || n.filePath?.includes('/Middleware/')],
  ['Frontsite Travel Runtime', 'Controller, view model, theme Blade va CTA public cua frontsite travel.', (n) => n.filePath?.startsWith('resources/views/themes/haidangtravel') || /Frontsite|Consultation|TravelInquiry/.test(n.filePath || '')],
  ['Admin CMS & Livewire UI', 'Man hinh admin CMS, Livewire component, Flux UI va view quan tri.', (n) => n.filePath?.startsWith('app/Livewire') || n.filePath?.startsWith('resources/views/livewire') || n.filePath?.includes('/Admin/') || n.filePath?.startsWith('resources/views/admin')],
  ['Domain Models & Services', 'Model, service, action, policy va domain layer cho travel CMS.', (n) => n.filePath?.startsWith('app/Models') || n.filePath?.startsWith('app/Services') || n.filePath?.startsWith('app/Actions') || n.filePath?.startsWith('app/Policies') || n.filePath?.startsWith('src/Domains')],
  ['Data, Seeders & Migrations', 'Migration, factory, seeder, snapshot schema va bootstrap data.', (n) => n.type === 'table' || n.type === 'schema' || n.filePath?.startsWith('database/') || n.filePath?.startsWith('blueprints/')],
  ['Assets & Frontend Build', 'CSS, JavaScript, public manifest va asset build surface.', (n) => n.filePath?.startsWith('resources/css') || n.filePath?.startsWith('resources/js') || n.filePath?.startsWith('public/')],
  ['Tests', 'Feature/unit tests bao ve behavior cua CMS.', (n) => n.filePath?.startsWith('tests/')],
  ['Miscellaneous Application Files', 'Cac file app khac khong thuoc layer chuyen biet.', () => true],
];

const fileLevelTypes = new Set(['file', 'config', 'document', 'service', 'pipeline', 'table', 'schema', 'resource', 'endpoint']);
const fileLevelNodes = nodes.filter((node) => fileLevelTypes.has(node.type));
const assigned = new Set();
const layers = layerDefs.map(([name, description, predicate]) => {
  const nodeIdsForLayer = [];
  for (const node of fileLevelNodes) {
    if (assigned.has(node.id)) continue;
    if (predicate(node)) {
      nodeIdsForLayer.push(node.id);
      assigned.add(node.id);
    }
  }
  return {
    id: layerId(name),
    name,
    description,
    nodeIds: nodeIdsForLayer,
  };
}).filter((layer) => layer.nodeIds.length);

function existing(ids) {
  return ids.filter((id) => nodeIds.has(id));
}

const tour = [
  {
    order: 1,
    title: 'Project Overview',
    description: 'Bat dau tu README va AGENTS de nam pham vi Haidang Travel CMS, domain travel va cac boundary khong duoc dua logic cu quay lai.',
    nodeIds: existing(['document:README.md', 'document:AGENTS.md', 'document:docs/AGENTS.md']),
  },
  {
    order: 2,
    title: 'Laravel Entry Points',
    description: 'Xem bootstrap, config va route de hieu cach Laravel app khoi dong va expose public/admin surface.',
    nodeIds: existing(['file:artisan', 'file:bootstrap/app.php', 'config:composer.json', 'file:routes/web.php', 'file:routes/auth.php']),
  },
  {
    order: 3,
    title: 'Public Travel Frontsite',
    description: 'Theo route public vao frontsite controller va theme haidangtravel, noi CTA lead di qua TravelInquiry.',
    nodeIds: existing([
      'file:app/Http/Controllers/FrontsiteConsultationController.php',
      'file:app/Http/Controllers/FrontsiteSeoPageController.php',
      'file:resources/views/themes/haidangtravel/layouts/app.blade.php',
      'file:resources/views/themes/haidangtravel/home.blade.php',
    ]),
  },
  {
    order: 4,
    title: 'Domain Models And Data',
    description: 'Doc cac model, service va migration/seeders de hieu Tour, Service, BlogPost, LandingPage, TravelInquiry va bootstrap data.',
    nodeIds: existing([
      'file:app/Models/Tour.php',
      'file:app/Models/Service.php',
      'file:app/Models/BlogPost.php',
      'file:app/Models/LandingPage.php',
      'file:app/Models/TravelInquiry.php',
      'file:database/seeders/HaidangTravelBootstrapSeeder.php',
    ]),
  },
  {
    order: 5,
    title: 'Admin CMS Workflows',
    description: 'Di qua Livewire/admin surface de hieu cach quan tri tour, service, blog, menu, media va theme settings.',
    nodeIds: existing([
      'file:app/Livewire/Admin/Tours/TourForm.php',
      'file:app/Livewire/Admin/Services/ServiceForm.php',
      'file:app/Livewire/Admin/Blogs/BlogPostForm.php',
      'file:resources/views/livewire/admin/tours/index.blade.php',
    ]),
  },
  {
    order: 6,
    title: 'Theme And Assets',
    description: 'Kiem tra Blade theme, CSS/JS va Vite surface de hieu frontend public hien tai.',
    nodeIds: existing([
      'file:resources/css/app.css',
      'file:resources/js/app.js',
      'config:vite.config.js',
      'file:resources/views/themes/haidangtravel/components/navigation.blade.php',
    ]),
  },
  {
    order: 7,
    title: 'Validation And Tests',
    description: 'Ket thuc o test suite va docs validation de nam nhung luong nen chay khi sua lon.',
    nodeIds: existing([
      'file:tests/Feature/ExampleTest.php',
      'config:phpunit.xml',
      'document:docs/TECHNICAL_REQUIREMENTS.md',
    ]),
  },
].filter((step) => step.nodeIds.length);

const graph = {
  version: '1.0.0',
  project: {
    name: scan.projectName || 'Haidang Travel CMS',
    languages: scan.languages || [],
    frameworks: scan.frameworks || [],
    description: scan.projectDescription || 'Laravel + Livewire CMS cho haidangtravel.com.',
    analyzedAt: new Date().toISOString(),
    gitCommitHash: 'unavailable-no-git-repository',
  },
  nodes,
  edges: dedupedEdges,
  layers,
  tour,
};

fs.writeFileSync(path.join(inter, 'assembled-graph.json'), JSON.stringify(graph, null, 2));

const stats = {
  nodes: nodes.length,
  edges: dedupedEdges.length,
  layers: layers.length,
  tourSteps: tour.length,
  nodeTypes: nodes.reduce((acc, node) => ((acc[node.type] = (acc[node.type] || 0) + 1), acc), {}),
  edgeTypes: dedupedEdges.reduce((acc, edge) => ((acc[edge.type] = (acc[edge.type] || 0) + 1), acc), {}),
};

console.log(JSON.stringify(stats, null, 2));
