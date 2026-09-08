const fs = require('fs');

const graphPath = process.argv[2];
const outputPath = process.argv[3];
const graph = JSON.parse(fs.readFileSync(graphPath, 'utf8'));
const issues = [];
const warnings = [];

if (!Array.isArray(graph.nodes)) issues.push('graph.nodes is missing or not an array');
if (!Array.isArray(graph.edges)) issues.push('graph.edges is missing or not an array');
if (!Array.isArray(graph.layers)) issues.push('graph.layers is missing or not an array');
if (!Array.isArray(graph.tour)) issues.push('graph.tour is missing or not an array');

const nodes = Array.isArray(graph.nodes) ? graph.nodes : [];
const nodeIds = new Set();
const seen = new Map();

nodes.forEach((node, index) => {
  if (!node.id) issues.push(`Node[${index}] missing id`);
  if (!node.type) issues.push(`Node[${index}] '${node.id}' missing type`);
  if (!node.name) issues.push(`Node[${index}] '${node.id}' missing name`);
  if (!node.summary) issues.push(`Node[${index}] '${node.id}' missing summary`);
  if (!node.tags || !node.tags.length) issues.push(`Node[${index}] '${node.id}' missing tags`);
  if (seen.has(node.id)) issues.push(`Duplicate node ID '${node.id}'`);
  else seen.set(node.id, index);
  nodeIds.add(node.id);
});

(graph.edges || []).forEach((edge, index) => {
  if (!nodeIds.has(edge.source)) issues.push(`Edge[${index}] source '${edge.source}' not found`);
  if (!nodeIds.has(edge.target)) issues.push(`Edge[${index}] target '${edge.target}' not found`);
});

const fileLevelTypes = new Set(['file', 'config', 'document', 'service', 'pipeline', 'table', 'schema', 'resource', 'endpoint']);
const fileNodes = nodes.filter((node) => fileLevelTypes.has(node.type)).map((node) => node.id);
const assigned = new Map();

(graph.layers || []).forEach((layer, index) => {
  for (const key of ['id', 'name', 'description', 'nodeIds']) {
    if (!(key in layer)) issues.push(`Layer[${index}] missing ${key}`);
  }
  if (!Array.isArray(layer.nodeIds)) issues.push(`Layer[${index}] nodeIds is not an array`);
  for (const id of layer.nodeIds || []) {
    if (!nodeIds.has(id)) issues.push(`Layer '${layer.id}' refs missing node '${id}'`);
    if (assigned.has(id)) issues.push(`Node '${id}' appears in multiple layers`);
    assigned.set(id, layer.id);
  }
});

for (const id of fileNodes) {
  if (!assigned.has(id)) issues.push(`File-level node '${id}' not in any layer`);
}

(graph.tour || []).forEach((step, index) => {
  for (const key of ['order', 'title', 'description', 'nodeIds']) {
    if (!(key in step)) issues.push(`Tour step[${index}] missing ${key}`);
  }
  for (const id of step.nodeIds || []) {
    if (!nodeIds.has(id)) issues.push(`Tour step[${index}] refs missing node '${id}'`);
  }
});

const connected = new Set();
for (const edge of graph.edges || []) {
  connected.add(edge.source);
  connected.add(edge.target);
}

for (const node of nodes) {
  if (!connected.has(node.id)) warnings.push(`Node '${node.id}' has no edges`);
}

const stats = {
  totalNodes: nodes.length,
  totalEdges: (graph.edges || []).length,
  totalLayers: (graph.layers || []).length,
  tourSteps: (graph.tour || []).length,
  nodeTypes: nodes.reduce((acc, node) => ((acc[node.type] = (acc[node.type] || 0) + 1), acc), {}),
  edgeTypes: (graph.edges || []).reduce((acc, edge) => ((acc[edge.type] = (acc[edge.type] || 0) + 1), acc), {}),
};

fs.writeFileSync(outputPath, JSON.stringify({ issues, warnings, stats }, null, 2));
console.log(JSON.stringify({ issueCount: issues.length, warningCount: warnings.length, stats }, null, 2));
