<template>
  <div class="space-y-4">
    <div class="page-head">
      <div>
        <div class="eyebrow">Administration</div>
        <h1>Users & Roles</h1>
        <p>Manage access, roles, branch assignments and account status from one place.</p>
      </div>
      <button class="btn btn-primary" @click="openCreate">+ Add user</button>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div v-for="card in summaryCards" :key="card.label" class="card metric metric-compact">
        <div class="metric-label">{{ card.label }}</div>
        <div class="metric-value metric-value-number">{{ card.value }}</div>
        <div class="metric-trend neutral">{{ card.note }}</div>
      </div>
    </div>

    <section class="card overflow-hidden">
      <div class="section-toolbar">
        <div>
          <h2 class="section-title">Team access</h2>
          <p class="section-subtitle">Roles control what each team member can view or change.</p>
        </div>
        <div class="toolbar-actions">
          <input v-model="search" class="input input-compact" placeholder="Search users..." />
          <select v-model="statusFilter" class="input input-compact filter-input">
            <option value="all">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="table-wrap">
        <table class="data-table users-table">
          <thead><tr><th>User</th><th>Role</th><th>Branch</th><th>Status</th><th>Last updated</th><th></th></tr></thead>
          <tbody>
            <tr v-for="u in filteredUsers" :key="u.id">
              <td><div class="user-cell"><div class="user-avatar">{{ initials(u.name) }}</div><div class="min-w-0"><div class="user-name truncate">{{u.name}}</div><div class="user-email truncate">{{u.email}}</div></div></div></td>
              <td><span class="role-pill">{{ roleName(u) }}</span></td>
              <td>{{u.branch?.name || 'All / Unassigned'}}</td>
              <td><span class="status" :class="u.is_active?'status-success':'status-neutral'">{{u.is_active?'Active':'Inactive'}}</span></td>
              <td>{{ formatDate(u.updated_at) }}</td>
              <td class="text-right whitespace-nowrap"><button class="btn btn-soft btn-sm" @click="edit(u)">Edit</button><button v-if="u.is_active && u.id !== currentUserId" class="btn btn-danger btn-sm ml-2" @click="deactivate(u)">Deactivate</button></td>
            </tr>
            <tr v-if="!filteredUsers.length"><td colspan="6"><div class="empty">No users match your filters.</div></td></tr>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card p-5">
      <div class="flex items-start justify-between gap-4"><div><h2 class="section-title">Available roles</h2><p class="section-subtitle">Use a role instead of assigning permissions user-by-user.</p></div><span class="status status-neutral">{{roles.length}} roles</span></div>
      <div class="role-grid mt-4">
        <div v-for="r in roles" :key="r.id" class="role-card">
          <div class="role-card-icon">{{ roleIcon(r.name) }}</div>
          <div class="min-w-0"><div class="role-card-name">{{r.name}}</div><div class="role-card-count">{{ roleUserCount(r.name) }} {{ roleUserCount(r.name)===1?'user':'users' }} · {{ r.permissions_count ?? 0 }} permissions</div></div>
        </div>
      </div>
    </section>

    <AppModal :open="modal" :title="editing?'Edit user':'Add user'" @close="modal=false">
      <form class="space-y-4" @submit.prevent="save">
        <div class="modal-intro"><div class="user-avatar large">{{ form.name ? initials(form.name) : '?' }}</div><div><div class="font-extrabold text-sm">{{editing?'Update access':'Create team account'}}</div><div class="text-[11px] text-slate-400 mt-1">Assign a role and optional branch to control access.</div></div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="label">Full name</label><input v-model.trim="form.name" class="input" required maxlength="255"></div>
          <div><label class="label">Email address</label><input v-model.trim="form.email" type="email" class="input" required :disabled="editing"></div>
        </div>
        <div v-if="!editing"><label class="label">Temporary password</label><input v-model="form.password" type="password" class="input" minlength="8" required><div class="field-help">The user should change this after first sign-in.</div></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="label">Role <span class="required">*</span></label><select v-model="form.role" class="input" required><option value="" disabled>Select a role</option><option v-for="r in roles" :key="r.id" :value="r.name">{{r.name}}</option></select><div v-if="!roles.length" class="field-error">No roles are available. Run the database seeder first.</div></div>
          <div><label class="label">Branch</label><select v-model="form.branch_id" class="input"><option value="">All / unassigned</option><option v-for="b in branches" :key="b.id" :value="b.id">{{b.name}}</option></select><div class="field-help">Leave unassigned for company-level access where permitted.</div></div>
        </div>
        <div v-if="editing" class="rounded-xl border border-slate-200 dark:border-slate-700 p-3"><label class="label">Account status</label><label class="inline-flex items-center gap-2 text-xs"><input v-model="form.is_active" type="checkbox"> Active account</label></div>
        <div v-if="selectedRole" class="role-preview"><div class="role-card-icon">{{roleIcon(selectedRole.name)}}</div><div><div class="font-bold text-xs">{{selectedRole.name}}</div><div class="text-[10px] text-slate-400 mt-1">{{ roleDescription(selectedRole.name) }}</div></div></div>
        <div class="action-row justify-end"><button type="button" class="btn btn-soft" @click="modal=false">Cancel</button><button class="btn btn-primary" :disabled="saving || !form.role">{{saving?'Saving...':editing?'Save changes':'Create user'}}</button></div>
      </form>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import {computed,onMounted,ref} from 'vue';
