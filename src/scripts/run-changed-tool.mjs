import { spawnSync } from 'node:child_process'
import { changedFiles } from './changed-files.mjs'

/**
 * Runs a formatter or linter only for files changed in the current quality scope.
 *
 * @returns {number} Process exit status.
 */
function main() {
    const [command, ...arguments_] = process.argv.slice(2)
    const files = changedFiles().filter((file) =>
        /\.(css|js|json|mjs|sass|scss|vue|yaml|yml)$/.test(file),
    )
    if (files.length === 0) {
        console.log(`Keine geänderten ${command}-Dateien zu prüfen.`)
        return 0
    }
    const result = spawnSync(command, [...arguments_, ...files], { stdio: 'inherit' })
    return result.status ?? 1
}

process.exit(main())
