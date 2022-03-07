import { resolveRouter } from '@sleightful/core'
import { toRaw } from 'vue'
import { state } from './stores/state'

/**
 * The sleightful router.
 * This is the core sleightful function that you can use to navigate
 * in your application. Make sure the routes you call return a
 * sleightful response, otherwise you need to call `external`.
 *
 * @example
 * router.get('/posts/edit', { post })
 */
export const router = resolveRouter(() => toRaw(state.context.value!))
