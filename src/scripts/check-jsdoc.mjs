import fs from 'node:fs'
import path from 'node:path'
import { changedFiles } from './changed-files.mjs'

/**
 * Checks that changed JavaScript declarations have a nearby JSDoc block.
 *
 * @returns {number} Process exit status.
 */
function main() {
    const candidates = changedFiles().filter((file) =>
        /^(resources\/js|scripts)\/.*\.(js|mjs|vue)$/.test(file),
    )
    const violations = []
    const declaration =
        /(?:^|\n)\s*(?:export\s+)?(?:async\s+)?function\s+\w+|(?:^|\n)\s*(?:const|let)\s+\w+\s*=\s*(?:async\s*)?\(/g

    for (const relativePath of candidates) {
        const filePath = path.resolve(relativePath)
        if (!fs.existsSync(filePath)) continue
        const source = fs.readFileSync(filePath, 'utf8')
        for (const match of source.matchAll(declaration)) {
            const before = source.slice(0, match.index)
            const line = before.split('\n').length
            const previous = before.trimEnd().split('\n').at(-1) ?? ''
            if (!previous.endsWith('*/')) {
                violations.push(`${relativePath}:${line}`)
            }
        }
    }

    if (violations.length > 0) {
        console.error('Fehlende JSDoc-Blöcke:')
        console.error(violations.join('\n'))
        return 1
    }
    console.log(`JSDoc-Prüfung bestanden (${candidates.length} geänderte Datei(en)).`)
    return 0
}

process.exit(main())
