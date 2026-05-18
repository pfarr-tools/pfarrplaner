#!/usr/bin/env node

/*
 * Pfarrplaner
 *
 * @package Pfarrplaner
 * @author Christoph Fischer <chris@toph.de>
 * @copyright (c) Christoph Fischer, https://christoph-fischer.org
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPL 3.0 or later
 * @link https://codeberg.org/pfarr.tools/pfarrplaner
 * @version git: $Id$
 */

'use strict';

const { spawnSync } = require('child_process');
const path = require('path');

const rootDir = path.resolve(__dirname, '..');
const isDryRun = process.argv.includes('--dry-run');

/**
 * Print a section header.
 *
 * @param {string} title Section title
 * @return {void}
 */
function section(title) {
    console.log(`\n${title}`);
}

/**
 * Run a command and inherit stdio.
 *
 * @param {string} command Command binary
 * @param {string[]} args Command arguments
 * @return {void}
 */
function run(command, args) {
    const result = spawnSync(command, args, {
        cwd: rootDir,
        stdio: 'inherit',
    });

    if (result.error) {
        throw result.error;
    }

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

/**
 * Run a command and capture its output.
 *
 * @param {string} command Command binary
 * @param {string[]} args Command arguments
 * @param {boolean} allowFailure Allow non-zero exit code?
 * @return {string|null}
 */
function capture(command, args, allowFailure = false) {
    const result = spawnSync(command, args, {
        cwd: rootDir,
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
    });

    if (result.error) {
        throw result.error;
    }

    if (result.status !== 0) {
        if (allowFailure) {
            return null;
        }

        process.stderr.write(result.stderr || '');
        process.exit(result.status ?? 1);
    }

    return (result.stdout || '').trim();
}

/**
 * Read a file from a git revision if it exists.
 *
 * @param {string} revision Git revision
 * @param {string} file Path relative to repository root
 * @return {string|null}
 */
function getGitFile(revision, file) {
    return capture('git', ['show', `${revision}:${file}`], true);
}

/**
 * Return whether the working tree is dirty.
 *
 * @return {boolean}
 */
function hasLocalChanges() {
    return (capture('git', ['status', '--porcelain']) || '') !== '';
}

/**
 * Return a normalized JSON string without the top-level version field.
 *
 * @param {string|null} content JSON text
 * @return {string|null}
 */
function normalizeJson(content) {
    if (content === null) {
        return null;
    }

    const parsed = JSON.parse(content);
    if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
        delete parsed.version;
    }

    return JSON.stringify(sortKeys(parsed));
}

/**
 * Sort keys recursively so JSON comparisons are stable.
 *
 * @param {*} value Value to normalize
 * @return {*}
 */
function sortKeys(value) {
    if (Array.isArray(value)) {
        return value.map(sortKeys);
    }

    if (value && typeof value === 'object') {
        return Object.keys(value)
            .sort()
            .reduce((result, key) => {
                result[key] = sortKeys(value[key]);
                return result;
            }, {});
    }

    return value;
}

/**
 * Check whether a JSON file changed in a meaningful way beyond version only.
 *
 * @param {string} file Path relative to repository root
 * @param {string} beforeRevision Old revision
 * @param {string} afterRevision New revision
 * @return {boolean}
 */
function hasMeaningfulJsonChange(file, beforeRevision, afterRevision) {
    const beforeContent = getGitFile(beforeRevision, file);
    const afterContent = getGitFile(afterRevision, file);

    if (beforeContent === afterContent) {
        return false;
    }

    try {
        return normalizeJson(beforeContent) !== normalizeJson(afterContent);
    } catch {
        return true;
    }
}

/**
 * Check whether any file starts with one of the provided prefixes.
 *
 * @param {string[]} files Changed files
 * @param {string[]} prefixes Path prefixes
 * @return {boolean}
 */
function includesPath(files, prefixes) {
    return files.some((file) => prefixes.some((prefix) => file.startsWith(prefix)));
}

