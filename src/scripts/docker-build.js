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

const { execSync } = require('child_process');

const dockerRepo = 'pfarrtools/pfarrplaner';

const args = process.argv.slice(2);
let version = null;
let push = false;

for (const arg of args) {
    if (arg === '--push') {
        push = true;
    } else if (!arg.startsWith('--')) {
        version = arg;
    }
}

const tags = [`${dockerRepo}:latest`];
if (version) {
    tags.unshift(`${dockerRepo}:${version}`);
}

const tagFlags = tags.map(t => `-t ${t}`).join(' ');
console.log(`Building Docker image(s): ${tags.join(', ')}...`);

try {
    execSync(`docker build ${tagFlags} .`, { stdio: 'inherit' });
} catch (err) {
    console.error('Docker build failed.');
    process.exit(err.status ?? 1);
}

if (push) {
    for (const tag of tags) {
        console.log(`Pushing ${tag}...`);
        try {
            execSync(`docker push ${tag}`, { stdio: 'inherit' });
        } catch (err) {
            console.error(`Docker push failed for ${tag}.`);
            process.exit(err.status ?? 1);
        }
    }
}