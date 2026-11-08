/**
 * Upload a file in resumable chunks.
 *
 * @param {File} file The file to upload.
 * @param {Object} options Upload callbacks and chunk size.
 * @returns {Promise<Object>} The completed upload response.
 */
export async function uploadFileInChunks(file, options) {
    const chunkSize = options.chunkSize
    const totalChunks = Math.ceil(file.size / chunkSize)
    const started = options.session
        ? await options.status(options.session)
        : await options.start({
        filename: file.name,
        mime_type: file.type,
        size: file.size,
        total_chunks: totalChunks,
        })
    const session = started.session
    options.onSession?.(session)
    const received = new Set(started.received || [])
    let completed = received.size
    options.onProgress?.(Math.round(completed / totalChunks * 100))

    for (let index = 0; index < totalChunks; index++) {
        if (received.has(index)) continue
        const blob = file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize))
        let attempt = 0
        while (true) {
            try {
                const checksum = options.checksum ? await options.checksum(blob) : null
                await options.sendChunk(session, index, blob, checksum)
                break
            } catch (error) {
                attempt++
                if (attempt >= 3) throw error
            }
        }
        completed++
        received.add(index)
        options.onChunk?.(index, [...received])
        options.onProgress?.(Math.round(completed / totalChunks * 100))
    }

    return options.complete(session)
}
