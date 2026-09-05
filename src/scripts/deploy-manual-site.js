/**
 * Deploy the generated manual website via rsync over SSH.
 */

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const SITE_DIR = path.resolve(ROOT, process.env.MANUAL_SITE_DIR || 'build/handbuch-site');
const USER = process.env.MANUAL_DEPLOY_USER || 'peregrinus';
const HOST = process.env.MANUAL_DEPLOY_HOST || 'pfarr.tools';
const PATH = process.env.MANUAL_DEPLOY_PATH || '/home/peregrinus/pfarr.tools/planer-handbuch/';
const TARGET = process.env.MANUAL_DEPLOY_TARGET || `${USER}@${HOST}:${PATH}`;
const SSH_OPTS = (process.env.MANUAL_DEPLOY_SSH_OPTS || '').split(/\s+/).filter(Boolean);
const DRY_RUN = process.argv.includes('--dry-run');

function run(command, args) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
            cwd: ROOT,
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

(async () => {
    if (['0', 'false', 'no'].includes(String(process.env.MANUAL_DEPLOY || '').toLowerCase())) {
        console.log('Skipping manual deploy because MANUAL_DEPLOY=0.');
        return;
    }

    if (!fs.existsSync(SITE_DIR)) {
        throw new Error(`Missing static manual site: ${SITE_DIR}. Run npm run manual:site first.`);
    }

    const source = `${SITE_DIR.replace(/\/$/, '')}/`;
    const args = ['-az', '--delete'];

    if (DRY_RUN) args.push('--dry-run');
    if (SSH_OPTS.length) args.push('-e', ['ssh', ...SSH_OPTS].join(' '));

    args.push(source, TARGET);

    console.log(`${DRY_RUN ? 'Checking' : 'Deploying'} manual site to ${TARGET}...`);
    await run('rsync', args);
})().catch((error) => {
    console.error(error.message);
    process.exit(1);
});
