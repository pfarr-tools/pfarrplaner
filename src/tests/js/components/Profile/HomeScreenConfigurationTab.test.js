import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import HomeScreenConfigurationTab from '@/components/Profile/Tabs/HomeScreenConfigurationTab.vue'

describe('HomeScreenConfigurationTab', () => {
    it('shows modules with a default route as start page choices', () => {
        const wrapper = mount(HomeScreenConfigurationTab, {
            props: {
                availableTabs: {},
                homeScreenTabsConfig: { tabs: [] },
                cities: [],
                locations: [],
                ministries: [],
                settings: { homeScreen: 'homescreen:configurable' },
                thirdParty: true,
                moduleGroups: {
                    default: [
                        { title: 'Kalender', defaultRoute: 'calendar' },
                    ],
                },
            },
            global: {
                stubs: {
                    'form-group': { template: '<div><slot :field-id="\'homeScreenInput\'" :described-by="undefined" /></div>' },
                    'form-check': true,
                    draggable: true,
                },
            },
        })

        expect(wrapper.findAll('option')).toHaveLength(2)
        expect(wrapper.find('option[value="route:calendar"]').exists()).toBe(true)
        expect(wrapper.text()).toContain('Kalender')
    })
})
