import { execFileSync } from 'node:child_process'

/**
 * Returns repository files changed against the configured quality base.
 *
 * @returns {string[]} Changed paths relative to the application root.
 */
export function changedFiles() {
    if (process.env.QUALITY_FILES !== undefined) {
        return process.env.QUALITY_FILES.split('\n').filter(Boolean)
    }

    const base = process.env.QUALITY_BASE_REF ?? 'origin/main'
    let output = ''
    try {
        output = execFileSync('git', ['diff', '--name-only', `${base}...HEAD`], {
            encoding: 'utf8',
        })
    } catch {
        try {
            output = execFileSync('git', ['diff', '--name-only', 'HEAD~1'], { encoding: 'utf8' })
        } catch {
            return []
        }
    }

    let workingTree = ''
    try {
        workingTree = execFileSync('git', ['diff', '--name-only', 'HEAD'], { encoding: 'utf8' })
    } catch {
        // The lightweight Vite image intentionally does not contain Git.
    }
    return [...new Set(`${output}\n${workingTree}`.split('\n').filter(Boolean))].map((file) =>
        file.replace(/^src\//, ''),
    )
}

if (import.meta.url === `file://${process.argv[1]}`) {
    process.stdout.write(changedFiles().join('\n'))
}
