<template>
  <ConfirmModal v-if="state" :open="true" :title="state.title" :message="state.message" :confirm-text="state.confirmText || 'Confirm'" :icon="state.icon || (state.danger ? '!' : '?')" :danger="state.danger" @cancel="close(false)" @confirm="close(true)" />
</template>
<script setup lang="ts">import {useConfirm} from '../stores/confirm';import ConfirmModal from './ConfirmModal.vue';const {state,close}=useConfirm()</script>
