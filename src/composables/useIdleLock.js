/**
 * Idle detection and lock screen.
 * After IDLE_MINUTES of no activity, trigger lock. Reset on mousemove, keydown, click, scroll, touchstart.
 */
const IDLE_MINUTES = 15
const IDLE_MS = IDLE_MINUTES * 60 * 1000

const events = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart']

export function useIdleLock(onLock) {
  let timer = null

  function reset() {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      onLock()
    }, IDLE_MS)
  }

  function stop() {
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
    events.forEach((ev) => document.removeEventListener(ev, reset))
  }

  function start() {
    events.forEach((ev) => document.addEventListener(ev, reset))
    reset()
  }

  return { start, stop, reset }
}