/**
 * Build the action list for the pending update.
 *
 * @param {string[]} files Changed files
 * @param {string} beforeRevision Old revision
 * @param {string} afterRevision New revision
 * @return {{composerInstall: boolean, npmInstall: boolean, browserslist: boolean, build: boolean, migrations: boolean, viewCache: boolean, optimize: boolean, queueRestart: boolean, ping: boolean, octaneReload: boolean, messages: string[]}}
 */
function determineActions(files, beforeRevision, afterRevision) {
    const composerJsonChanged = files.includes('composer.json');
    const composerLockChanged = files.includes('composer.lock');
    const packageJsonChanged = files.includes('package.json');
    const packageLockChanged = files.includes('package-lock.json') || files.includes('npm-shrinkwrap.json');
    const composerInstall = composerLockChanged || (
        composerJsonChanged
        && hasMeaningfulJsonChange('composer.json', beforeRevision, afterRevision)
    );
    const npmInstall = packageLockChanged || (
        packageJsonChanged
        && hasMeaningfulJsonChange('package.json', beforeRevision, afterRevision)
    );
    const assetChanges = includesPath(files, ['resources/js/', 'resources/css/'])
        || files.includes('vite.config.js')
        || files.includes('vite.config.mjs')
        || npmInstall;

    const messages = [];
    if (composerJsonChanged && !composerInstall) {
        messages.push('Skipping composer install, only the version field changed in composer.json.');
    }
    if (packageJsonChanged && !npmInstall) {
        messages.push('Skipping npm install, only the version field changed in package.json.');
    }

    return {
        composerInstall,
        npmInstall,
        browserslist: assetChanges,
        build: assetChanges,
        migrations: includesPath(files, ['database/migrations/']),
        viewCache: includesPath(files, ['resources/views/']),
        optimize: true,
        queueRestart: true,
        ping: true,
        octaneReload: true,
        messages,
    };
}

/**
 * Return the current branch upstream.
 *
 * @return {{upstream: string, pullArgs: string[]}}
 */
function getUpdateTarget() {
    const currentBranch = capture('git', ['branch', '--show-current']);
    const upstream = capture('git', ['rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{u}'], true);
    if (!upstream) {
        const matchingRemote = `origin/${currentBranch}`;
        if (currentBranch === 'main') {
            return {
                upstream: 'origin/main',
                pullArgs: ['origin', 'main'],
            };
        }

        if (capture('git', ['show-ref', '--verify', `refs/remotes/${matchingRemote}`], true) !== null) {
            return {
                upstream: matchingRemote,
                pullArgs: ['origin', currentBranch],
            };
        }

        console.error(
            `No upstream branch is configured for ${currentBranch}. `
            + 'Configure an upstream branch or switch to a tracked deployment branch before running updates.'
        );
        process.exit(1);
    }

    return {
        upstream,
        pullArgs: [],
    };
}

/**
 * Return ahead/behind counts against upstream.
 *
 * @param {string} upstream Upstream reference
 * @return {{ahead: number, behind: number}}
 */
function getAheadBehind(upstream) {
    const counts = capture('git', ['rev-list', '--left-right', '--count', `HEAD...${upstream}`]);
    const [ahead, behind] = counts.split(/\s+/).map((value) => parseInt(value, 10));

    return {
        ahead,
        behind,
    };
}

/**
 * Return a human-readable action list.
 *
 * @param {ReturnType<typeof determineActions>} actions Planned actions
 * @return {string[]}
 */
function actionList(actions) {
    const list = [];

    if (actions.composerInstall) list.push('Composer install');
    if (actions.npmInstall) list.push('NPM install');
    if (actions.browserslist) list.push('Update Browserslist database');
    if (actions.build) list.push('Build frontend assets');
    if (actions.migrations) list.push('Run database migrations');
    if (actions.viewCache) list.push('Clear and rebuild the view cache');
    if (actions.optimize) list.push('Refresh Laravel optimizations');
    if (actions.queueRestart) list.push('Restart queue workers');
    if (actions.ping) list.push('Ping the instance registry');
    if (actions.octaneReload) list.push('Reload Octane when available');

    return list;
}

