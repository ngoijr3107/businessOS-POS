<template><div>
  <div class="page-head">
    <div><h1>Categories</h1><p>Organise products and operating expenses.</p></div>
    <div class="action-row">
      <button class="btn btn-soft" @click="add('product')">+ Product category</button>
      <button class="btn btn-primary" @click="add('expense')">+ Expense category</button>
    </div>
  </div>
  <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="card overflow-hidden">
      <div class="category-card-head">
        <div><h2 class="font-bold text-sm">Product categories</h2><p>Group products and services for faster reporting.</p></div>
        <button class="btn btn-primary btn-sm" @click="add('product')">+ Add</button>
      </div>
      <div class="table-wrap">
        <table class="data-table"><thead><tr><th>Name</th><th>Items</th><th class="text-right">Actions</th></tr></thead>
          <tbody><tr v-for="c in products" :key="c.id"><td>{{c.name}}</td><td>{{c.products_count}}</td><td><div class="flex justify-end gap-2"><button class="btn btn-soft" @click="edit('product',c)">Edit</button><button class="btn btn-danger" @click="remove('product',c)">Delete</button></div></td></tr>
          <tr v-if="!products.length"><td colspan="3"><div class="category-empty"><strong>No product categories yet</strong><span>Create your first category to organise the catalogue.</span><button class="btn btn-soft" @click="add('product')">+ Add product category</button></div></td></tr></tbody>
        </table>
      </div>
    </div>
    <div class="card overflow-hidden">
      <div class="category-card-head">
        <div><h2 class="font-bold text-sm">Expense categories</h2><p>Track operating costs by meaningful business groups.</p></div>
        <button class="btn btn-primary btn-sm" @click="add('expense')">+ Add</button>
      </div>
      <div class="table-wrap">
        <table class="data-table"><thead><tr><th>Name</th><th>Expenses</th><th class="text-right">Actions</th></tr></thead>
          <tbody><tr v-for="c in expenses" :key="c.id"><td>{{c.name}}</td><td>{{c.expenses_count}}</td><td><div class="flex justify-end gap-2"><button class="btn btn-soft" @click="edit('expense',c)">Edit</button><button class="btn btn-danger" @click="remove('expense',c)">Delete</button></div></td></tr>
          <tr v-if="!expenses.length"><td colspan="3"><div class="category-empty"><strong>No expense categories yet</strong><span>Add categories such as rent, utilities, transport or marketing.</span><button class="btn btn-soft" @click="add('expense')">+ Add expense category</button></div></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
  <AppModal :open="open" :title="editing?'Edit category':(kind==='product'?'New product category':'New expense category')" @close="open=false">
    <form @submit.prevent="save"><label class="label">Category name *</label><input v-model="name" class="input" required maxlength="100" placeholder="e.g. Electronics"><div class="mt-5 flex justify-end gap-2"><button type="button" class="btn btn-soft" @click="open=false">Cancel</button><button class="btn btn-primary">Save category</button></div></form>
  </AppModal>
  <div v-if="error" class="fixed bottom-5 right-5 rounded-xl bg-rose-600 px-4 py-3 text-xs font-bold text-white shadow-xl">{{error}}</div>
</div></template>
<script setup lang="ts">import {onMounted,ref} from 'vue';import {api,apiError} from '../../services/api';import {useConfirm} from '../../stores/confirm';import {useToast} from '../../stores/toast';import AppModal from '../../components/AppModal.vue';const {ask:confirm}=useConfirm();const toast=useToast();const products=ref<any[]>([]),expenses=ref<any[]>([]),open=ref(false),editing=ref<any>(null),name=ref(''),kind=ref<'product'|'expense'>('product'),error=ref('');async function load(){[products.value,expenses.value]=await Promise.all([(api.get('/categories').then(r=>r.data)),(api.get('/expense-categories').then(r=>r.data))])}function add(k:'product'|'expense'='product'){editing.value=null;name.value='';kind.value=k;error.value='';open.value=true}function edit(k:any,c:any){kind.value=k;editing.value=c;name.value=c.name;open.value=true}async function save(){try{const base=kind.value==='product'?'categories':'expense-categories';if(editing.value)await api.put(`/${base}/${editing.value.id}`,{name:name.value});else await api.post(`/${base}`,{name:name.value});open.value=false;await load();toast.success(editing.value?'Category updated':'Category created','Category changes were saved successfully.')}catch(e){error.value=apiError(e);toast.error('Action failed', apiError(e))}}async function remove(k:any,c:any){if(!(await confirm({title:'Delete category?',message:`You are about to delete ${c.name}. This action cannot be undone.`,confirmText:'Delete',danger:true})))return;try{await api.delete(`/${k==='product'?'categories':'expense-categories'}/${c.id}`);await load();toast.success('Category deleted', `${c.name} was deleted successfully.`)}catch(e){error.value=apiError(e);toast.error('Action failed', apiError(e))}}onMounted(load)</script>
