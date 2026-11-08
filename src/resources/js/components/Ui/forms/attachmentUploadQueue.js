let itemSequence = 0

/**
 * Create a stable identifier for a local upload item.
 *
 * @returns {string} A client-side-only identifier.
 */
function nextId() {
    itemSequence += 1
    return `upload-${Date.now()}-${itemSequence}`
}

/**
 * Derive a readable title from a file name.
 *
 * @param {string} name The original file name.
 * @returns {string} The file name without its extension.
 */
function defaultTitle(name) {
    const title = name.replace(/\.[^.]+$/, '')
    return title.charAt(0).toUpperCase() + title.slice(1)
}

/**
 * Create a local upload queue item.
 *
 * @param {File} file The local file.
 * @param {Object} metadata Optional queue metadata.
 * @returns {Object} A ready upload item.
 */
export function createUploadItem(file, metadata = {}) {
    const sourceId = metadata.sourceId || nextId()

    return {
        id: metadata.id || nextId(),
        sourceId,
        sourceFile: metadata.sourceFile || file,
        file,
        title: metadata.title ?? defaultTitle(file.name),
        previewUrl: metadata.previewUrl || null,
        status: metadata.status || 'ready',
        progress: metadata.progress || 0,
        error: metadata.error || null,
        cut: metadata.cut || null,
        preset: metadata.preset || null,
        transform: metadata.transform || null,
    }
}

/**
 * Duplicate a queue item as an independent variant of the same source.
 *
 * @param {Object} item The source queue item.
 * @param {Object} metadata Variant overrides.
 * @returns {Object} A new ready queue item.
 */
export function duplicateUploadItem(item, metadata = {}) {
    return createUploadItem(item.sourceFile || item.file, {
        ...item,
        ...metadata,
        id: undefined,
        sourceId: item.sourceId,
        sourceFile: item.sourceFile || item.file,
        file: metadata.file || item.sourceFile || item.file,
        status: 'ready',
        progress: 0,
        error: null,
    })
}

/**
 * Append files to a queue without mutating the existing queue.
 *
 * @param {Object[]} queue Existing queue items.
 * @param {File[]|FileList} files Files selected or dropped by the user.
 * @returns {Object[]} The updated queue.
 */
export function appendFiles(queue, files) {
    return [...queue, ...Array.from(files || [], file => createUploadItem(file))]
}
