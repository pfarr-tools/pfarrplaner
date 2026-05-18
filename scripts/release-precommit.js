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

const releaseType = process.env.PFARRPLANER_RELEASE_TYPE;

if (!['major', 'minor'].includes(releaseType)) {
    console.log(`Skipping manual rebuild for release type: ${releaseType || 'unknown'}`);
    process.exit(0);
}

console.log(`Rebuilding and deploying manual for ${releaseType} release...`);

try {
    execSync('npm run manual:all:server', { stdio: 'inherit' });
    execSync('npm run manual:deploy', { stdio: 'inherit' });
    execSync('git add manual/versionsangaben.md manual/lizenzen.md manual/media/images manual/media/site', { stdio: 'inherit' });
} catch (err) {
    console.error('Manual build/deploy failed.');
    process.exit(err.status ?? 1);
}
