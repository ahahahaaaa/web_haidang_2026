import difflib
import pathlib
import subprocess

root = pathlib.Path(__file__).resolve().parent.parent
output = root / 'outputs/seo-primary-fixes-20260918/haidangtravel-seo-primary-fixes.patch'
tracked = [
    'app/Http/Controllers/FrontsiteController.php',
    'app/Support/FrontsiteUrls.php',
    'resources/views/themes/haidangtravel/pages/landing/show.blade.php',
    'resources/views/themes/haidangtravel/partials/head.blade.php',
    'resources/views/themes/haidangtravel/partials/landing-content-blocks.blade.php',
    'resources/views/themes/haidangtravel/partials/landing-hero-demo.blade.php',
    'resources/views/themes/haidangtravel/partials/landing-hero.blade.php',
]
patch = subprocess.check_output(['git', 'diff', '--no-ext-diff', '--', *tracked], cwd=root).decode('utf-8')

# SitemapBuilder already had other work before this task. Export only our two edits.
sitemap_path = 'app/Services/Seo/SitemapBuilder.php'
current = (root / sitemap_path).read_text(encoding='utf-8')
before = current.replace('sitemap:entries:v4', 'sitemap:entries:v3').replace(
    'FrontsiteUrls::canonicalModelUrl($model, $url)',
    "FrontsiteUrls::canonicalUrl($model->getAttribute('canonical_url'))",
)
assert before != current
patch += f'diff --git a/{sitemap_path} b/{sitemap_path}\n'
patch += ''.join(difflib.unified_diff(before.splitlines(True), current.splitlines(True), fromfile='a/' + sitemap_path, tofile='b/' + sitemap_path))

new_path = 'app/Support/LandingPageHtml.php'
content = (root / new_path).read_text(encoding='utf-8')
patch += f'diff --git a/{new_path} b/{new_path}\nnew file mode 100644\n'
patch += ''.join(difflib.unified_diff([], content.splitlines(True), fromfile='/dev/null', tofile='b/' + new_path))
output.write_text(patch, encoding='utf-8', newline='\n')
print(f'Exported runtime patch: {output}')
