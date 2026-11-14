import { afterEach, describe, expect, it, vi } from 'vitest'
import EventBus from '@/plugins/EventBus.js'

class FirstEvent {
    static eventName = 'shared-event'
}

class SecondEvent {
    static eventName = 'shared-event'
}

describe('EventBus', () => {
    afterEach(() => EventBus.removeAll())

    it('shares events across separately bundled event classes', () => {
        const handler = vi.fn()
        EventBus.listen(FirstEvent, handler)

        const event = new SecondEvent()
        EventBus.publish(event)

        expect(handler).toHaveBeenCalledWith(event)
    })
})
