import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const theme = ref<'light'|'dark'>((localStorage.getItem('mauzohub_theme') as 'light'|'dark') || 'light')
  function apply(){ document.documentElement.classList.toggle('dark', theme.value === 'dark'); document.documentElement.dataset.theme = theme.value }
  function setTheme(value:'light'|'dark'){ theme.value=value; localStorage.setItem('mauzohub_theme',value); apply() }
  function toggle(){ setTheme(theme.value==='dark'?'light':'dark') }
  apply()
  return {theme,setTheme,toggle,apply}
})
