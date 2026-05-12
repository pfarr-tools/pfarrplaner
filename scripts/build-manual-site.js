/**
 * Build the static manual website with MkDocs Material.
 *
 * The source stays in manual/. This script derives the navigation from the
 * existing TOC comments, writes a temporary MkDocs config below build/, and
 * runs MkDocs. mkdocs-to-pdf creates handbuch.pdf as part of that build.
 */

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const MANUAL_DIR = path.join(ROOT, 'manual');
const BUILD_DIR = path.join(ROOT, 'build');
const FONTS_SRC_DIR = path.join(ROOT, 'resources', 'fonts');
const TEMPLATES_DIR = path.join(BUILD_DIR, 'manual-templates');
const SITE_SOURCE_DIR = path.join(BUILD_DIR, 'handbuch-source');
const SITE_DIR = path.resolve(ROOT, process.env.MANUAL_SITE_DIR || 'build/handbuch-site');
const CONFIG_FILE = path.join(BUILD_DIR, 'mkdocs.manual.yml');
const MKDOCS_PYTHON = process.env.MKDOCS_PYTHON
    ? path.resolve(process.env.MKDOCS_PYTHON)
    : path.join(process.env.HOME, '.local/share/pipx/venvs/mkdocs/bin/python');

function yamlString(value) {
    return `'${String(value).replaceAll("'", "''")}'`;
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

function getManualChapters() {
    const chapters = fs.readdirSync(MANUAL_DIR)
        .filter((file) => file.endsWith('.md'))
        .flatMap((file) => {
            const content = fs.readFileSync(path.join(MANUAL_DIR, file), 'utf8');
            const match = content.match(/\[\/\/\]: # \(TOC: ((?:\d+\.)*)\s*(.*?)\)/);

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

    return chapters;
}

function writeMkdocsConfig(chapters) {
    fs.mkdirSync(BUILD_DIR, { recursive: true });

    const version = getPackageVersion();
    const buildDate = getBuildDate();
    const pdfCopyright = `Pfarrplaner v.${version}, Stand: ${buildDate}`;


    const nav = chapters
        .map((chapter) => `  - ${yamlString(chapter.title)}: ${chapter.file}`)
        .join('\n');

    const config = [
        'site_name: Pfarrplaner Benutzerhandbuch',
        'site_url: https://handbuch.pfarrplaner.de/',
        'site_description: Benutzerhandbuch fuer Pfarrplaner. Auch erreichbar unter https://handbuch.planer.pfarr.tools/',
        'site_author: Christoph Fischer',
        'copyright: Copyright (c) Christoph Fischer. Pfarrplaner steht unter der GNU General Public License Version 3 oder spaeter.',
        'docs_dir: handbuch-source',
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
        '    - navigation.footer',
        '    - navigation.top',
        '    - search.highlight',
        '    - search.suggest',
        'extra_css:',
        '  - stylesheets/custom.css',
        'plugins:',
        '  - search',
        '  - enumerate-headings:',
        '      increment_across_pages: true',
        '      start_level: 1',
        '      toc_depth: 3',
        '      exclude:',
        '        - lizenzen.md',
        '        - versionsangaben.md',
        '        - stichwortverzeichnis.md',
        '  - to-pdf:',
        '      author: Christoph Fischer',
        `      copyright: ${yamlString(pdfCopyright)}`,
        '      cover_title: Pfarrplaner Benutzerhandbuch',
        '      cover_subtitle: Hilfe für die tägliche Arbeit im Pfarramt und Gemeindebüro',
        '      cover_logo: media/site/pfarrplaner.svg',
        '      toc_title: Inhaltsverzeichnis',
        '      toc_level: 1',
        '      output_path: handbuch.pdf',
        '      html_path: handbuch.html',
        '      ordered_chapter_level: 0',
        `      custom_template_path: ${yamlString(TEMPLATES_DIR)}`,
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
        nav,
        '',
    ].join('\n');

    fs.writeFileSync(CONFIG_FILE, config);
}

function copyDirectory(source, target) {
    fs.mkdirSync(target, { recursive: true });

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

function writePdfTemplates() {
    fs.mkdirSync(TEMPLATES_DIR, { recursive: true });

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
        `/* Redirect the brand color away from structural chrome — links only */`,
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

    fs.writeFileSync(path.join(TEMPLATES_DIR, 'styles.scss'), pdfStyles);
}

function prepareSiteSource() {
    fs.rmSync(SITE_SOURCE_DIR, { recursive: true, force: true });
    copyDirectory(MANUAL_DIR, SITE_SOURCE_DIR);

    fs.writeFileSync(path.join(SITE_SOURCE_DIR, 'handbuch.pdf'), '');

    const stylesheetsDir = path.join(SITE_SOURCE_DIR, 'stylesheets');
    fs.mkdirSync(stylesheetsDir, { recursive: true });
    fs.writeFileSync(path.join(stylesheetsDir, 'custom.css'), CUSTOM_CSS);

    const fontsDir = path.join(stylesheetsDir, 'fonts');
    fs.mkdirSync(fontsDir, { recursive: true });
    for (const font of MANUAL_FONTS) {
        fs.copyFileSync(path.join(FONTS_SRC_DIR, font.filename), path.join(fontsDir, font.filename));
    }
}

function writeRootRedirect() {
    fs.writeFileSync(path.join(SITE_DIR, 'index.html'), [
        '<!doctype html>',
        '<html lang="de">',
        '<head>',
        '    <meta charset="utf-8">',
        '    <meta http-equiv="refresh" content="0; url=einfuehrung/">',
        '    <title>Pfarrplaner Benutzerhandbuch</title>',
        '    <link rel="canonical" href="einfuehrung/">',
        '</head>',
        '<body>',
        '    <p><a href="einfuehrung/">Zum Pfarrplaner Benutzerhandbuch</a></p>',
        '</body>',
        '</html>',
        '',
    ].join('\n'));
}

function run(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            cwd: BUILD_DIR,
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

(async () => {
    const chapters = getManualChapters();
    prepareSiteSource();
    writePdfTemplates();
    writeMkdocsConfig(chapters);

    console.log(`Building static manual site into ${SITE_DIR}...`);
    console.log(`Using MkDocs Python: ${MKDOCS_PYTHON}`);
    await run(MKDOCS_PYTHON, ['-m', 'mkdocs', 'build', '-f', CONFIG_FILE]);
    writeRootRedirect();
})().catch((error) => {
    if (error.code === 'ENOENT' || error.message.includes('ENOENT')) {
        console.error('MkDocs was not found. Install the manual build dependencies with:');
        console.error('  python3 -m pip install -r requirements-manual.txt');
    }

    console.error(error.message);
    process.exit(1);
});
