import concurrent.futures
import hashlib
import json
import pathlib
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from html.parser import HTMLParser

ROOT = pathlib.Path(__file__).resolve().parents[1]
OUT = ROOT / 'outputs' / 'seo-primary-fixes-20260918'
OUT.mkdir(parents=True, exist_ok=True)
BASE = 'https://haidangtravel.com'


class Page(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.counts = dict.fromkeys(['head', 'body', 'title', 'h1'], 0)
        self.in_head = False
        self.canonicals = []
        self.robots = []
        self.descriptions = 0
        self.outside_head = []
        self.links = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag in self.counts:
            self.counts[tag] += 1
        if tag == 'head':
            self.in_head = True
        if tag == 'body':
            self.in_head = False
        if tag == 'title' and not self.in_head:
            self.outside_head.append('title')
        if tag == 'link' and 'canonical' in attrs.get('rel', '').lower().split():
            self.canonicals.append(attrs.get('href', ''))
        if tag == 'meta':
            name = attrs.get('name', '').lower()
            if name == 'robots':
                self.robots.append(attrs.get('content', ''))
            if name == 'description':
                self.descriptions += 1
                if not self.in_head:
                    self.outside_head.append('description')
        if tag == 'a' and attrs.get('href'):
            self.links.append(attrs['href'])

    def handle_endtag(self, tag):
        if tag == 'head':
            self.in_head = False


def fetch(url):
    started = time.monotonic()
    record = {'url': url}
    try:
        request = urllib.request.Request(url, headers={'User-Agent': 'HaidangTravel-SEO-Check/1.0', 'Accept': 'text/html,application/xml;q=0.9,*/*;q=0.8'})
        try:
            response = urllib.request.urlopen(request, timeout=25)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            record.update(status=response.status, final_url=response.geturl(), content_type=response.headers.get('Content-Type', ''), x_robots=response.headers.get('X-Robots-Tag', ''))
            body = response.read(4_000_000).decode('utf-8', errors='replace')
        if 'html' in record['content_type']:
            page = Page()
            page.feed(body)
            record.update(counts=page.counts, canonicals=page.canonicals, robots=page.robots, descriptions=page.descriptions, outside_head=page.outside_head)
            record['links'] = list(dict.fromkeys(urllib.parse.urljoin(record['final_url'], value).split('#')[0] for value in page.links if urllib.parse.urljoin(record['final_url'], value).startswith(BASE)))
            if any(page.counts[tag] != 1 for tag in ['head', 'body', 'title', 'h1']) or page.outside_head or len(page.canonicals) != 1 or record['status'] >= 400:
                path = OUT / (hashlib.sha256(url.encode()).hexdigest()[:12] + '.html')
                path.write_text(body, encoding='utf-8')
                record['html_file'] = str(path)
        record['seconds'] = round(time.monotonic() - started, 2)
    except Exception as error:
        record['error'] = str(error)
    return record


urls = [node.text for node in ET.parse(ROOT / 'tmp' / 'seo-live-sitemap.xml').iter() if node.tag.endswith('}loc')]
groups = {}
for url in urls:
    path = urllib.parse.urlsplit(url).path
    key = path.split('/')[1] if '/' in path else ''
    if key.startswith('tour-') and key not in ['tour-trong-nuoc', 'tour-nuoc-ngoai', 'tour-doan']:
        key = 'destination-hubs'
    groups.setdefault(key, []).append(url)
selection = urls[:18]
for key, count in [('chuong-trinh', 24), ('destination-hubs', 20), ('danh-muc-tour', 5), ('vung-mien', 5), ('dich-vu', 8)]:
    selection += groups.get(key, [])[:count]
for key, values in groups.items():
    if key not in ['chuong-trinh', 'destination-hubs', 'danh-muc-tour', 'vung-mien', 'dich-vu', '', 'blog', 'tour-trong-nuoc', 'tour-nuoc-ngoai', 'tour-doan'] and len(values) > 10:
        selection += values[:6]
selection += [BASE + '/tour-du-lich-ta-dung', BASE + '/sitemap-index.xml']
selection = list(dict.fromkeys(selection))[:110]
report_name = 'live'
if '--extra-links' in sys.argv:
    report_name = 'extra-live'
    previous = json.loads((OUT / 'live-pages.json').read_text(encoding='utf-8'))
    selection = list(dict.fromkeys(link for record in previous for link in record.get('links', []) if link not in urls and '/cdn-cgi/' not in link and not link.endswith('/login')))
    selection += [record['url'] for record in previous if record.get('status', 0) >= 500 or record.get('error')]
    selection += [url for url in urls if '/bai-viet/' in url][-30::5]
    selection = list(dict.fromkeys(selection))[:80]
print(json.dumps({'sitemap_urls': len(urls), 'groups': {key: len(value) for key, value in groups.items()}, 'sample_urls': len(selection)}, ensure_ascii=False), flush=True)
results = []
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
    for record in pool.map(fetch, selection):
        results.append(record)
        if record.get('error') or record.get('status', 0) >= 400 or record.get('counts', {}).get('h1', 1) != 1 or record.get('canonicals', [record['url']])[0].rstrip('/') != record.get('final_url', record['url']).rstrip('/') or len(results) % 10 == 0:
            print(json.dumps({key: value for key, value in record.items() if key not in ['links', 'html_file']}, ensure_ascii=False), flush=True)
(OUT / (report_name + '-pages.json')).write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding='utf-8')
canonical_urls = list(dict.fromkeys(urllib.parse.urljoin(record.get('final_url', record['url']), canonical) for record in results for canonical in record.get('canonicals', []) if canonical and canonical.rstrip('/') != record.get('final_url', record['url']).rstrip('/') and urllib.parse.urljoin(record.get('final_url', record['url']), canonical).startswith(BASE)))
canonical_results = []
with concurrent.futures.ThreadPoolExecutor(max_workers=3) as pool:
    for record in pool.map(fetch, canonical_urls):
        canonical_results.append(record)
        print('CANONICAL ' + json.dumps({key: value for key, value in record.items() if key not in ['links', 'html_file']}, ensure_ascii=False), flush=True)
(OUT / (report_name + '-canonical-targets.json')).write_text(json.dumps(canonical_results, ensure_ascii=False, indent=2), encoding='utf-8')
print('DONE ' + str(OUT), flush=True)
