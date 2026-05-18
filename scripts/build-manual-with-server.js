/**
 * Build the manual and PDF with a temporary local Laravel server.
 *
 * This is intended for release scripts: it updates generated manual pages,
 * starts `php artisan serve`, builds the static MkDocs site with PDF, and
 * stops the server again.
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const net = require('net');

const HOST = process.env.MANUAL_BUILD_HOST || '127.0.0.1';
const PORT = Number(process.env.MANUAL_BUILD_PORT || 8000);
const APP_URL = process.env.APP_URL || `http://${HOST}:${PORT}`;
const START_TIMEOUT = Number(process.env.MANUAL_BUILD_START_TIMEOUT || 30_000);
const SKIP_DUSK = ['1', 'true', 'yes'].includes(String(process.env.SKIP_DUSK || '').toLowerCase());
const SERVER_LOG = path.resolve('storage/logs/manual-build-server.log');

function logStep(message) {
    console.log(`\n==> ${message}`);
}

function parseEnvFile(file) {
    if (!fs.existsSync(file)) {
        return {};
    }

    return fs.readFileSync(file, 'utf8')
        .split(/\r?\n/u)
        .reduce((vars, line) => {
            const trimmed = line.trim();

            if (!trimmed || trimmed.startsWith('#')) {
                return vars;
            }

            const separator = trimmed.indexOf('=');
            if (separator === -1) {
                return vars;
            }

            const key = trimmed.slice(0, separator).trim();
            let value = trimmed.slice(separator + 1).trim();

            if (
                (value.startsWith('"') && value.endsWith('"'))
                || (value.startsWith("'") && value.endsWith("'"))
            ) {
                value = value.slice(1, -1);
            }

            vars[key] = value;

            return vars;
        }, {});
}

function resolveDuskEnvFile() {
    const appEnv = process.env.APP_ENV || 'local';
    const candidates = [
        path.resolve(`.env.dusk.${appEnv}`),
        path.resolve('.env.dusk.local'),
        path.resolve('.env.dusk'),
    ];

    return candidates.find((file) => fs.existsSync(file));
}

function buildManualEnv() {
    const duskEnvFile = resolveDuskEnvFile();
    const duskEnv = duskEnvFile ? parseEnvFile(duskEnvFile) : {};

    return {
        ...process.env,
        ...duskEnv,
        APP_URL,
    };
}

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
    const manualEnv = buildManualEnv();
    fs.mkdirSync(path.dirname(SERVER_LOG), { recursive: true });
    const serverLogFd = fs.openSync(SERVER_LOG, 'w');

    try {
        logStep(`Starte temporären Server auf ${APP_URL}`);
        console.log(`Server-Log: ${SERVER_LOG}`);
        server = spawn('php', ['artisan', 'serve', `--host=${HOST}`, `--port=${PORT}`], {
            stdio: ['ignore', serverLogFd, serverLogFd],
            shell: process.platform === 'win32',
            env: manualEnv,
        });

        await waitForPort(HOST, PORT, START_TIMEOUT);
        logStep('Server bereit');

        if (SKIP_DUSK) {
            logStep('Überspringe Screenshot-Tests (SKIP_DUSK gesetzt)');
        } else {
            logStep('Führe Screenshot-Tests aus');
            await run('php', ['vendor/phpunit/phpunit/phpunit', '--debug', '-c', 'phpunit.dusk.xml', 'tests/Browser/Manual'], {
                env: manualEnv,
            });
        }

        logStep('Erzeuge Lizenzübersicht');
        await run('npm', ['run', 'manual:licenses']);
        logStep('Baue Handbuchquellen');
        await run('php', ['artisan', 'build:manual']);
        logStep('Baue statische Handbuchseite');
        await run('node', ['scripts/build-manual-site.js']);
        logStep('Handbuch-Build abgeschlossen');
    } finally {
        await stopServer(server);
        fs.closeSync(serverLogFd);
    }
})().catch((error) => {
    console.error(error.message);
    console.error(`Server-Log: ${SERVER_LOG}`);
    process.exit(1);
});
