<template>
  <div class="app-shell" :class="{'sidebar-collapsed': collapsed}">
    <div v-if="mobileOpen" class="mobile-backdrop" @click="mobileOpen=false"></div>
    <aside class="sidebar" :class="{'mobile-open': mobileOpen}">
      <div class="brand"><div class="brand-mark">M</div><div class="brand-copy"><div class="brand-name">Mauzo<span>Hub</span></div><div class="brand-sub">Business OS</div></div><button class="sidebar-close" @click="mobileOpen=false" aria-label="Close navigation">×</button></div>
      <nav class="sidebar-nav mt-7">
        <div class="nav-label">Workspace</div>
        <RouterLink v-for="item in links" :key="item.to" :to="item.to" class="nav-item" :class="{'nav-item-active': isActive(item)}" @click="mobileOpen=false"><span class="nav-icon">{{item.icon}}</span><span class="nav-text">{{item.label}}</span></RouterLink>
        <div class="nav-label mt-7">Management</div>
        <RouterLink v-for="item in management" :key="item.to" :to="item.to" class="nav-item" :class="{'nav-item-active': isActive(item)}" @click="mobileOpen=false"><span class="nav-icon">{{item.icon}}</span><span class="nav-text">{{item.label}}</span></RouterLink>
        <div class="nav-label mt-7">Insights</div>
        <RouterLink v-for="item in reports" :key="item.to" :to="item.to" class="nav-item" :class="{'nav-item-active': isActive(item)}" @click="mobileOpen=false"><span class="nav-icon">{{item.icon}}</span><span class="nav-text">{{item.label}}</span></RouterLink>
      </nav>
      <div class="sidebar-footer">
        <div class="sidebar-status"><span class="online-dot"></span><span class="nav-text">All systems operational</span></div>
        <div class="sidebar-footer-actions">
          <button class="sidebar-footer-btn" type="button" @click="showNotifications=true" title="Notifications">🔔<span class="nav-text">Alerts</span><span v-if="unread" class="sidebar-footer-count">{{ unread > 9 ? '9+' : unread }}</span></button>
          <button class="sidebar-footer-btn" type="button" @click="askTheme" :title="theme.theme==='dark'?'Switch to light mode':'Switch to dark mode'">{{theme.theme==='dark'?'☀':'☾'}}<span class="nav-text">{{theme.theme==='dark'?'Light mode':'Dark mode'}}</span></button>
        </div>
      </div>
    </aside>

    <main class="main-shell">
      <header class="topbar">
        <div class="flex items-center gap-3"><button class="mobile-menu icon-btn" @click="mobileOpen=true">☰</button><button class="collapse-btn icon-btn" @click="collapsed=!collapsed" :title="collapsed?'Expand sidebar':'Collapse sidebar'">{{collapsed?'→':'←'}}</button><div><div class="eyebrow">{{companyName}}</div><div class="page-title">{{title}}</div></div></div>
        <div class="top-actions">
          <button class="search-pill" @click="focusSearch">⌕ <span>Quick search</span><kbd>Ctrl K</kbd></button>
          <button class="icon-btn notification-btn" @click="showNotifications=true" title="Notifications">♢<span v-if="unread" class="notification-dot">{{unread>9?'9+':unread}}</span></button>
          <button class="icon-btn theme-btn" @click="askTheme" :title="theme.theme==='dark'?'Switch to light mode':'Switch to dark mode'">{{theme.theme==='dark'?'☀':'☾'}}</button>
          <div class="avatar">{{initials}}</div>
          <button class="user-menu" @click="logout">Sign out</button>
        </div>
      </header>
      <section class="content"><RouterView/></section>
    </main>

    <ConfirmModal :open="themeConfirm" :title="theme.theme==='dark'?'Switch to light mode?':'Switch to dark mode?'" :message="theme.theme==='dark'?'The interface will return to the light workspace theme.':'The interface will switch to the dark workspace theme and remember your choice.'" :confirm-text="theme.theme==='dark'?'Use light mode':'Use dark mode'" icon="◐" @cancel="themeConfirm=false" @confirm="confirmTheme"/>
    <Transition name="slide"><div v-if="showNotifications" class="notification-panel"><div class="flex items-center justify-between border-b border-slate-100 p-5 dark:border-slate-800"><div><h3 class="text-sm font-extrabold">Notifications</h3><p class="mt-1 text-[10px] text-slate-400">Operational alerts and collection priorities</p></div><button class="icon-btn" @click="showNotifications=false">×</button></div><div class="max-h-[70vh] overflow-y-auto p-3"><div v-for="n in notifications" :key="n.id" class="notification-item" :class="{'unread':!n.is_read}" @click="readNotification(n)"><div class="notification-icon">{{n.type==='low_stock'?'!':'₵'}}</div><div class="min-w-0 flex-1"><div class="text-xs font-bold">{{n.title}}</div><div class="mt-1 text-[11px] leading-5 text-slate-500 dark:text-slate-400">{{n.message}}</div></div></div><div v-if="!notifications.length" class="empty">No alerts right now.</div></div></div></Transition>
  </div>
