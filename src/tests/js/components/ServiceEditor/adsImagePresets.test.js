import { describe, expect, it } from 'vitest'
import { createAdsImagePresets } from '@/components/ServiceEditor/adsImagePresets.js'

describe('ads image presets', () => {
    it('turns configured image cuts into target presets', () => {
        const presets = createAdsImagePresets({
            Newsletter: [1200, 630],
            Quadrat: [800, 800],
        }, value => value.toLowerCase())

        expect(presets).toEqual([
            { title: 'Newsletter', width: 1200, height: 630, aspectRatio: 1200 / 630, cut: 'newsletter' },
            { title: 'Quadrat', width: 800, height: 800, aspectRatio: 1, cut: 'quadrat' },
        ])
    })
})
