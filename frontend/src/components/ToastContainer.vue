<template>
  <div class="toast-container" aria-live="polite" aria-atomic="true">
    <TransitionGroup name="toast" tag="div" class="space-y-3">
      <div v-for="toast in toasts" :key="toast.id" class="toast-card" :class="`toast-${toast.type}`">
        <div class="toast-icon">{{ icons[toast.type] }}</div>
        <div class="min-w-0 flex-1"><div class="toast-title">{{ toast.title }}</div><div v-if="toast.message" class="toast-message">{{ toast.message }}</div></div>
        <button class="toast-close" @click="dismiss(toast.id)" aria-label="Close notification">×</button>
      </div>
    </TransitionGroup>
  </div>
</template>
<script setup lang="ts">
import {useToast} from '../stores/toast'
const {toasts,dismiss}=useToast()
const icons={success:'✓',error:'!',warning:'!',info:'i'}
</script>