section('Fetching updates');
const updateTarget = getUpdateTarget();
const upstream = updateTarget.upstream;
const beforeRevision = capture('git', ['rev-parse', 'HEAD']);
run('git', ['fetch', '--prune', upstream.split('/')[0]]);

const { ahead, behind } = getAheadBehind(upstream);
if (ahead > 0 && behind > 0) {
    console.error(`The current branch has diverged from ${upstream}. Resolve this manually before running updates.`);
    process.exit(1);
}

if (behind === 0) {
    console.log(`No updates available from ${upstream}.`);
    process.exit(0);
}

const pendingFiles = capture('git', ['diff', '--name-only', `HEAD..${upstream}`])
    .split('\n')
    .map((file) => file.trim())
    .filter(Boolean);
const actions = determineActions(pendingFiles, beforeRevision, upstream);

console.log(`Found updates for ${pendingFiles.length} file(s) from ${upstream}.`);

if (isDryRun) {
    section('Dry run');
    console.log('The following files would be updated:');
    pendingFiles.forEach((file) => console.log(` - ${file}`));
    console.log('\nThe following actions would run:');
    actionList(actions).forEach((action) => console.log(` - ${action}`));
    actions.messages.forEach((message) => console.log(` - ${message}`));

    if (hasLocalChanges()) {
        console.log(' - Local changes detected: the updater would create a stash and reset the worktree to HEAD before pulling.');
    }

    process.exit(0);
}

if (hasLocalChanges()) {
    section('Preparing worktree');
    const stashName = `install-updates ${new Date().toISOString()}`;
    console.log('Local changes detected. Stashing tracked and untracked files before pulling.');
    run('git', ['stash', 'push', '--include-untracked', '--message', stashName]);
    run('git', ['reset', '--hard', 'HEAD']);
}

section('Pulling updates');
run('git', ['pull', '--ff-only', ...updateTarget.pullArgs]);

const afterRevision = capture('git', ['rev-parse', 'HEAD']);
const installedActions = determineActions(pendingFiles, beforeRevision, afterRevision);

if (installedActions.composerInstall) {
    section('Composer');
    run('composer', ['install', '--no-interaction']);
} else if (pendingFiles.includes('composer.json')) {
    console.log('Skipping composer install, only the version field changed in composer.json.');
}

if (installedActions.npmInstall) {
    section('NPM packages');
    run('npm', ['install']);
} else if (pendingFiles.includes('package.json')) {
    console.log('Skipping npm install, only the version field changed in package.json.');
}

if (installedActions.browserslist) {
    section('Browserslist');
    run('npx', ['--yes', 'update-browserslist-db@latest']);
    run('npm', ['install', '--save-dev', 'baseline-browser-mapping@latest']);
}

if (installedActions.build) {
    section('Frontend build');
    run('npm', ['run', 'build']);
}

if (installedActions.migrations) {
    section('Database migrations');
    run('php', ['artisan', 'migrate', '--force']);
}

if (installedActions.viewCache) {
    section('View cache');
    run('php', ['artisan', 'view:clear']);
    run('php', ['artisan', 'view:cache']);
}

section('Optimizations');
run('php', ['artisan', 'optimize']);

section('Queue workers');
run('php', ['artisan', 'queue:restart']);

section('Instance registry');
run('php', ['artisan', 'ping']);

const artisanCommands = capture('php', ['artisan', 'list', '--raw'], true) || '';
if (artisanCommands.split('\n').includes('octane:reload')) {
    section('Octane');
    run('php', ['artisan', 'octane:reload']);
}

console.log('\nDone installing updates.');
