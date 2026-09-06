/**
 * Build the static handbook website with MkDocs Material.
 *
 * The public result is one MkDocs site with three handbook sections in the
 * sidebar. Separate PDF builds are generated for each handbook and copied
 * into the matching section so the site can expose three PDF links.
 */

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const MANUAL_ROOT = path.resolve(
    process.env.PFARRPLANER_MANUAL_ROOT
        || (fs.existsSync(path.join(ROOT, '..', 'docs', 'manual'))
            ? path.join(ROOT, '..', 'docs', 'manual')
            : path.join(ROOT, 'manual'))
);
const BUILD_DIR = path.join(ROOT, 'build');
const UNIFIED_BUILD_DIR = path.join(BUILD_DIR, 'manual-site');
const DOCS_SOURCE_DIR = path.join(UNIFIED_BUILD_DIR, 'source');
const PDF_TEMPLATE_DIR = path.join(UNIFIED_BUILD_DIR, 'pdf-templates');
const THEME_OVERRIDE_DIR = path.join(UNIFIED_BUILD_DIR, 'theme-overrides');
const CONFIG_FILE = path.join(UNIFIED_BUILD_DIR, 'mkdocs.handbook.yml');
const SITE_DIR = path.resolve(ROOT, process.env.MANUAL_SITE_DIR || 'build/handbuch-site');
const FONTS_SRC_DIR = path.join(ROOT, 'resources', 'fonts');
const SHARED_SITE_ASSETS_DIR = path.join(MANUAL_ROOT, 'media', 'site');
const SHARED_LICENSE_ASSETS_DIR = path.join(MANUAL_ROOT, 'media', 'licenses');
const OPENAPI_FILE = path.join(ROOT, 'public', 'openapi.json');
const MKDOCS_PYTHON = process.env.MKDOCS_PYTHON
    ? path.resolve(process.env.MKDOCS_PYTHON)
    : path.join(process.env.HOME, '.local/share/pipx/venvs/mkdocs/bin/python');

const MANUALS = [
    {
        id: 'benutzerhandbuch',
        sourceDir: path.join(MANUAL_ROOT, 'benutzerhandbuch'),
        title: 'Pfarrplaner Benutzerhandbuch',
        shortTitle: 'Benutzerhandbuch',
        description: 'Hilfe für die tägliche Arbeit im Pfarramt und Gemeindebüro.',
        coverTitle: 'Pfarrplaner\\nBenutzerhandbuch',
        coverSubtitle: 'Hilfe für die tägliche Arbeit im Pfarramt und Gemeindebüro',
        pdfFile: 'benutzerhandbuch.pdf',
        htmlFile: 'benutzerhandbuch.html',
    },
    {
        id: 'administratorhandbuch',
        sourceDir: path.join(MANUAL_ROOT, 'administratorhandbuch'),
        title: 'Pfarrplaner Administratorhandbuch',
        shortTitle: 'Administratorhandbuch',
        description: 'Installation, Betrieb, Updates und Wartung für Administratorinnen und Administratoren.',
        coverTitle: 'Pfarrplaner\\nAdministratorhandbuch',
        coverSubtitle: 'Installation, Betrieb, Updates und Wartung',
        pdfFile: 'administratorhandbuch.pdf',
        htmlFile: 'administratorhandbuch.html',
    },
    {
        id: 'technikhandbuch',
        sourceDir: path.join(MANUAL_ROOT, 'technikhandbuch'),
        title: 'Pfarrplaner Technisches Handbuch',
        shortTitle: 'Technisches Handbuch',
        description: 'Architektur, Entwicklung, Deployment und API-Dokumentation.',
        coverTitle: 'Pfarrplaner\\nTechnisches Handbuch',
        coverSubtitle: 'Architektur, Entwicklung, Deployment und API-Dokumentation',
        pdfFile: 'technikhandbuch.pdf',
        htmlFile: 'technikhandbuch.html',
    },
];

const MANUAL_FONTS = [
    { filename: 'Sarabun-Light.ttf', weight: 300 },
    { filename: 'Sarabun-SemiBold.ttf', weight: 600 },
];

