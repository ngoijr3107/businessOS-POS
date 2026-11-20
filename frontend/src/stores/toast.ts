import { ref } from 'vue'

type ToastType = 'success' | 'error' | 'warning' | 'info'
export interface ToastItem { id:number; type:ToastType; title:string; message?:string; duration?:number }
const items = ref<ToastItem[]>([])
let seed = 0

function push(type:ToastType, title:string, message?:string, duration=4200){
  const id=++seed
  items.value.push({id,type,title,message,duration})
  if(duration>0) window.setTimeout(()=>dismiss(id), duration)
  return id
}
function dismiss(id:number){ items.value=items.value.filter(t=>t.id!==id) }
export function useToast(){
  return {
    toasts: items,
    success:(title:string,message?:string)=>push('success',title,message),
    error:(title:string,message?:string)=>push('error',title,message,6000),
    warning:(title:string,message?:string)=>push('warning',title,message),
    info:(title:string,message?:string)=>push('info',title,message),
    dismiss,
  }
}
