import { mount } from '@vue/test-utils'
import FormSelectize from '@/components/Ui/forms/FormSelectize.vue'

const MultiselectStub = {
    template: '<div class="multiselect-stub" />',
    props: ['modelValue', 'options', 'valueProp', 'label', 'trackBy', 'mode', 'placeholder',
            'disabled', 'searchable', 'name', 'groups', 'groupLabel', 'groupOptions'],
    emits: ['change'],
}

const stubs = { Multiselect: MultiselectStub }

describe('FormSelectize', () => {
    it('resolvedOptions uses options prop when non-empty', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', options: [{ id: 1, name: 'A' }] },
            global: { stubs },
        })
        expect(w.vm.resolvedOptions).toEqual([{ id: 1, name: 'A' }])
    })
    it('resolvedOptions falls back to items prop', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', options: [], items: [{ id: 2, name: 'B' }] },
            global: { stubs },
        })
        expect(w.vm.resolvedOptions).toEqual([{ id: 2, name: 'B' }])
    })
    it('resolvedOptions falls back to settings.options', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', options: [], settings: { options: [{ id: 3, name: 'C' }] } },
            global: { stubs },
        })
        expect(w.vm.resolvedOptions).toEqual([{ id: 3, name: 'C' }])
    })
    it('resolvedOptions returns empty array when nothing provided', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test' },
            global: { stubs },
        })
        expect(w.vm.resolvedOptions).toEqual([])
    })
    it('resolvedIdKey uses idKey prop', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', idKey: 'slug' },
            global: { stubs },
        })
        expect(w.vm.resolvedIdKey).toBe('slug')
    })
    it('resolvedIdKey uses settings.valueField over idKey', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', idKey: 'id', settings: { valueField: 'uuid' } },
            global: { stubs },
        })
        expect(w.vm.resolvedIdKey).toBe('uuid')
    })
    it('resolvedTitleKey uses titleKey prop', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', titleKey: 'label' },
            global: { stubs },
        })
        expect(w.vm.resolvedTitleKey).toBe('label')
    })
    it('resolvedTitleKey uses settings.labelField over titleKey', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', titleKey: 'name', settings: { labelField: 'title' } },
            global: { stubs },
        })
        expect(w.vm.resolvedTitleKey).toBe('title')
    })
    it('changed() emits input and update:modelValue', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test' },
            global: { stubs },
        })
        w.vm.changed('value-a')
        expect(w.emitted('input')?.[0]).toEqual(['value-a'])
        expect(w.emitted('update:modelValue')?.[0]).toEqual(['value-a'])
    })
})

describe('FormSelectize — grouping', () => {
    const services = [
        { id: 1, name: 'GD 1', category: 'Taufgottesdienste' },
        { id: 2, name: 'GD 2', category: 'Taufgottesdienste' },
        { id: 3, name: 'GD 3', category: 'Andere Gottesdienste' },
    ]
    const groupSettings = {
        optgroupField: 'category',
        optgroupLabelField: 'groupName',
        optgroupValueField: 'groupName',
        optgroups: [{ groupName: 'Taufgottesdienste' }, { groupName: 'Andere Gottesdienste' }],
    }

    it('isGrouped is false without optgroupField', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', options: services },
            global: { stubs },
        })
        expect(w.vm.isGrouped).toBe(false)
    })

    it('isGrouped is true when settings.optgroupField is set', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', options: services, settings: groupSettings },
            global: { stubs },
        })
        expect(w.vm.isGrouped).toBe(true)
    })

    it('resolvedOptions groups items by optgroupField into _groupLabel/_groupOptions', () => {
        const w = mount(FormSelectize, {
            props: { name: 'test', options: services, settings: groupSettings },
            global: { stubs },
        })
        const opts = w.vm.resolvedOptions
        expect(opts).toHaveLength(2)
        expect(opts[0]._groupLabel).toBe('Taufgottesdienste')
        expect(opts[0]._groupOptions).toEqual([services[0], services[1]])
        expect(opts[1]._groupLabel).toBe('Andere Gottesdienste')
        expect(opts[1]._groupOptions).toEqual([services[2]])
    })

    it('resolvedOptions produces an empty _groupOptions array for a group with no matching items', () => {
        const w = mount(FormSelectize, {
            props: {
                name: 'test',
                options: [{ id: 1, name: 'X', category: 'Taufgottesdienste' }],
                settings: groupSettings,
            },
            global: { stubs },
        })
        const opts = w.vm.resolvedOptions
        expect(opts[1]._groupLabel).toBe('Andere Gottesdienste')
        expect(opts[1]._groupOptions).toEqual([])
    })

    it('allowEmptyOption prepends empty entry in flat mode', () => {
        const w = mount(FormSelectize, {
            props: {
                name: 'test',
                options: [{ id: 1, name: 'A' }],
                settings: { allowEmptyOption: true, emptyOptionLabel: 'Keine Auswahl' },
            },
            global: { stubs },
        })
        const opts = w.vm.resolvedOptions
        expect(opts[0]).toEqual({ id: null, name: 'Keine Auswahl' })
        expect(opts).toHaveLength(2)
    })

    it('allowEmptyOption prepends empty group in grouped mode', () => {
        const w = mount(FormSelectize, {
            props: {
                name: 'test',
                options: services,
                settings: { ...groupSettings, allowEmptyOption: true, emptyOptionLabel: 'noch nicht gewählt' },
            },
            global: { stubs },
        })
        const opts = w.vm.resolvedOptions
        expect(opts[0]._groupLabel).toBe('')
        expect(opts[0]._groupOptions[0]).toEqual({ id: null, name: 'noch nicht gewählt' })
        expect(opts).toHaveLength(3)
    })
})
