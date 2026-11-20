import {ref} from 'vue'
export interface ConfirmOptions{title:string;message:string;confirmText?:string;cancelText?:string;danger?:boolean;icon?:string}
const state=ref<(ConfirmOptions & {resolve:(value:boolean)=>void})|null>(null)
export function useConfirm(){
  function ask(options:ConfirmOptions){
    if(state.value) state.value.resolve(false)
    return new Promise<boolean>(resolve=>{state.value={...options,resolve}})
  }
  function close(value:boolean){const current=state.value;if(!current)return;state.value=null;current.resolve(value)}
  return {state,ask,close}
}