const CUSTOM_CSS = `
@font-face {
    font-family: 'Sarabun';
    font-weight: 300;
    font-style: normal;
    src: url('fonts/Sarabun-Light.ttf') format('truetype');
}

@font-face {
    font-family: 'Sarabun';
    font-weight: 600;
    font-style: normal;
    src: url('fonts/Sarabun-SemiBold.ttf') format('truetype');
}

:root,
[data-md-color-scheme="default"],
[data-md-color-scheme="slate"] {
    --md-primary-fg-color: #591855;
    --md-primary-fg-color--light: #7d2378;
    --md-primary-fg-color--dark: #3d1039;
    --md-primary-bg-color: #ffffff;
    --md-primary-bg-color--light: rgba(255, 255, 255, 0.7);
    --md-accent-fg-color: #591855;
    --md-accent-fg-color--transparent: rgba(89, 24, 85, 0.1);
    --md-accent-bg-color: #ffffff;
    --md-accent-bg-color--light: rgba(255, 255, 255, 0.7);
}

body,
.md-typeset {
    font-family: 'Sarabun', sans-serif;
    font-weight: 300;
}

.md-typeset h1,
.md-typeset h2,
.md-typeset h3,
.md-typeset h4,
.md-typeset h5,
.md-typeset h6 {
    font-family: 'Sarabun', sans-serif;
    font-weight: 600;
}
`.trimStart();

const PDF_LINK_CSS = `
.manual-pdf-links {
    display: flex;
    gap: .9rem;
    margin-left: auto;
    margin-right: .75rem;
    align-items: center;
    flex-wrap: nowrap;
}

.manual-pdf-links__group {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    white-space: nowrap;
    font-size: .78rem;
    line-height: 1.2;
}

.manual-pdf-links__title {
    color: #ffffff;
}

.manual-pdf-links__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.55rem;
    height: 1.55rem;
    border: 1px solid rgba(255, 255, 255, .45);
    color: #ffffff;
    text-decoration: none;
}

.manual-pdf-links__icon:hover,
.manual-pdf-links__icon:focus {
    background: rgba(255, 255, 255, .12);
}

.manual-pdf-links__icon svg {
    width: .92rem;
    height: .92rem;
    fill: currentColor;
}

@media (max-width: 76.1875em) {
    .manual-pdf-links {
        display: none;
    }
}
`.trimStart();

function yamlString(value) {
    return `'${String(value).replaceAll("'", "''")}'`;
}

function ensureDirectory(directory) {
    fs.mkdirSync(directory, { recursive: true });
}

function removeDirectory(directory) {
    fs.rmSync(directory, {
        recursive: true,
        force: true,
        maxRetries: 10,
        retryDelay: 200,
    });
}

function copyDirectory(source, target) {
    ensureDirectory(target);

    for (const entry of fs.readdirSync(source, { withFileTypes: true })) {
        const sourcePath = path.join(source, entry.name);
        const targetPath = path.join(target, entry.name);

        if (entry.isDirectory()) {
            copyDirectory(sourcePath, targetPath);
            continue;
        }

        if (entry.isFile()) {
            fs.copyFileSync(sourcePath, targetPath);
        }
    }
}

