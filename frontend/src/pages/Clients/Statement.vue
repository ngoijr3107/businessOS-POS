<template>
  <div class="space-y-4">
    <div v-if="statement" class="print-surface">
      <div class="page-head no-print">
        <div>
          <RouterLink :to="`/clients/${statement.client.id}`" class="text-[11px] font-bold text-indigo-600">← {{ statement.client.name }}</RouterLink>
          <h1 class="mt-2">Client Statement</h1>
          <p>{{ statement.client.client_number }} · {{ statement.client.phone || 'No phone' }}</p>
        </div>
        <div class="action-row">
          <button class="btn btn-soft" @click="printPage">Print statement</button>
          <RouterLink :to="`/payments?client_id=${statement.client.id}`" class="btn btn-primary">Receive payment</RouterLink>
        </div>
      </div>
      <div class="card p-5">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
          <div><div class="metric-label">Client</div><div class="mt-1 font-extrabold">{{ statement.client.name }}</div></div>
          <div><div class="metric-label">Period</div><div class="mt-1 font-extrabold">{{ statement.from || 'All time' }} — {{ statement.to || 'Today' }}</div></div>
          <div class="md:text-right"><div class="metric-label">Closing balance</div><div class="mt-1 text-2xl font-black">{{ money(statement.closing_balance) }}</div></div>
        </div>
      </div>
      <div class="card table-wrap overflow-hidden">
        <table class="data-table">
          <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr></thead>
          <tbody>
            <tr><td>{{ statement.from || statement.client.created_at?.slice(0,10) || '—' }}</td><td>Opening</td><td>Opening balance</td><td class="text-right">{{ money(statement.opening_balance) }}</td><td class="text-right">—</td><td class="text-right font-bold">{{ money(statement.opening_balance) }}</td></tr>
            <tr v-for="row in statement.entries" :key="row.key">
              <td>{{ row.date }}</td><td><RouterLink v-if="row.type==='invoice'" :to="`/invoices/${row.id}`" class="font-bold text-indigo-600">{{ row.reference }}</RouterLink><RouterLink v-else :to="`/payments/${row.id}`" class="font-bold text-indigo-600">{{ row.reference }}</RouterLink></td>
              <td>{{ row.description }}</td><td class="text-right">{{ row.debit ? money(row.debit) : '—' }}</td><td class="text-right">{{ row.credit ? money(row.credit) : '—' }}</td><td class="text-right font-bold">{{ money(row.balance) }}</td>
            </tr>
            <tr v-if="!statement.entries.length"><td colspan="6"><div class="empty">No transactions in this period.</div></td></tr>
          </tbody>
          <tfoot><tr><td colspan="5" class="text-right font-extrabold">Closing balance</td><td class="text-right text-base font-black">{{ money(statement.closing_balance) }}</td></tr></tfoot>
        </table>
      </div>
    </div>
    <div v-else class="empty">Loading statement...</div>
  </div>
</template>
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { api, apiError } from '../../services/api';
import { useToast } from '../../stores/toast';
const route=useRoute(), toast=useToast(), statement=ref<any>(null);
function printPage(){window.print()}
function money(v:any){return new Intl.NumberFormat('en-TZ',{style:'currency',currency:'TZS',maximumFractionDigits:0}).format(Number(v||0))}
async function load(){try{statement.value=(await api.get(`/clients/${route.params.id}/statement`,{params:{from:route.query.from,to:route.query.to}})).data}catch(e){toast.error('Unable to load statement',apiError(e))}}
onMounted(load)
</script>
