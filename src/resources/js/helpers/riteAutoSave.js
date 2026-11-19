const STORAGE_KEY = 'pfarrplaner'

/**
 * Reads the shared browser storage object without failing on stale JSON.
 * @returns {Object} Stored Pfarrplaner browser data.
 */
function readStorage() {
    try {
        return JSON.parse(window.localStorage.getItem(STORAGE_KEY)) || {}
    } catch {
        return {}
    }
}

/**
 * Returns an unsynchronized draft for a rite.
 * @param {string} rite Rite type.
 * @param {number|string} id Rite identifier.
 * @returns {Object|null} Draft record or null.
 */
export function getRiteDraft(rite, id) {
    return readStorage().autosaves?.[rite]?.[id] || null
}

/**
 * Stores a local draft before attempting a server synchronization.
 * @param {string} rite Rite type.
 * @param {number|string} id Rite identifier.
 * @param {Object} data Rite form data.
 * @returns {string} Serialized snapshot used to avoid clearing newer edits.
 */
export function saveRiteDraft(rite, id, data) {
    const storage = readStorage()
    storage.autosaves = storage.autosaves || {}
    storage.autosaves[rite] = storage.autosaves[rite] || {}
    storage.autosaves[rite][id] = { data, updatedAt: new Date().toISOString() }
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(storage))

    return JSON.stringify(data)
}

/**
 * Deletes a draft only if it still matches the server-saved snapshot.
 * @param {string} rite Rite type.
 * @param {number|string} id Rite identifier.
 * @param {string} snapshot Serialized saved form data.
 * @returns {boolean} Whether the matching draft was deleted.
 */
export function clearRiteDraft(rite, id, snapshot) {
    const storage = readStorage()
    const draft = storage.autosaves?.[rite]?.[id]
    if (!draft || JSON.stringify(draft.data) !== snapshot) return false

    delete storage.autosaves[rite][id]
    if (Object.keys(storage.autosaves[rite]).length === 0) delete storage.autosaves[rite]
    if (Object.keys(storage.autosaves).length === 0) delete storage.autosaves
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(storage))

    return true
}
