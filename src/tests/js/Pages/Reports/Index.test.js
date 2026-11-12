import { mount } from '@vue/test-utils'
import ReportsIndex from '@/Pages/Reports/Index.vue'

const reports = {
    Gottesdienste: [
        {
            title: 'Jahresplan',
            key: 'ServiceTable',
            description: 'Gottesdienste als Übersicht',
            icon: 'mdi mdi-calendar',
            inertia: true,
        },
        {
            title: 'Excel-Tabelle',
            key: 'ServiceExcelTable',
            description: 'Export für Tabellenprogramme',
            icon: 'mdi mdi-file-excel',
            inertia: false,
        },
    ],
}

const FormatIndexStub = {
    template: '<div data-format-index />',
    props: ['groups', 'searchPlaceholder', 'emptyTitle', 'emptyDescription'],
}

describe('Reports Index', () => {
    it('maps grouped reports to the shared format index', () => {
        const wrapper = mount(ReportsIndex, {
            props: { reports },
            global: {
                stubs: {
                    AdminLayout: { template: '<div><slot /></div>' },
                    FormatIndex: FormatIndexStub,
                },
            },
        })

        expect(wrapper.getComponent(FormatIndexStub).props('emptyTitle')).toBe(
            'Keine Ausgabeformate verfügbar',
        )
        expect(wrapper.getComponent(FormatIndexStub).props('groups')).toEqual([
            {
                title: 'Gottesdienste',
                items: [
                    {
                        key: 'ServiceTable',
                        title: 'Jahresplan',
                        description: 'Gottesdienste als Übersicht',
                        icon: 'mdi mdi-calendar',
                        href: '/reports.setup',
                        inertia: true,
                    },
                    {
                        key: 'ServiceExcelTable',
                        title: 'Excel-Tabelle',
                        description: 'Export für Tabellenprogramme',
                        icon: 'mdi mdi-file-excel',
                        href: '/reports.setup',
                        inertia: false,
                    },
                ],
            },
        ])
    })

    it('keeps Inertia and regular report navigation distinct', () => {
        const wrapper = mount(ReportsIndex, {
            props: { reports },
            global: {
                stubs: {
                    AdminLayout: { template: '<div><slot /></div>' },
                    FormatIndex: FormatIndexStub,
                },
            },
        })
        const [inertiaReport, regularReport] = wrapper
            .getComponent(FormatIndexStub)
            .props('groups')[0].items

        wrapper.vm.createReport(inertiaReport)

        expect(wrapper.vm.$inertia.get).toHaveBeenCalledWith('/reports.setup')
        expect(regularReport.href).toBe('/reports.setup')
        expect(regularReport.inertia).toBe(false)
    })
})
