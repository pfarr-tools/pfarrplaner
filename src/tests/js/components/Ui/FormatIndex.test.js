import { mount } from '@vue/test-utils'
import FormatIndex from '@/components/Ui/FormatIndex.vue'

const groups = [
    {
        title: 'Gottesdienste',
        items: [
            {
                title: 'Jahresplan',
                description: 'Gottesdienste als Übersicht',
                icon: 'mdi mdi-calendar',
                href: '/reports.setup/ServiceTable',
                inertia: true,
            },
            {
                title: 'Excel-Tabelle',
                description: 'Export für Tabellenprogramme',
                icon: 'mdi mdi-file-excel',
                href: '/reports.setup/ServiceExcelTable',
            },
        ],
    },
]

/**
 * Mounts the format index with representative grouped data.
 *
 * @param {Object} props Additional component props.
 * @returns {import('@vue/test-utils').VueWrapper} The mounted component.
 */
function mountIndex(props = {}) {
    return mount(FormatIndex, {
        props: {
            groups,
            ...props,
        },
    })
}

describe('FormatIndex', () => {
    it('renders grouped compact rows with accessible links', () => {
        const wrapper = mountIndex()

        expect(wrapper.find('h1').exists()).toBe(false)
        expect(wrapper.get('.format-index__search')).toBeTruthy()
        expect(wrapper.get('[data-format-group]').text()).toContain('Gottesdienste')
        expect(wrapper.findAll('[data-format-row]')).toHaveLength(2)
        expect(wrapper.get('[data-format-row] a').attributes('href')).toBe(
            '/reports.setup/ServiceTable',
        )
    })

    it('filters rows by title and description', async () => {
        const wrapper = mountIndex()
        const search = wrapper.get('input[type="search"]')

        await search.setValue('Tabellenprogramme')

        expect(wrapper.findAll('[data-format-row]')).toHaveLength(1)
        expect(wrapper.get('[data-format-row]').text()).toContain('Excel-Tabelle')
    })

    it('shows a useful empty state when no row matches the search', async () => {
        const wrapper = mountIndex()

        await wrapper.get('input[type="search"]').setValue('nicht vorhanden')

        expect(wrapper.get('[data-format-empty]').text()).toContain('Keine passenden Formate')
    })

    it('explains when the supplied list is empty before searching', () => {
        const wrapper = mountIndex({ groups: [{ title: 'Assistenten', items: [] }] })

        expect(wrapper.get('[data-format-empty]').text()).toContain('Keine Formate verfügbar')
    })

    it('emits an in-app selection without following the link', async () => {
        const wrapper = mountIndex()
        const link = wrapper.get('[data-format-row] a')
        const event = new window.MouseEvent('click', { bubbles: true, cancelable: true })

        link.element.dispatchEvent(event)
        await wrapper.vm.$nextTick()

        expect(event.defaultPrevented).toBe(true)
        expect(wrapper.emitted('select')[0][0]).toEqual(groups[0].items[0])
    })
})
