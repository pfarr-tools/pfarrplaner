import FuneralEditor from '@/Pages/Rites/FuneralEditor.vue'
import BaptismEditor from '@/Pages/Rites/BaptismEditor.vue'
import WeddingEditor from '@/Pages/Rites/WeddingEditor.vue'
import { getRiteDraft } from '@/helpers/riteAutoSave'

describe('rite editor autosave integration', () => {
    it.each([
        ['FuneralEditor', FuneralEditor],
        ['BaptismEditor', BaptismEditor],
        ['WeddingEditor', WeddingEditor],
    ])(
        '%s watches its prepared form data',
        /**
         * Tests the editor autosave integration.
         * @param {string} _name Editor name.
         * @param {Object} editor Editor component.
         */
        (_name, editor) => {
            expect(editor.watch.autoSaveSnapshot).toBeTypeOf('function')
            expect(editor.methods.queueAutoSave).toBeTypeOf('function')
            expect(editor.methods.syncAutoSave).toBeTypeOf('function')
            expect(editor.methods.restoreAutoSaveDraft).toBeTypeOf('function')
        },
    )

    it.each([
        ['FuneralEditor', FuneralEditor, 'funerals', 'myFuneral'],
        ['BaptismEditor', BaptismEditor, 'baptisms', 'myBaptism'],
        ['WeddingEditor', WeddingEditor, 'weddings', 'myWedding'],
    ])('%s preserves edits locally while offline', (_name, editor, rite, formName) => {
        const navigatorDescriptor = Object.getOwnPropertyDescriptor(window, 'navigator')
        Object.defineProperty(window, 'navigator', {
            configurable: true,
            value: { onLine: false },
        })
        window.localStorage.clear()

        const context = {
            autoSaveDraftHandled: true,
            hasUnsavedChanges: false,
            autoSaveStatus: '',
            autoSaveTimer: null,
            [formName]: { id: 17 },
        }
        context.persistAutoSaveDraft = editor.methods.persistAutoSaveDraft.bind(context)
        const snapshot = JSON.stringify({ id: 17, notes: 'Gesprächsnotiz' })

        editor.methods.queueAutoSave.call(context, snapshot)

        expect(getRiteDraft(rite, 17).data.notes).toBe('Gesprächsnotiz')
        expect(context.hasUnsavedChanges).toBe(true)
        expect(context.autoSaveStatus).toContain('bei Verbindung')
        Object.defineProperty(window, 'navigator', navigatorDescriptor)
    })

    it('imports a matching funeral JSON package without replacing attachments', async () => {
        const applyFormData = vi.fn()
        const context = {
            myFuneral: {
                id: 11,
                data: () => ({ id: 11, attachments: [{ id: 2 }], buried_name: 'Old name' }),
            },
            applyFormData,
            autoSaveDraftHandled: false,
            autoSaveStatus: '',
        }
        const event = {
            target: {
                files: [{ text: async () => JSON.stringify({ id: 11, buried_name: 'New name' }) }],
                value: 'selected.json',
            },
        }

        await FuneralEditor.methods.importFromJson.call(context, event)

        expect(event.target.value).toBe('')
        expect(applyFormData).toHaveBeenCalledWith({ buried_name: 'New name' })
        expect(context.autoSaveDraftHandled).toBe(true)
    })
})
