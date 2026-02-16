import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useLoadingStore = defineStore('loading', () => {
  const pendingRequests = ref(0)

  const isLoading = computed(() => pendingRequests.value > 0)

  function start() {
    pendingRequests.value++
  }

  function finish() {
    if (pendingRequests.value > 0) {
      pendingRequests.value--
    }
  }

  return { isLoading, start, finish }
})