import { mount } from '@vue/test-utils'
import FakeTable from '@/components/Ui/FakeTable.vue'

describe('FakeTable', () => {
    const columns = [3, 3, 6]
    const headers = ['Name', 'Datum', 'Beschreibung']

    it('has .fake-table root class', () => {
        const w = mount(FakeTable, { props: { columns, headers } })
        expect(w.classes()).toContain('fake-table')
    })
    it('renders all headers', () => {
        const w = mount(FakeTable, { props: { columns, headers } })
        expect(w.text()).toContain('Name')
        expect(w.text()).toContain('Datum')
        expect(w.text()).toContain('Beschreibung')
    })
    it('applies column width classes', () => {
        const w = mount(FakeTable, { props: { columns, headers } })
        const headerDivs = w.findAll('.fake-table-head .row > div')
        expect(headerDivs[0].classes()).toContain('col-md-3')
        expect(headerDivs[2].classes()).toContain('col-md-6')
    })
    it('shows collapsedHeader for mobile', () => {
        const w = mount(FakeTable, { props: { columns, headers, collapsedHeader: 'Eintrag' } })
        const mobileHead = w.find('.d-block.d-md-none')
        expect(mobileHead.text()).toBe('Eintrag')
    })
    it('renders slot content in .fake-table-body', () => {
        const w = mount(FakeTable, {
            props: { columns, headers },
            slots: { default: '<div class="row-item">Row 1</div>' },
        })
        expect(w.find('.fake-table-body .row-item').text()).toBe('Row 1')
    })
})