</template>
<script setup lang="ts">
import {computed,onMounted,ref} from 'vue';import {useRoute} from 'vue-router';import {useAuthStore} from '../stores/auth';import {useThemeStore} from '../stores/theme';import {api} from '../services/api';import ConfirmModal from '../components/ConfirmModal.vue';
const route=useRoute(),auth=useAuthStore(),theme=useThemeStore();const collapsed=ref(localStorage.getItem('mauzohub_sidebar')==='collapsed'),mobileOpen=ref(false),themeConfirm=ref(false),showNotifications=ref(false),notifications=ref<any[]>([]),unread=ref(0);
const links=[{to:'/',label:'Overview',icon:'⌂'},{to:'/clients',label:'Clients',icon:'♙'},{to:'/sales',label:'Sales',icon:'↗'},{to:'/invoices',label:'Invoices',icon:'▤'},{to:'/payments',label:'Payments',icon:'₵'},{to:'/reports/receivables',label:'Receivables',icon:'◔'}];
const management=[{to:'/management',label:'Management & Audit',icon:'◉'},{to:'/users',label:'Users & Roles',icon:'♟'},{to:'/products',label:'Products & Services',icon:'◈'},{to:'/expenses',label:'Expenses',icon:'↘'},{to:'/categories',label:'Categories',icon:'◫'},{to:'/branches',label:'Branches',icon:'⌂'}];
const reports=[{to:'/reports',label:'Business Insights',icon:'✦'},{to:'/reports/sales',label:'Sales Report',icon:'↗'},{to:'/reports/expenses',label:'Expense Report',icon:'↘'},{to:'/reports/inventory',label:'Inventory Report',icon:'◈'}];
const title=computed(()=>{const item=[...links,...management,...reports].find(x=>x.to===route.path);if(item)return item.label;if(route.path.startsWith('/clients/'))return 'Client Details';if(route.path.startsWith('/invoices/'))return 'Invoice Details';if(route.path.startsWith('/payments/'))return 'Payment Details';if(route.path.startsWith('/sales/'))return 'Sale Details';return 'Overview'});function isActive(item:any){if(item.to==='/')return route.path==='/';return route.path===item.to || route.path.startsWith(item.to+'/')}const companyName=computed(()=>auth.user?.company?.name??'Demo Company');const initials=computed(()=>String(auth.user?.name??'SA').split(' ').map(x=>x[0]).slice(0,2).join('').toUpperCase());
function focusSearch(){document.dispatchEvent(new CustomEvent('mauzohub:search'))}function askTheme(){themeConfirm.value=true}function confirmTheme(){theme.toggle();themeConfirm.value=false}function logout(){auth.logout().then(()=>location.href='/login')}function persist(){localStorage.setItem('mauzohub_sidebar',collapsed.value?'collapsed':'expanded')}async function loadNotifications(){try{notifications.value=(await api.get('/notifications')).data;unread.value=notifications.value.filter(n=>!n.is_read).length}catch{}}async function readNotification(n:any){if(n.is_read)return;try{await api.patch(`/notifications/${n.id}/read`);n.is_read=true;unread.value=Math.max(0,unread.value-1)}catch{}}onMounted(loadNotifications);import {watch} from 'vue';watch(collapsed,persist);watch(showNotifications,v=>{if(v)loadNotifications()})
</script>
