import { mount } from '@vue/test-utils'
import TeamIndex from '@/Pages/Teams/Index.vue'

describe('Team index page', () => {
    it('passes teams to the shared admin index page', () => {
        const wrapper = mount(TeamIndex, {
            props: {
                teams: [{ id: 1, label: 'Küsterteam', city: { name: 'Musterstadt' }, users: [] }],
                canCreate: true,
            },
            global: {
                stubs: {
                    'model-index-page': {
                        props: ['title', 'records', 'canCreate', 'createLabel', 'createRouteName', 'modelLabel'],
                        template: '<div><span class="title">{{ title }}</span><span class="model-label">{{ modelLabel }}</span><span class="records">{{ records.length }}</span><span class="can-create">{{ canCreate }}</span><slot name="table-row-additional-columns" :row="records[0]" /></div>',
                    },
                },
            },
        })

        expect(wrapper.find('.title').text()).toBe('Teams')
        expect(wrapper.find('.model-label').text()).toBe('Team')
        expect(wrapper.find('.records').text()).toBe('1')
        expect(wrapper.find('.can-create').text()).toBe('true')
        expect(wrapper.text()).toContain('Musterstadt')
    })
})