function applyManualChapterHeading(content) {
    const match = getTocMatch(content);
    if (!match) return content;

    const number = match[1].replace(/\.$/, '');
    const title = match[2].trim();
    const heading = number && !number.startsWith('0')
        ? `# ${number}. ${title}`
        : `# ${title}`;
    const counters = [0, 0, 0, 0, 0, 0, 0];
    let inFence = false;

    return content
        .replace(/^#\s+.*$/m, heading)
        .split('\n')
        .map((line) => {
            if (/^(```|~~~)/.test(line)) {
                inFence = !inFence;
                return line;
            }

            if (inFence || !number || number.startsWith('0')) return line;

            const matchHeading = line.match(/^(#{2,6})\s+(.*)$/);
            if (!matchHeading) return line;

            const level = matchHeading[1].length;
            const headingText = matchHeading[2];

            counters[level] += 1;
            for (let index = level + 1; index < counters.length; index += 1) {
                counters[index] = 0;
            }

            const subNumbers = [number];
            for (let index = 2; index <= level; index += 1) {
                if (counters[index] > 0) subNumbers.push(String(counters[index]));
            }

            return `${matchHeading[1]} ${subNumbers.join('.')}. ${headingText}`;
        })
        .join('\n');
}

function copyManualSource(source, target) {
    ensureDirectory(target);

    for (const entry of fs.readdirSync(source, { withFileTypes: true })) {
        const sourcePath = path.join(source, entry.name);
        const targetPath = path.join(target, entry.name);

        if (entry.isDirectory()) {
            copyManualSource(sourcePath, targetPath);
            continue;
        }

        if (!entry.isFile()) continue;

        if (entry.name.endsWith('.md')) {
            fs.writeFileSync(targetPath, applyManualChapterHeading(fs.readFileSync(sourcePath, 'utf8')));
            continue;
        }

        fs.copyFileSync(sourcePath, targetPath);
    }
}

function getPackageVersion() {
    const packageJson = JSON.parse(fs.readFileSync(path.join(ROOT, 'package.json'), 'utf8'));
    return packageJson.version;
}

function getBuildDate() {
    return new Intl.DateTimeFormat('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        timeZone: 'Europe/Berlin',
    }).format(new Date());
}

function compareChapterNumbers(left, right) {
    const leftParts = left.split('.').map((part) => Number(part));
    const rightParts = right.split('.').map((part) => Number(part));
    const length = Math.max(leftParts.length, rightParts.length);

    for (let index = 0; index < length; index += 1) {
        const diff = (leftParts[index] || 0) - (rightParts[index] || 0);
        if (diff !== 0) return diff;
    }

    return 0;
}

function getTocMatch(content) {
    return content.match(/\[\/\/\]: # \(TOC: ((?:\d+\.)*)\s*(.*?)\)/);
}

function getManualChapters(sourceDir) {
    return fs.readdirSync(sourceDir)
        .filter((file) => file.endsWith('.md'))
        .flatMap((file) => {
            const content = fs.readFileSync(path.join(sourceDir, file), 'utf8');
            const match = getTocMatch(content);

            if (!match) return [];

            const number = match[1].replace(/\.$/, '');
            const title = match[2].trim();
            const label = number && !number.startsWith('0') ? `${number}. ${title}` : title;

            return [{
                number,
                title: label,
                file,
            }];
        })
        .sort((left, right) => compareChapterNumbers(left.number, right.number));
}

function makeHomepageContent() {
    return [
        '<meta http-equiv="refresh" content="0; url=benutzerhandbuch/">',
        '<script>window.location.replace("benutzerhandbuch/");</script>',
        '',
        '# Weiterleitung',
        '',
        'Wenn die Weiterleitung nicht automatisch funktioniert, öffnen Sie das [Benutzerhandbuch](benutzerhandbuch/).',
        '',
    ].join('\n');
}

function copySharedRootAssets() {
    const mediaRoot = path.join(DOCS_SOURCE_DIR, 'media');
    ensureDirectory(mediaRoot);
    copyDirectory(SHARED_SITE_ASSETS_DIR, path.join(mediaRoot, 'site'));
    copyDirectory(SHARED_LICENSE_ASSETS_DIR, path.join(mediaRoot, 'licenses'));
}

function prepareUnifiedSource() {
    removeDirectory(UNIFIED_BUILD_DIR);
    ensureDirectory(DOCS_SOURCE_DIR);

    fs.writeFileSync(path.join(DOCS_SOURCE_DIR, 'index.md'), makeHomepageContent());
    copySharedRootAssets();

    for (const manual of MANUALS) {
        const targetDir = path.join(DOCS_SOURCE_DIR, manual.id);
        copyManualSource(manual.sourceDir, targetDir);
        copyDirectory(path.join(MANUAL_ROOT, 'media'), path.join(targetDir, 'media'));

        if (manual.id === 'technikhandbuch' && fs.existsSync(OPENAPI_FILE)) {
            fs.copyFileSync(OPENAPI_FILE, path.join(targetDir, 'openapi.json'));
        }

        fs.writeFileSync(path.join(targetDir, manual.pdfFile), '');
    }

    const stylesheetsDir = path.join(DOCS_SOURCE_DIR, 'stylesheets');
    ensureDirectory(stylesheetsDir);
    fs.writeFileSync(path.join(stylesheetsDir, 'custom.css'), CUSTOM_CSS);
    fs.writeFileSync(path.join(stylesheetsDir, 'header-links.css'), PDF_LINK_CSS);

    const fontsDir = path.join(stylesheetsDir, 'fonts');
    ensureDirectory(fontsDir);
    for (const font of MANUAL_FONTS) {
        fs.copyFileSync(path.join(FONTS_SRC_DIR, font.filename), path.join(fontsDir, font.filename));
    }

    const jsDir = path.join(DOCS_SOURCE_DIR, 'javascripts');
    ensureDirectory(jsDir);
    fs.writeFileSync(path.join(jsDir, 'pdf-links.js'), makePdfLinkScript());
}

function makePdfLinkScript() {
    const links = MANUALS.map((manual) => ({
        label: manual.shortTitle,
        href: `/${manual.id}/${manual.pdfFile}`,
    }));

    return [
        'document.addEventListener("DOMContentLoaded", () => {',
        '  const headerInner = document.querySelector(".md-header__inner");',
        '  if (!headerInner || headerInner.querySelector(".manual-pdf-links")) return;',
        '  const container = document.createElement("nav");',
        '  container.className = "manual-pdf-links";',
        `  const links = ${JSON.stringify(links)};`,
        '  const iconSvg = `<svg viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm0 2.5L18.5 9H14zM8 13h8v2H8zm0 4h8v2H8zm0-8h5v2H8z"/></svg>`;',
        '  for (const link of links) {',
        '    const group = document.createElement("span");',
        '    group.className = "manual-pdf-links__group";',
        '    const title = document.createElement("span");',
        '    title.className = "manual-pdf-links__title";',
        '    title.textContent = link.label;',
        '    const anchor = document.createElement("a");',
        '    anchor.className = "manual-pdf-links__icon";',
        '    anchor.href = link.href;',
        '    anchor.innerHTML = iconSvg;',
        '    anchor.setAttribute("target", "_blank");',
        '    anchor.setAttribute("rel", "noopener");',
        '    anchor.setAttribute("aria-label", `${link.label} als PDF öffnen`);',
        '    anchor.setAttribute("title", `${link.label} als PDF öffnen`);',
        '    group.appendChild(title);',
        '    group.appendChild(anchor);',
        '    container.appendChild(group);',
        '  }',
        '  const firstOption = headerInner.querySelector(".md-header__option, .md-header__source");',
        '  if (firstOption) {',
        '    headerInner.insertBefore(container, firstOption);',
        '  } else {',
        '    headerInner.appendChild(container);',
        '  }',
        '});',
        '',
    ].join('\n');
}

function writePdfTemplates() {
    ensureDirectory(PDF_TEMPLATE_DIR);

    const fontUrl = (filename) => `file://${path.join(FONTS_SRC_DIR, filename).replaceAll('\\', '/')}`;
    const pdfStyles = [
        `@font-face {`,
        `    font-family: 'Sarabun';`,
        `    font-weight: 300;`,
        `    font-style: normal;`,
        `    src: url('${fontUrl('Sarabun-Light.ttf')}') format('truetype');`,
        `}`,
        ``,
        `@font-face {`,
        `    font-family: 'Sarabun';`,
        `    font-weight: 600;`,
        `    font-style: normal;`,
        `    src: url('${fontUrl('Sarabun-SemiBold.ttf')}') format('truetype');`,
        `}`,
        ``,
        `:root {`,
        `    --md-primary-fg-color: #000000;`,
        `    --md-primary-fg-color--light: #333333;`,
        `    --md-primary-fg-color--dark: #000000;`,
        `}`,
        ``,
        `* { font-family: 'Sarabun', sans-serif !important; }`,
        ``,
        `body, p, li, td, th { font-weight: 300; }`,
        ``,
        `h1, h2, h3, h4, h5, h6 {`,
        `    font-weight: 600;`,
        `    color: #000000;`,
        `    border-color: #000000 !important;`,
        `}`,
        ``,
        `hr { border-color: #000000 !important; }`,
        ``,
        `a, a:link, a:visited { color: #591855 !important; }`,
    ].join('\n');

    fs.writeFileSync(path.join(PDF_TEMPLATE_DIR, 'styles.scss'), pdfStyles);
    fs.writeFileSync(path.join(PDF_TEMPLATE_DIR, 'cover.html.j2'), [
        '<article id="doc-cover">',
        '    {% if cover_logo is defined %}',
        '        <div class="wrapper upper">',
        '            <div class="logo" style="background-image: url({{ cover_logo | to_url }});"></div>',
        '        </div>',
        '    {% else %}',
        '        <div class="wrapper"></div>',
        '    {% endif %}',
        '    <div class="wrapper">',
        '        <h1>{{ cover_title | e | replace(\'\\\\n\', \'<br>\') | safe }}</h1>',
        '        <h2>{{ cover_subtitle | e }}</h2>',
        '    </div>',
        '    <div class="properties">',
        '        <address>',
        '            {% if author is defined %}',
        '                <p id="author">{{ author | e }}</p>',
        '            {% endif %}',
        '            {% if copyright is defined %}',
        '                <p id="copyright">{{ copyright | e }}</p>',
        '            {% endif %}',
        '        </address>',
        '    </div>',
        '</article>',
        '',
    ].join('\n'));
}

function sectionNav(manual, chapters) {
    const lines = [`  - ${yamlString(manual.shortTitle)}:`];

    for (const chapter of chapters.filter((chapter) => chapter.number !== '0')) {
        lines.push(`      - ${yamlString(chapter.title)}: ${manual.id}/${chapter.file}`);
    }

    return lines.join('\n');
}

function writeUnifiedConfig(manualChapters) {
    const navSections = MANUALS.map((manual) => sectionNav(manual, manualChapters[manual.id])).join('\n');

    const config = [
        `site_name: ${yamlString('Pfarrplaner-Handbücher')}`,
        `site_url: ${yamlString('https://handbuch.pfarrplaner.de/')}`,
        `site_description: ${yamlString('Benutzerhandbuch, Administratorhandbuch und technisches Handbuch für Pfarrplaner.')}`,
        'site_author: Christoph Fischer',
        'copyright: Copyright (c) Christoph Fischer. Pfarrplaner steht unter der GNU General Public License Version 3 oder später.',
        `docs_dir: ${yamlString(DOCS_SOURCE_DIR)}`,
        `site_dir: ${yamlString(SITE_DIR)}`,
        'use_directory_urls: true',
        'theme:',
        '  name: material',
        '  language: de',
        '  font: false',
        '  palette:',
        '    - primary: custom',
        '      accent: custom',
        '  logo: media/site/pfarrplaner.svg',
        '  favicon: media/site/favicon.ico',
        '  features:',
        '    - navigation.instant',
        '    - navigation.sections',
        '    - navigation.expand',
        '    - navigation.footer',
        '    - navigation.top',
        '    - search.highlight',
        '    - search.suggest',
        'extra_css:',
        '  - stylesheets/custom.css',
        '  - stylesheets/header-links.css',
        'extra_javascript:',
        '  - javascripts/pdf-links.js',
        'plugins:',
        '  - search',
        'markdown_extensions:',
        '  - admonition',
        '  - attr_list',
        '  - md_in_html',
        '  - sane_lists',
        '  - tables',
        '  - toc:',
        '      permalink: true',
        '  - pymdownx.emoji:',
        '      emoji_index: !!python/name:material.extensions.emoji.twemoji',
        '      emoji_generator: !!python/name:material.extensions.emoji.to_svg',
        'extra:',
        '  alternate_domains:',
        '    - https://handbuch.planer.pfarr.tools/',
        'nav:',
        navSections,
        '',
    ].join('\n');

    fs.writeFileSync(CONFIG_FILE, config);
}

function pdfBuildPaths(manual) {
    const buildDir = path.join(UNIFIED_BUILD_DIR, `pdf-${manual.id}`);
    return {
        buildDir,
        configFile: path.join(buildDir, `mkdocs.${manual.id}.yml`),
        docsDir: path.join(buildDir, 'source'),
        siteDir: path.join(buildDir, 'site'),
    };
}

function preparePdfSource(manual, paths) {
    ensureDirectory(paths.docsDir);
    copyManualSource(manual.sourceDir, paths.docsDir);
    fs.writeFileSync(path.join(paths.docsDir, manual.pdfFile), '');

    copyDirectory(path.join(MANUAL_ROOT, 'media'), path.join(paths.docsDir, 'media'));

    if (manual.id === 'technikhandbuch' && fs.existsSync(OPENAPI_FILE)) {
        fs.copyFileSync(OPENAPI_FILE, path.join(paths.docsDir, 'openapi.json'));
    }
}

function writePdfConfig(manual, chapters, paths) {
    ensureDirectory(paths.buildDir);

    const version = getPackageVersion();
    const buildDate = getBuildDate();
    const pdfCopyright = `Pfarrplaner v.${version}, Stand: ${buildDate}`;
    const nav = chapters
        .map((chapter) => `  - ${yamlString(chapter.title)}: ${chapter.file}`)
        .join('\n');

    const config = [
        `site_name: ${yamlString(manual.title)}`,
        `site_url: ${yamlString(`https://handbuch.pfarrplaner.de/${manual.id}/`)}`,
        `site_description: ${yamlString(manual.description)}`,
        'site_author: Christoph Fischer',
        'copyright: Copyright (c) Christoph Fischer. Pfarrplaner steht unter der GNU General Public License Version 3 oder später.',
        `docs_dir: ${yamlString(paths.docsDir)}`,
        `site_dir: ${yamlString(paths.siteDir)}`,
        'use_directory_urls: true',
        'theme:',
        '  name: material',
        '  language: de',
        '  font: false',
        'plugins:',
        '  - search',
        `  - to-pdf:`,
        '      author: Christoph Fischer',
        `      copyright: ${yamlString(pdfCopyright)}`,
        `      cover_title: ${yamlString(manual.coverTitle)}`,
        `      cover_subtitle: ${yamlString(manual.coverSubtitle)}`,
        '      cover_logo: media/site/pfarrplaner.svg',
        '      toc_title: Inhaltsverzeichnis',
        '      toc_level: 1',
        `      output_path: ${yamlString(manual.pdfFile)}`,
        `      html_path: ${yamlString(manual.htmlFile)}`,
        '      ordered_chapter_level: 0',
        `      custom_template_path: ${yamlString(PDF_TEMPLATE_DIR)}`,
        'markdown_extensions:',
        '  - admonition',
        '  - attr_list',
        '  - md_in_html',
        '  - sane_lists',
        '  - tables',
        '  - toc:',
        '      permalink: true',
        'nav:',
        nav,
        '',
    ].join('\n');

    fs.writeFileSync(paths.configFile, config);
}

async function run(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            cwd: options.cwd || BUILD_DIR,
            stdio: 'inherit',
            shell: process.platform === 'win32',
            ...options,
        });

        child.on('error', reject);
        child.on('exit', (code, signal) => {
            if (code === 0) {
                resolve();
                return;
            }

            reject(new Error(`${command} ${args.join(' ')} failed with ${signal || code}`));
        });
    });
}

