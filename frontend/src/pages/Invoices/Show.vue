<template>
  <div v-if="invoice" class="space-y-4">
    <div class="page-head">
      <div>
        <RouterLink to="/invoices" class="text-[11px] font-bold text-indigo-600">← Invoices</RouterLink>
        <h1 class="mt-2">{{ invoice.invoice_number }}</h1>
        <p>{{ invoice.client?.name || 'Unknown client' }} · Issued {{ invoice.issue_date || '—' }}</p>
      </div>
      <div class="action-row">
        <button class="btn btn-soft no-print" @click="printPage">Print invoice</button><RouterLink v-if="invoice.client_id && Number(invoice.balance)>0" :to="`/payments?client_id=${invoice.client_id}&invoice_id=${invoice.id}`" class="btn btn-primary no-print">Receive payment</RouterLink><RouterLink v-if="invoice.client_id" :to="`/clients/${invoice.client_id}`" class="btn btn-soft no-print">View client</RouterLink>
        <button v-if="invoice.status !== 'cancelled'" class="btn btn-danger" @click="cancelInvoice">Cancel invoice</button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
      <div class="card metric"><div class="metric-label">Total</div><div class="metric-value mt-2">{{ money(invoice.total) }}</div></div>
      <div class="card metric"><div class="metric-label">Paid</div><div class="metric-value mt-2">{{ money(invoice.amount_paid) }}</div></div>
      <div class="card metric"><div class="metric-label">Balance</div><div class="metric-value mt-2">{{ money(invoice.balance) }}</div></div>
      <div class="card metric"><div class="metric-label">Status</div><div class="mt-3"><StatusBadge :value="invoice.status" /></div></div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.5fr_1fr]">
      <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800"><h2 class="text-sm font-extrabold">Invoice items</h2></div>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Description</th><th>Qty</th><th>Unit price</th><th>Discount</th><th>Tax</th><th class="text-right">Amount</th></tr></thead><tbody>
          <tr v-for="item in invoice.items || []" :key="item.id"><td>{{ item.description || item.product?.name || 'Item' }}</td><td>{{ item.quantity }}</td><td>{{ money(item.unit_price) }}</td><td>{{ money(item.discount) }}</td><td>{{ money(item.tax) }}</td><td class="text-right font-bold">{{ money(item.total) }}</td></tr>
          <tr v-if="!(invoice.items || []).length"><td colspan="6"><div class="empty">No invoice items.</div></td></tr>
        </tbody></table></div>
        <div class="border-t border-slate-100 dark:border-slate-800 p-5 ml-auto max-w-sm space-y-2 text-sm"><div class="flex justify-between"><span>Subtotal</span><b>{{ money(invoice.subtotal) }}</b></div><div class="flex justify-between"><span>Discount</span><b>{{ money(invoice.discount) }}</b></div><div class="flex justify-between"><span>Tax</span><b>{{ money(invoice.tax) }}</b></div><div class="flex justify-between text-base border-t pt-3"><span class="font-bold">Total</span><b>{{ money(invoice.total) }}</b></div></div>
      </div>

      <div class="space-y-4">
        <div class="card p-5"><h2 class="text-sm font-extrabold">Invoice details</h2><dl class="mt-4 grid grid-cols-2 gap-4 text-xs"><div><dt class="text-slate-400">Client</dt><dd class="mt-1 font-bold">{{ invoice.client?.name || '—' }}</dd></div><div><dt class="text-slate-400">Due date</dt><dd class="mt-1 font-bold">{{ invoice.due_date || '—' }}</dd></div><div><dt class="text-slate-400">Sale</dt><dd class="mt-1 font-bold"><RouterLink v-if="invoice.sale_id" :to="`/sales/${invoice.sale_id}`" class="text-indigo-600">{{ invoice.sale?.sale_number || `Sale #${invoice.sale_id}` }}</RouterLink><span v-else>Manual invoice</span></dd></div><div><dt class="text-slate-400">Notes</dt><dd class="mt-1 font-bold">{{ invoice.notes || '—' }}</dd></div></dl></div>
        <div class="card overflow-hidden"><div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800"><h2 class="text-sm font-extrabold">Payment history</h2></div><table class="data-table"><thead><tr><th>Receipt</th><th>Date</th><th>Amount</th></tr></thead><tbody><tr v-for="a in invoice.allocations || []" :key="a.id"><td><RouterLink :to="`/payments/${a.payment_id}`" class="font-bold text-indigo-600">{{ a.payment?.payment_number || `PAY-${a.payment_id}` }}</RouterLink></td><td>{{ formatDisplayDate(a.payment?.payment_date) }}</td><td>{{ money(a.amount) }}</td></tr><tr v-if="!(invoice.allocations || []).length"><td colspan="3"><div class="empty">No payments allocated.</div></td></tr></tbody></table></div>
      </div>
    </div>
  </div>
  <div v-else class="empty">Loading invoice...</div>
</template>
<script setup lang="ts">import {formatDate as formatDisplayDate} from '../../utils/date';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, apiError } from '../../services/api';
import { useConfirm } from '../../stores/confirm';
import { useToast } from '../../stores/toast';
import StatusBadge from '../../components/StatusBadge.vue';
const route=useRoute(), router=useRouter(), invoice=ref<any>(null); const {ask:confirm}=useConfirm(); const toast=useToast();
function printPage(){window.print()}
function money(v:any){return new Intl.NumberFormat('en-TZ',{style:'currency',currency:'TZS',maximumFractionDigits:0}).format(Number(v||0))}
async function load(){invoice.value=(await api.get(`/invoices/${route.params.id}`)).data}
async function cancelInvoice(){if(!(await confirm({title:'Cancel invoice?',message:`${invoice.value.invoice_number} will be cancelled and removed from active receivables. This action cannot be undone.`,confirmText:'Cancel invoice',danger:true})))return;try{await api.put(`/invoices/${invoice.value.id}`,{status:'cancelled'});toast.success('Invoice cancelled',`${invoice.value.invoice_number} was cancelled successfully.`);await load()}catch(e){toast.error('Action failed',apiError(e))}}
onMounted(load)
</script>
