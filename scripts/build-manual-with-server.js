/**
 * Build the manual and PDF with a temporary local Laravel server.
 *
 * This is intended for release scripts: it updates generated manual pages,
 * starts `php artisan serve`, builds the static MkDocs site with PDF, and
 * stops the server again.
 */

const { spawn } = require('child_process');
const net = require('net');

const HOST = process.env.MANUAL_BUILD_HOST || '127.0.0.1';
const PORT = Number(process.env.MANUAL_BUILD_PORT || 8000);
const APP_URL = process.env.APP_URL || `http://${HOST}:${PORT}`;
const START_TIMEOUT = Number(process.env.MANUAL_BUILD_START_TIMEOUT || 30_000);
const SKIP_DUSK = ['1', 'true', 'yes'].includes(String(process.env.SKIP_DUSK || '').toLowerCase());

function run(command, args, options = {}) {
    return new Promise((resolve, reject) => {
        const child = spawn(command, args, {
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

function waitForPort(host, port, timeoutMs) {
    const startedAt = Date.now();

    return new Promise((resolve, reject) => {
        const tryConnect = () => {
            const socket = net.createConnection({ host, port });

            socket.once('connect', () => {
                socket.end();
                resolve();
            });

            socket.once('error', () => {
                socket.destroy();

                if (Date.now() - startedAt > timeoutMs) {
                    reject(new Error(`Timed out waiting for ${host}:${port}`));
                    return;
                }

                setTimeout(tryConnect, 250);
            });
        };

        tryConnect();
    });
}

function stopServer(server) {
    if (!server || server.killed) return Promise.resolve();

    return new Promise((resolve) => {
        const timeout = setTimeout(() => {
            if (!server.killed) server.kill('SIGKILL');
            resolve();
        }, 5_000);

        server.once('exit', () => {
            clearTimeout(timeout);
            resolve();
        });

        server.kill('SIGTERM');
    });
}

(async () => {
    let server = null;

    try {
        console.log(`Starting manual build server at ${APP_URL}...`);
        server = spawn('php', ['artisan', 'serve', `--host=${HOST}`, `--port=${PORT}`], {
            stdio: ['ignore', 'pipe', 'pipe'],
            shell: process.platform === 'win32',
        });

        server.stdout.on('data', (chunk) => process.stdout.write(chunk));
        server.stderr.on('data', (chunk) => process.stderr.write(chunk));

        await waitForPort(HOST, PORT, START_TIMEOUT);

        if (SKIP_DUSK) {
            console.log('Skipping manual screenshots because SKIP_DUSK is set.');
        } else {
            await run('php', ['artisan', 'dusk', 'tests/Browser/Manual'], {
                env: {
                    ...process.env,
                    APP_URL,
                },
            });
        }

        await run('npm', ['run', 'manual:licenses']);
        await run('php', ['artisan', 'build:manual']);
        await run('node', ['scripts/build-manual-site.js']);
    } finally {
        await stopServer(server);
    }
})().catch((error) => {
    console.error(error.message);
    process.exit(1);
});
