import { describe, expect, it, vi } from 'vitest'
import { uploadFileInChunks } from '@/components/Ui/forms/resumableUpload.js'

describe('resumable upload client', () => {
    it('skips confirmed chunks, retries a failed chunk and reports progress', async () => {
        const file = new File(['0123456789'], 'photo.jpg', { type: 'image/jpeg' })
        const sent = []
        const progress = vi.fn()
        const sendChunk = vi.fn()
            .mockRejectedValueOnce(new Error('network'))
            .mockResolvedValue({ received: [0, 1, 2] })

        const result = await uploadFileInChunks(file, {
            chunkSize: 4,
            start: vi.fn().mockResolvedValue({ session: 7, received: [0] }),
            sendChunk: async (session, index, blob) => {
                sent.push([session, index, blob.size])
                return sendChunk()
            },
            complete: vi.fn().mockResolvedValue({ id: 12 }),
            onProgress: progress,
        })

        expect(result).toEqual({ id: 12 })
        expect(sent).toEqual([[7, 1, 4], [7, 1, 4], [7, 2, 2]])
        expect(progress).toHaveBeenLastCalledWith(100)
    })
})
