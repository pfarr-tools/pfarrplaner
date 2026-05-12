import { mount } from '@vue/test-utils'
import FormSkipLabelsInput from '@/components/Ui/forms/FormSkipLabelsInput.vue'

describe('FormSkipLabelsInput', () => {
    it('renders N label divs when labels=N', () => {
        const w = mount(FormSkipLabelsInput, { props: { labels: 6, length: 3, modelValue: 0 } })
        expect(w.findAll('.label')).toHaveLength(6)
    })
    it('div at myValue index has "active" class', () => {
        const w = mount(FormSkipLabelsInput, { props: { labels: 6, length: 2, modelValue: 0 } })
        expect(w.findAll('.label')[0].classes()).toContain('active')
    })
    it('divs before myValue have "skipped" class', () => {
        const w = mount(FormSkipLabelsInput, { props: { labels: 6, length: 2, modelValue: 2 } })
        expect(w.findAll('.label')[0].classes()).toContain('skipped')
        expect(w.findAll('.label')[1].classes()).toContain('skipped')
    })
    it('divs after active range have "empty" class', () => {
        const w = mount(FormSkipLabelsInput, { props: { labels: 6, length: 2, modelValue: 0 } })
        expect(w.findAll('.label')[2].classes()).toContain('empty')
    })
    it('click on a label calls setValue and emits update:modelValue', async () => {
        const w = mount(FormSkipLabelsInput, { props: { labels: 6, length: 2, modelValue: 0 } })
        await w.findAll('.label')[3].trigger('click')
        expect(w.emitted('update:modelValue')?.[0]).toEqual([3])
        expect(w.emitted('input')?.[0]).toEqual([3])
    })
    it('shows print text span for active labels', () => {
        const w = mount(FormSkipLabelsInput, { props: { labels: 3, length: 2, modelValue: 0 } })
        expect(w.findAll('.label span').some(s => s.text().includes('bedruckt'))).toBe(true)
    })
})
