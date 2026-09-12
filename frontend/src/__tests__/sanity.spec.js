import { describe, it, expect } from 'vitest'

describe('Vitest setup', () => {
    it('runs a basic test', () => {
        expect(2 + 2).toBe(4)
    })

    it('has access to localStorage (jsdom)', () => {
        localStorage.setItem('test', 'ok')
        expect(localStorage.getItem('test')).toBe('ok')
        localStorage.clear()
    })
})