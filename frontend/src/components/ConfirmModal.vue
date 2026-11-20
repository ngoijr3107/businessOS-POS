<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/45 p-4 backdrop-blur-sm" @click.self="$emit('cancel')">
      <div class="card w-full max-w-md p-6 shadow-2xl">
        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">{{ icon }}</div>
        <h3 class="text-base font-extrabold">{{ title }}</h3>
        <p class="mt-2 text-xs leading-6 text-slate-500 dark:text-slate-400">{{ message }}</p>
        <div class="mt-6 flex justify-end gap-2"><button class="btn btn-soft" :disabled="loading" @click="$emit('cancel')">Cancel</button><button class="btn" :class="danger ? 'btn-danger' : 'btn-primary'" :disabled="loading" @click="$emit('confirm')">{{ loading ? loadingText : confirmText }}</button></div>
      </div>
    </div>
  </Transition>
</template>
<script setup lang="ts">withDefaults(defineProps<{open:boolean,title:string,message:string,confirmText?:string,loadingText?:string,icon?:string,loading?:boolean,danger?:boolean}>(),{confirmText:'Confirm',loadingText:'Working…',icon:'✓',loading:false,danger:false});defineEmits(['confirm','cancel'])</script>
