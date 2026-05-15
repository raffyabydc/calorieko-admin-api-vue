<template>
  <Teleport to="body">
    <Transition name="toast-slide">
      <div v-if="visible" class="ck-toast" :class="`ck-toast--${type}`" role="alert">
        <div class="ck-toast__content">
          <CheckCircleIcon v-if="type === 'success'" :size="20" class="ck-toast__icon" />
          <AlertCircleIcon v-else-if="type === 'error'" :size="20" class="ck-toast__icon" />
          <InfoIcon v-else :size="20" class="ck-toast__icon" />
          
          <div class="ck-toast__text">
            <span class="ck-toast__message">{{ message }}</span>
          </div>

          <button @click="close" class="ck-toast__close" aria-label="Close">&times;</button>
        </div>
        <div class="ck-toast__progress">
          <div class="ck-toast__progress-bar" :style="{ width: progress + '%' }"></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { 
  CheckCircle as CheckCircleIcon, 
  AlertCircle as AlertCircleIcon, 
  Info as InfoIcon 
} from 'lucide-vue-next'

const props = defineProps({
  message: { type: String, required: true },
  type: { type: String, default: 'success' },
  duration: { type: Number, default: 5000 }
})

const emit = defineEmits(['close'])

const visible = ref(true)
const progress = ref(100)
let timer = null
let progressTimer = null

const close = () => {
  visible.value = false
  // Allow time for exit animation
  setTimeout(() => {
    emit('close')
  }, 300)
}

onMounted(() => {
  const startTime = Date.now()
  
  progressTimer = setInterval(() => {
    const elapsed = Date.now() - startTime
    progress.value = Math.max(0, 100 - (elapsed / props.duration) * 100)
    
    if (progress.value <= 0) {
      clearInterval(progressTimer)
    }
  }, 10)

  timer = setTimeout(close, props.duration)
})

onUnmounted(() => {
  if (timer) clearTimeout(timer)
  if (progressTimer) clearInterval(progressTimer)
})
</script>

<style scoped>
.ck-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  z-index: 10000;
  background: white;
  border-radius: var(--ck-radius-lg);
  box-shadow: var(--ck-shadow-xl);
  overflow: hidden;
  width: calc(100vw - 4rem);
  max-width: 380px;
  border: 1px solid var(--ck-gray-200);
  pointer-events: auto;
}

.ck-toast__content {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 1rem 1.25rem;
}

.ck-toast__icon {
  flex-shrink: 0;
}

.ck-toast--success .ck-toast__icon { color: var(--ck-primary); }
.ck-toast--error .ck-toast__icon { color: var(--ck-red); }
.ck-toast--info .ck-toast__icon { color: var(--ck-blue); }

.ck-toast__text {
  flex: 1;
  min-width: 0;
}

.ck-toast__message {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ck-gray-800);
  line-height: 1.4;
  display: block;
}

.ck-toast__close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: var(--ck-gray-400);
  cursor: pointer;
  padding: 0.25rem;
  margin-right: -0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.ck-toast__close:hover {
  color: var(--ck-gray-700);
}

.ck-toast__progress {
  height: 3px;
  background: var(--ck-gray-100);
  width: 100%;
}

.ck-toast__progress-bar {
  height: 100%;
  transition: width 10ms linear;
}

.ck-toast--success .ck-toast__progress-bar { background: var(--ck-primary); }
.ck-toast--error .ck-toast__progress-bar { background: var(--ck-red); }
.ck-toast--info .ck-toast__progress-bar { background: var(--ck-blue); }

/* Transitions */
.toast-slide-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 1, 1);
}

.toast-slide-enter-from {
  opacity: 0;
  transform: translateX(100px) scale(0.8);
}

.toast-slide-leave-to {
  opacity: 0;
  transform: translateX(100px) scale(0.8);
}
</style>
