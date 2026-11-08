import { describe, expect, it } from 'vitest'
import {
    appendFiles,
    createUploadItem,
    duplicateUploadItem,
} from '@/components/Ui/forms/attachmentUploadQueue.js'

const file = (name, type = 'image/jpeg') => new File(['data'], name, { type })

describe('attachment upload queue', () => {
    it('creates a ready item with source and variant ids', () => {
        const item = createUploadItem(file('Foto.jpg'))

        expect(item).toMatchObject({
            file: expect.any(File),
            sourceId: expect.any(String),
            id: expect.any(String),
            title: 'Foto',
            status: 'ready',
            progress: 0,
            error: null,
            cut: null,
            preset: null,
        })
        expect(item.id).not.toBe(item.sourceId)
    })

    it('appends every selected file exactly once', () => {
        const queue = appendFiles([], [file('one.jpg'), file('two.pdf', 'application/pdf')])

        expect(queue).toHaveLength(2)
        expect(queue.map(item => item.file.name)).toEqual(['one.jpg', 'two.pdf'])
        expect(new Set(queue.map(item => item.id)).size).toBe(2)
    })

    it('duplicates a source as an independent variant', () => {
        const original = createUploadItem(file('Foto.jpg'), { title: 'Original' })
        const copy = duplicateUploadItem(original, {
            title: 'Homepage',
            cut: 'homepage',
            preset: { width: 1200, height: 630 },
        })

        expect(copy.sourceId).toBe(original.sourceId)
        expect(copy.id).not.toBe(original.id)
        expect(copy.file).toBe(original.file)
        expect(copy).toMatchObject({ title: 'Homepage', cut: 'homepage' })
        expect(copy.preset).toEqual({ width: 1200, height: 630 })
    })
})