import {api,apiError} from '../../services/api';
import {useToast} from '../../stores/toast';
import {useConfirm} from '../../stores/confirm';
import AppModal from '../../components/AppModal.vue';
import {formatDateTime} from '../../utils/date';

const users=ref<any[]>([]),roles=ref<any[]>([]),branches=ref<any[]>([]),modal=ref(false),editing=ref<any>(null),saving=ref(false),search=ref(''),statusFilter=ref('all');
const form=ref<any>({name:'',email:'',password:'',role:'',branch_id:'',is_active:true});
const toast=useToast(),{ask:confirm}=useConfirm();
const currentUserId=Number(localStorage.getItem('mauzohub_user_id')||0);

const filteredUsers=computed(()=>users.value.filter(u=>{
  const q=search.value.toLowerCase().trim(); const matches=!q || [u.name,u.email,u.roles?.[0]?.name,u.branch?.name].some(x=>String(x||'').toLowerCase().includes(q));
  return matches && (statusFilter.value==='all' || (statusFilter.value==='active'?u.is_active:!u.is_active));
}));
const summaryCards=computed(()=>[
 {label:'Team members',value:users.value.length,note:'Company accounts'},
 {label:'Active users',value:users.value.filter(u=>u.is_active).length,note:'Can sign in'},
 {label:'Roles',value:roles.value.length,note:'Access profiles'},
]);
const selectedRole=computed(()=>roles.value.find(r=>r.name===form.value.role));
function roleName(u:any){return u.roles?.[0]?.name||'No role'}
function roleUserCount(name:string){return users.value.filter(u=>roleName(u)===name).length}
function initials(name:string){return String(name||'?').split(/\s+/).filter(Boolean).slice(0,2).map(x=>x[0]).join('').toUpperCase()}
function formatDate(v:any){return formatDateTime(v)}
function roleIcon(name:string){return ({'Super Admin':'★','Administrator':'A','Manager':'M','Accountant':'₵','Cashier':'$','Salesperson':'S'} as any)[name]||'R'}
function roleDescription(name:string){return ({'Super Admin':'Full system administration and configuration.','Administrator':'Manage daily operations, users and business settings.','Manager':'Monitor operations, reports and approvals.','Accountant':'Manage invoices, payments, expenses and financial reports.','Cashier':'Process sales and customer payments.','Salesperson':'Create sales and manage customer-facing activity.'} as any)[name]||'Configured access profile.'}
async function load(){
  const results=await Promise.allSettled([
    api.get('/users',{params:{per_page:200}}),
    api.get('/roles'),
    api.get('/branches',{params:{per_page:100}}),
  ]);
  const [u,r,b]=results;
  if(u.status==='fulfilled') users.value=u.value.data.data||u.value.data||[]; else toast.error('Unable to load users',apiError(u.reason));
  if(r.status==='fulfilled') roles.value=r.value.data.data||r.value.data||[]; else toast.error('Unable to load roles',apiError(r.reason));
  if(b.status==='fulfilled') branches.value=b.value.data.data||b.value.data||[];
  if(!form.value.role) form.value.role=roles.value.find((x:any)=>x.name==='Salesperson')?.name||roles.value[0]?.name||'';
}
function openCreate(){editing.value=null;form.value={name:'',email:'',password:'',role:roles.value.find((x:any)=>x.name==='Salesperson')?.name||roles.value[0]?.name||'',branch_id:'',is_active:true};modal.value=true}
function edit(u:any){editing.value=u;form.value={name:u.name,email:u.email,password:'',role:roleName(u),branch_id:u.branch_id||'',is_active:!!u.is_active};modal.value=true}
async function save(){saving.value=true;try{if(editing.value)await api.put(`/users/${editing.value.id}`,form.value);else await api.post('/users',form.value);toast.success('User saved',`${form.value.name} access settings were updated.`);modal.value=false;await load()}catch(e){toast.error('Could not save user',apiError(e))}finally{saving.value=false}}
async function deactivate(u:any){if(!(await confirm({title:'Deactivate user?',message:`${u.name} will no longer be able to sign in. This does not delete their audit history.`,confirmText:'Deactivate',danger:true})))return;try{await api.delete(`/users/${u.id}`);toast.success('User deactivated',`${u.name} can no longer sign in.`);await load()}catch(e){toast.error('Action failed',apiError(e))}}
onMounted(load)
</script>
