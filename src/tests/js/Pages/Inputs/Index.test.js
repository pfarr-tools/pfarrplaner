import { mount } from '@vue/test-utils'
import InputsIndex from '@/Pages/Inputs/Index.vue'

const inputs = [
    { id: 'planning', name: 'Planungstabelle', description: 'Dienste planen' },
    { id: 'offerings', name: 'Kollekten', description: 'Kollekten erfassen' },
]

const FormatIndexStub = {
    template:
        '<button data-format-index @click="$emit(\'select\', groups[0].items[0])">Auswählen</button>',
    props: ['groups', 'searchPlaceholder', 'emptyTitle', 'emptyDescription'],
}

describe('Inputs Index', () => {
    it('maps inputs to the shared format index and keeps the setup route', async () => {
        const wrapper = mount(InputsIndex, {
            props: { inputs },
            global: {
                stubs: {
                    AdminLayout: { template: '<div><slot /></div>' },
                    FormatIndex: FormatIndexStub,
                },
            },
        })
        const formatIndex = wrapper.getComponent(FormatIndexStub)

        expect(formatIndex.props('emptyTitle')).toBe('Keine Assistenten verfügbar')
        expect(formatIndex.props('emptyDescription')).toContain('freigeschalteten Assistenten')
        expect(formatIndex.props('groups')).toEqual([
            {
                title: 'Assistenten',
                items: [
                    {
                        key: 'planning',
                        title: 'Planungstabelle',
                        description: 'Dienste planen',
                        icon: 'mdi mdi-keyboard',
                        href: '/inputs.setup',
                        inertia: true,
                    },
                    {
                        key: 'offerings',
                        title: 'Kollekten',
                        description: 'Kollekten erfassen',
                        icon: 'mdi mdi-keyboard',
                        href: '/inputs.setup',
                        inertia: true,
                    },
                ],
            },
        ])

        await formatIndex.get('[data-format-index]').trigger('click')

        expect(window.route).toHaveBeenCalledWith('inputs.setup', 'planning')
        expect(wrapper.vm.$inertia.get).toHaveBeenCalledWith('/inputs.setup')
    })

    it('normalizes object-shaped Inertia props without hiding assistants', () => {
        const wrapper = mount(InputsIndex, {
            props: { inputs: { 0: inputs[0], 1: inputs[1] } },
            global: {
                stubs: {
                    AdminLayout: { template: '<div><slot /></div>' },
                    FormatIndex: FormatIndexStub,
                },
            },
        })

        expect(wrapper.getComponent(FormatIndexStub).props('groups')[0].items).toHaveLength(2)
    })
})