async function buildUnifiedSite() {
    const manualChapters = Object.fromEntries(
        MANUALS.map((manual) => [manual.id, getManualChapters(manual.sourceDir)])
    );

    prepareUnifiedSource();
    writePdfTemplates();
    writeUnifiedConfig(manualChapters);

    removeDirectory(SITE_DIR);
    ensureDirectory(SITE_DIR);

    console.log(`Building unified handbook site into ${SITE_DIR}...`);
    console.log(`Using MkDocs Python: ${MKDOCS_PYTHON}`);
    await run(MKDOCS_PYTHON, ['-m', 'mkdocs', 'build', '-f', CONFIG_FILE], { cwd: UNIFIED_BUILD_DIR });

    return manualChapters;
}

async function buildPdf(manual, chapters) {
    const paths = pdfBuildPaths(manual);
    removeDirectory(paths.buildDir);
    ensureDirectory(paths.buildDir);
    preparePdfSource(manual, paths);
    writePdfConfig(manual, chapters, paths);

    console.log(`Building PDF for ${manual.title}...`);
    await run(MKDOCS_PYTHON, ['-m', 'mkdocs', 'build', '-f', paths.configFile], { cwd: UNIFIED_BUILD_DIR });

    const targetDir = path.join(SITE_DIR, manual.id);
    ensureDirectory(targetDir);
    fs.copyFileSync(path.join(paths.siteDir, manual.pdfFile), path.join(targetDir, manual.pdfFile));

    const htmlPath = path.join(paths.siteDir, manual.htmlFile);
    if (fs.existsSync(htmlPath)) {
        fs.copyFileSync(htmlPath, path.join(targetDir, manual.htmlFile));
    }
}

(async () => {
    const manualChapters = await buildUnifiedSite();

    for (const manual of MANUALS) {
        await buildPdf(manual, manualChapters[manual.id]);
    }
})().catch((error) => {
    if (error.code === 'ENOENT' || error.message.includes('ENOENT')) {
        console.error('MkDocs was not found. Install the manual build dependencies with:');
        console.error('  python3 -m pip install -r requirements-manual.txt');
    }

    console.error(error.message);
    process.exit(1);
});
