import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import TokensPage from '@/Pages/tokens.vue'

/**
 * Mount the token management Inertia page with default props.
 *
 * @param {Record<string, unknown>} props - Component prop overrides.
 * @returns {import('@vue/test-utils').VueWrapper} Mounted wrapper.
 */
const mountTokensPage = (props = {}) =>
    mount(TokensPage, {
        props: {
            tokens: [],
            token: null,
            canCreateChurchTokens: false,
            churchTokenCities: [],
            ...props,
        },
    })

describe('TokensPage', () => {
    it('hides the church selector without permission and creates a personal token', async () => {
        const wrapper = mountTokensPage()

        expect(wrapper.find('#newTokenCitySelect').exists()).toBe(false)

        await wrapper.find('input[name="newTokenTitle"]').setValue('Mein Skript')
        await wrapper.find('button.btn-success').trigger('click')

        expect(wrapper.vm.$inertia.post).toHaveBeenCalledWith('/tokens.create', {
            title: 'Mein Skript',
            city_id: null,
        })
    })

    it('renders church scope options when permitted and posts the selected city_id', async () => {
        const wrapper = mountTokensPage({
            canCreateChurchTokens: true,
            churchTokenCities: [
                { id: 8, name: 'Motzingen' },
                { id: 9, name: 'Baisingen' },
            ],
            tokens: [
                {
                    id: 42,
                    name: 'Website Sync',
                    created_at: '2026-11-25T12:00:00Z',
                    scope_type: 'church_extranet',
                    scope_label: 'Kirchengemeinde: Motzingen',
                },
            ],
        })

        expect(wrapper.text()).toContain('Kirchengemeinde: Motzingen')

        const citySelect = wrapper.find('#newTokenCitySelect')
        expect(citySelect.exists()).toBe(true)

        await wrapper.find('input[name="newTokenTitle"]').setValue('Motzingen Extranet')
        await citySelect.setValue('8')
        await wrapper.find('button.btn-success').trigger('click')

        expect(wrapper.vm.$inertia.post).toHaveBeenCalledWith('/tokens.create', {
            title: 'Motzingen Extranet',
            city_id: 8,
        })
    })
})
