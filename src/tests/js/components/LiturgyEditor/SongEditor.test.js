import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import SongEditor from '@/components/LiturgyEditor/Editors/SongEditor.vue'

const element = () => ({
    id: 1,
    title: 'Lied',
    data_type: 'song',
    data: {
        song: {
            id: 42,
            song: { id: 42, title: 'Ein vorhandenes Lied', verses: [] },
        },
        verses: '1',
        responsible: [],
    },
})

describe('SongEditor', () => {
    it('clears the selected song when the selection is emptied', () => {
        const get = vi.fn(() => Promise.resolve({
            data: {
                id: 42,
                reference: 'EG 1',
                song: { id: 42, title: 'Ein vorhandenes Lied', verses: [] },
            },
        }))
        const wrapper = mount(SongEditor, {
            props: { element: element(), service: {} },
            global: {
                stubs: {
                    FormInput: true,
                    FormSelectize: true,
                    TextStats: true,
                    Modal: true,
                    SongEditPane: true,
                },
                provide: { lists: { songs: [] } },
                mocks: { $api: () => ({ get }) },
            },
        })

        wrapper.vm.selectSong(null)

        expect(wrapper.vm.editedElement.data.song.id).toBe(-1)
        expect(wrapper.vm.selectedSong).toBeNull()
    })
})
