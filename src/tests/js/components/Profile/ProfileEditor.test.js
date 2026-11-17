import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ProfileEditor from '@/Pages/Profile/ProfileEditor.vue'

describe('ProfileEditor security tab', () => {
    it('passes the server-provided passkeys to the security tab', () => {
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
                        props: ['passkeys'],
                        template:
                            '<div data-testid="security-passkeys">{{ passkeys[0].name }}</div>',
                    },
                    SubscriptionsTab: true,
                    HomeScreenConfigurationTab: true,
                    CalendarConnectionsTab: true,
                    ExternalContentTab: true,
                },
            },
        })

        expect(wrapper.find('[data-testid="security-passkeys"]').text()).toBe('Laptop')
    })
})
