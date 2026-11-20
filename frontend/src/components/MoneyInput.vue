<template>
  <input
    :value="display"
    class="input"
    type="text"
    inputmode="decimal"
    autocomplete="off"
    @focus="editing = true"
    @blur="editing = false; emitValue(display)"
    @input="onInput"
    @keydown="preventInvalid"
  >
</template>
<script setup lang="ts">
import {computed, ref} from 'vue'
const props = withDefaults(defineProps<{modelValue:number|string|null, min?:number, max?:number}>(), {min:0})
const emit = defineEmits<{(e:'update:modelValue', value:number):void}>()
const editing=ref(false)
function parse(v:any){
  if(v===null||v===undefined||v==='') return 0
  const normalized=String(v).trim().replace(/[^0-9,.-]/g,'').replace(/,/g,'')
  const n=Number(normalized)
  if(!Number.isFinite(n)) return 0
  return Math.min(props.max ?? Infinity, Math.max(props.min ?? -Infinity, n))
}
const display=computed(()=>editing.value ? String(props.modelValue ?? '') : new Intl.NumberFormat('en-TZ',{minimumFractionDigits:0,maximumFractionDigits:2}).format(parse(props.modelValue)))
function onInput(e:Event){const el=e.target as HTMLInputElement; emitValue(el.value)}
function emitValue(v:any){emit('update:modelValue', Math.round((parse(v)+Number.EPSILON)*100)/100)}
function preventInvalid(e:KeyboardEvent){if(['e','E','+','-'].includes(e.key))e.preventDefault()}
</script>
