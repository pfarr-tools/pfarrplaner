import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import DatasetInfo from '@/components/Ui/dataset/DatasetInfo.vue'
import DatasetPager from '@/components/Ui/dataset/DatasetPager.vue'

// Both components use inject() from vue-dataset — provide refs via global.provide

describe('DatasetInfo', () => {
    const mountInfo = (overrides = {}) => mount(DatasetInfo, {
        global: {
            provide: {
                datasetI18n: ref({}),
                dsResultsNumber: ref(100),
                dsFrom: ref(0),
                dsTo: ref(10),
                ...overrides,
            },
        },
    })

    it('shows number of results', () => {
        expect(mountInfo().text()).toContain('100')
    })
    it('shows "from" as first+1 when results > 0', () => {
        // dsFrom=0 → showing = 0+1 = 1
        expect(mountInfo().text()).toContain('1')
    })
    it('shows 0 as "from" when no results', () => {
        const w = mountInfo({ dsResultsNumber: ref(0) })
        expect(w.text()).toContain('0')
    })
    it('shows total as "to" when dsTo >= total', () => {
        // dsTo=10, total=10 → showingTo = 10
        const w = mountInfo({ dsResultsNumber: ref(10), dsTo: ref(10) })
        expect(w.text()).toContain('10')
    })
    it('shows dsTo as "to" when not at last page', () => {
        // dsTo=10, total=100 → showingTo = 10
        expect(mountInfo({ dsTo: ref(10) }).text()).toContain('10')
    })
})

describe('DatasetPager', () => {
    const setActive = vi.fn()
    const mountPager = (overrides = {}) => mount(DatasetPager, {
        global: {
            provide: {
                datasetI18n: ref({}),
                setActive,
                dsPages: ref([1, 2, 3]),
                dsPagecount: ref(3),
                dsPage: ref(1),
                ...overrides,
            },
        },
    })

    it('renders a pagination list', () => {
        expect(mountPager().find('ul.pagination').exists()).toBe(true)
    })
    it('renders a page item for each page', () => {
        const items = mountPager().findAll('li.page-item')
        // 3 pages + prev + next = 5
        expect(items.length).toBe(5)
    })
    it('marks current page as active', () => {
        const w = mountPager()
        const pageItems = w.findAll('li.page-item')
        // First li = previous, second = page 1 (active)
        expect(pageItems[1].classes()).toContain('active')
    })
    it('disables previous button on first page', () => {
        const items = mountPager().findAll('li.page-item')
        expect(items[0].classes()).toContain('disabled')
    })
    it('disables next button on last page', () => {
        const w = mountPager({ dsPage: ref(3) })
        const items = w.findAll('li.page-item')
        expect(items[items.length - 1].classes()).toContain('disabled')
    })
    it('calls setActive when a page link is clicked', async () => {
        const w = mountPager()
        // Click on page 2 (index 2 in page items, index 1 in page links)
        const pageLinks = w.findAll('li.page-item a')
        await pageLinks[1].trigger('click')
        expect(setActive).toHaveBeenCalled()
    })
})
