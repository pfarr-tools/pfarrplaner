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

const { execFileSync, execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const packagePath = path.resolve(__dirname, '..', 'package.json');
const repositoryRoot = execSync('git rev-parse --show-toplevel', { encoding: 'utf8' }).trim();
const pkg = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
const standardVersionConfig = pkg['standard-version'] || {};
const tagPrefix = standardVersionConfig.tagPrefix || 'v';

// Parse CLI args: positional "major|minor|patch" or --release-as <type>
const args = process.argv.slice(2);
let forcedType = null;
for (let i = 0; i < args.length; i++) {
    if (args[i] === '--release-as' && args[i + 1]) {
        forcedType = args[++i];
        break;
    }
    if (['major', 'minor', 'patch'].includes(args[i])) {
        forcedType = args[i];
        break;
    }
}

const currentYear = new Date().getFullYear();
const versionMajor = parseInt(pkg.version.split('.')[0], 10);

let releaseType;

/**
 * Stage the given paths if they exist in the working tree.
 *
 * @param {string[]} paths Paths to stage
 * @return {void}
 */
function gitAddExisting(paths) {
    const existingPaths = paths.filter((target) => fs.existsSync(path.resolve(repositoryRoot, target)));

    if (existingPaths.length === 0) {
        return;
    }

    execSync(`git -C "${repositoryRoot}" add -- ${existingPaths.join(' ')}`, { stdio: 'inherit' });
}

function manualNeedsDusk() {
    let lastTag = '';
    try {
        lastTag = execFileSync('git', ['describe', '--tags', '--abbrev=0'], {
            cwd: repositoryRoot,
            encoding: 'utf8',
        }).trim();
    } catch {
        return true;
    }

    const changedFiles = execFileSync('git', [
        'diff',
        '--name-only',
        `${lastTag}..HEAD`,
        '--',
        'src/resources',
        'src/public',
    ], {
        cwd: repositoryRoot,
        encoding: 'utf8',
    });

    return changedFiles.trim().length > 0;
}

if (forcedType) {
    releaseType = forcedType;
    console.log(`Forced release type: ${releaseType}`);
} else if (currentYear !== versionMajor) {
    releaseType = 'major';
    console.log(`Determined release type: ${releaseType} (new year)`);
} else {
    // Find the latest git tag to scope the commit search
    let lastTag = '';
    try {
        lastTag = execSync('git describe --tags --abbrev=0', { encoding: 'utf8' }).trim();
    } catch {
        // No tags yet — scan all commits
    }

    const range = lastTag ? `${lastTag}..HEAD` : 'HEAD';
    let log = '';
    try {
        log = execSync(`git log ${range} --oneline`, { encoding: 'utf8' });
    } catch {
        // Empty range is fine
    }

    const hasFeature = log.split('\n').some(line => /^\w+ feat(\(.+?\))?:/.test(line));
    releaseType = hasFeature ? 'minor' : 'patch';
    console.log(`Determined release type: ${releaseType}`);
}

try {
    execSync(`npx standard-version --release-as ${releaseType} --skip.commit --skip.tag`, { stdio: 'inherit' });
} catch (err) {
    process.exit(err.status ?? 1);
}

const newPkg = JSON.parse(fs.readFileSync(packagePath, 'utf8'));
const releaseMessage = `chore(release): ${newPkg.version}`;

if (releaseType === 'major' || releaseType === 'minor') {
    console.log('Rebuilding and deploying manual...');
    try {
        const planer = path.resolve(repositoryRoot, 'planer');
        execFileSync(planer, ['dev', 'down', '--remove-orphans'], {
            cwd: repositoryRoot,
            stdio: 'inherit',
        });
        execFileSync(planer, ['dev', 'up', '-d', '--remove-orphans'], {
            cwd: repositoryRoot,
            stdio: 'inherit',
        });
        const manualArgs = ['dev', 'manual', 'all'];
        if (!manualNeedsDusk()) {
            manualArgs.push('--skip-dusk');
            console.log('Keine Änderungen unter src/resources/ oder src/public/ seit dem letzten Release; überspringe Dusk-Screenshot-Tests.');
        }
        execFileSync(planer, manualArgs, {
            cwd: repositoryRoot,
            stdio: 'inherit',
        });
        execSync('npm run manual:deploy', { stdio: 'inherit' });
        gitAddExisting([
            'docs/manual/benutzerhandbuch/versionsangaben.md',
            'docs/manual/benutzerhandbuch/lizenzen.md',
            'docs/manual/media/images',
            'docs/manual/media/site',
        ]);
    } catch (err) {
        console.error('Manual build/deploy failed.');
        process.exit(err.status ?? 1);
    }
}

try {
    gitAddExisting([
        'src/CHANGELOG.md',
        'src/package.json',
        'src/package-lock.json',
        'src/npm-shrinkwrap.json',
    ]);
    execSync(`git commit -m "${releaseMessage}"`, { stdio: 'inherit' });
    execSync(`git tag -a ${tagPrefix}${newPkg.version} -m "${releaseMessage}"`, { stdio: 'inherit' });
} catch (err) {
    process.exit(err.status ?? 1);
}
