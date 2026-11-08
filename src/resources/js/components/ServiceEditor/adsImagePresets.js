/**
 * Convert configured advertising image cuts into uploader target presets.
 *
 * @param {Object} cuts Configured cut names mapped to [width, height].
 * @param {Function} slugify Function converting a display name to its cut key.
 * @returns {Object[]} Target presets for the shared uploader.
 */
export function createAdsImagePresets(cuts, slugify) {
    return Object.entries(cuts || {}).map(([title, dimensions]) => {
        const [width, height] = dimensions
        return {
            title,
            width,
            height,
            aspectRatio: width / height,
            cut: slugify(title),
        }
    })
}
