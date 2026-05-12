/**
 * Publish the manual source into a dedicated Git repository.
 *
 * This intentionally is not part of the release script. It overwrites the
 * target repository content with the current manual source when run manually.
 */

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const TARGET_DIR = path.join(ROOT, 'build/manual-repository');
const REMOTE = process.env.MANUAL_SOURCE_REMOTE || 'ssh://git@codeberg.org/pfarr.tools/pfarrplaner-manual.git';
const BRANCH = process.env.MANUAL_SOURCE_BRANCH || 'main';

function run(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            cwd: options.cwd || ROOT,
            stdio: 'inherit',
            shell: process.platform === 'win32',
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

(async () => {
    fs.rmSync(TARGET_DIR, { recursive: true, force: true });
    fs.mkdirSync(TARGET_DIR, { recursive: true });

    copyDirectory(path.join(ROOT, 'manual'), path.join(TARGET_DIR, 'manual'));
    fs.copyFileSync(path.join(ROOT, 'requirements-manual.txt'), path.join(TARGET_DIR, 'requirements-manual.txt'));
    fs.writeFileSync(path.join(TARGET_DIR, 'mkdocs.yml'), [
        'site_name: Pfarrplaner Benutzerhandbuch',
        'site_url: https://handbuch.pfarrplaner.de/',
        'docs_dir: manual',
        'site_dir: site',
        'theme:',
        '  name: material',
        '  language: de',
        '  logo: media/site/pfarrplaner.svg',
        '  favicon: media/site/favicon.ico',
        '  features:',
        '    - navigation.footer',
        '    - navigation.top',
        'plugins:',
        '  - search',
        '',
    ].join('\n'));

    fs.writeFileSync(path.join(TARGET_DIR, 'README.md'), [
        '# Pfarrplaner-Handbuch',
        '',
        'Dieses Repository enthält die Quellen des Pfarrplaner-Benutzerhandbuchs.',
        '',
        '```sh',
        'python3 -m pip install -r requirements-manual.txt',
        'mkdocs build',
        '```',
        '',
    ].join('\n'));

    await run('git', ['init', '-b', BRANCH], { cwd: TARGET_DIR });
    await run('git', ['add', '.'], { cwd: TARGET_DIR });
    await run('git', ['commit', '-m', 'docs: Handbuchquellen aktualisieren'], { cwd: TARGET_DIR });
    await run('git', ['remote', 'add', 'origin', REMOTE], { cwd: TARGET_DIR });
    await run('git', ['push', '--force', 'origin', `${BRANCH}:${BRANCH}`], { cwd: TARGET_DIR });
})().catch((error) => {
    console.error(error.message);
    process.exit(1);
});
