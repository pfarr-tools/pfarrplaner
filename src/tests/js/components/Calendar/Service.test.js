import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import CalendarService from '@/components/Calendar/Service.vue'

function mountService(props = {}, apiMock = { get: vi.fn(), post: vi.fn(), delete: vi.fn() }) {
    return mount(CalendarService, {
        props: {
            city: { id: 1, is_org: false, childIds: [] },
            service: {
                id: 3,
                slug: 'morgengottesdienst',
                city_id: 1,
                titleText: 'Morgengottesdienst',
                date: '2024-01-14 10:00:00',
                timeText: '10:00',
                locationText: 'Stadtkirche',
                locationTextWithCity: 'Stadtkirche Musterstadt',
                isSpecialTime: false,
                isSpecialLocation: false,
                funeral: false,
                funeralSummary: '',
                descriptionText: 'Mit Abendmahl',
                internal_remarks: '',
                cc: false,
                city: { youtube_channel_url: '', default_ministries: ['Lektor:in'] },
                liturgicalInfo: {},
                hidden: false,
                participantText: {
                    P: 'Max Mustermann',
                    O: 'Erika Beispiel',
                    'Lektor:in': 'Chris Helfer',
                },
                need_predicant: false,
                isMine: true,
                isEditable: true,
                canSelfAssign: true,
                selfAssignViaPredicantClaim: false,
            },
            targetMode: false,
            target: null,
            ...props,
        },
        global: {
            provide: {
                settings: {},
            },
            stubs: {
                ControlledAccess: true,
                CalendarServiceParticipants: {
                    props: ['category', 'text'],
                    template: '<div class="participant-line">{{ category }} {{ text }}</div>',
                },
                CalendarServiceBaptism: true,
                CalendarServiceFuneral: true,
                CalendarServiceWedding: true,
                Modal: { template: '<div><slot /></div>' },
                FormSelectize: true,
            },
            mocks: {
                $api: () => apiMock,
                hasPermission: vi.fn(() => false),
                setUserSetting: vi.fn(),
                $updateComponentState: vi.fn(),
                $componentState: 'test-state',
                $page: {
                    props: {
                        currentUser: { data: { id: 1 } },
                        settings: {},
                        labels: {
                            code_pastor: 'P',
                            code_organist: 'O',
                            code_sacristan: 'M',
                            predicant: 'Prädikant:in',
                        },
                    },
                },
            },
        },
    })
}

describe('Calendar service card', () => {
    it('uses embedded service data without loading details again', () => {
        const apiMock = { get: vi.fn(), post: vi.fn(), delete: vi.fn() }
        const wrapper = mountService({}, apiMock)

        expect(apiMock.get).not.toHaveBeenCalled()
        expect(wrapper.text()).toContain('Max Mustermann')
        expect(wrapper.text()).toContain('Erika Beispiel')
        expect(wrapper.text()).toContain('Chris Helfer')
        expect(wrapper.vm.availableMinistries.map(item => item.id)).toContain('Lektor:in')
    })

    it('hides self-assignment when the service cannot be self-assigned', async () => {
        const wrapper = mountService()
        wrapper.vm.myService.isEditable = false
        wrapper.vm.myService.canSelfAssign = false
        await wrapper.vm.$nextTick()

        expect(wrapper.find('[title="Mich für diesen Gottesdienst eintragen"]').exists()).toBe(false)
    })

    it('shows self-assignment for the predicant-needed exception', async () => {
        const wrapper = mountService()
        wrapper.vm.myService.isEditable = false
        wrapper.vm.myService.need_predicant = true
        wrapper.vm.myService.canSelfAssign = true
        wrapper.vm.myService.selfAssignViaPredicantClaim = true
        await wrapper.vm.$nextTick()

        expect(wrapper.find('[title="Mich für diesen Gottesdienst eintragen"]').exists()).toBe(true)
    })
})
