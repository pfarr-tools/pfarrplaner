import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import ProfileEditor from '@/Pages/Profile/ProfileEditor.vue'

describe('ProfileEditor security tab', () => {
    it('passes the server-provided passkeys to the security tab', () => {
        const patch = vi.fn()
        const wrapper = mount(ProfileEditor, {
            props: {
                tab: 'security',
                user: { email: 'user@example.org' },
                calendarConnections: [],
                cities: [],
                subscriptions: [],
                availableTabs: {},
                homeScreenTabsConfig: { tabs: [] },
                locations: [],
                ministries: [],
                settings: {},
                twoFactorEnabled: false,
                twoFactorPending: false,
                passkeys: [{ id: 7, name: 'Laptop' }],
            },
            global: {
                stubs: {
                    'admin-layout': { template: '<div><slot /></div>' },
                    'tab-headers': true,
                    'tab-header': true,
                    tabs: { template: '<div><slot /></div>' },
                    tab: { template: '<div><slot /></div>' },
                    ProfileTab: true,
                    'security-tab': {
                        props: ['passkeys', 'twoFactorEnabled', 'twoFactorPending', 'user'],
                        template:
                            '<div data-testid="security-passkeys">{{ passkeys[0].name }}</div>',
                    },
                    SubscriptionsTab: true,
                    HomeScreenConfigurationTab: true,
                    CalendarConnectionsTab: true,
                    ExternalContentTab: true,
                },
                mocks: { $inertia: { patch } },
            },
        })

        expect(wrapper.find('[data-testid="security-passkeys"]').text()).toBe('Laptop')
        wrapper.vm.saveProfile()
        expect(patch).toHaveBeenCalledOnce()
        expect(patch.mock.calls[0][1]).not.toHaveProperty('password')
        expect(patch.mock.calls[0][1]).not.toHaveProperty('current_password')
        expect(patch.mock.calls[0][1]).not.toHaveProperty('new_password')
    })
})
