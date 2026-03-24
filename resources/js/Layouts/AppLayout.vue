<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  HomeIcon, UsersIcon, CalendarIcon, CreditCardIcon,
  CogIcon, UserGroupIcon, Bars3Icon, SunIcon, MoonIcon,
  ChevronDownIcon, UserCircleIcon, ArrowRightOnRectangleIcon, BeakerIcon, ArchiveBoxIcon,
  ClipboardDocumentListIcon, ChartBarIcon, ChatBubbleLeftRightIcon, ArrowUpTrayIcon
} from '@heroicons/vue/24/outline'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const clinic = computed(() => page.props.auth?.clinic)
const waUnread = computed(() => page.props.wa_unread ?? 0)

const sidebarOpen = ref(true)
const mobileOpen = ref(false)
const userMenuOpen = ref(false)
const isDark = ref(document.documentElement.classList.contains('dark'))

const toggleDark = () => {
  isDark.value = !isDark.value
  document.documentElement.classList.toggle('dark', isDark.value)
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
}

const navigation = [
  { name: 'Dashboard',    href: route('dashboard'),             icon: HomeIcon },
  { name: 'Pacientes',    href: route('patients.index'),        icon: UsersIcon },
  { name: 'Citas',        href: route('appointments.index'),    icon: CalendarIcon },
  { name: 'Facturación',  href: route('invoices.index'),        icon: CreditCardIcon },
  { name: 'Inventario',   href: route('inventory.index'),       icon: ArchiveBoxIcon },
  { name: 'Reportes',     href: route('reports.index'),         icon: ChartBarIcon },
  { name: 'WhatsApp',    href: route('whatsapp.inbox'),        icon: ChatBubbleLeftRightIcon, waOnly: true },
  { name: 'Tratamientos', href: route('treatments.index'),        icon: BeakerIcon,                adminOnly: true },
  { name: 'Diagnósticos', href: route('diagnosis-catalog.index'), icon: ClipboardDocumentListIcon, adminOnly: true },
  { name: 'Personal',     href: route('staff.index'),             icon: UserGroupIcon,             adminOnly: true },
  { name: 'Importar',     href: route('import.index'),            icon: ArrowUpTrayIcon,           adminOnly: true },
]

// Close user menu when clicking outside
const handleOutsideClick = (e) => {
  if (!e.target.closest('[data-user-menu]')) userMenuOpen.value = false
}
onMounted(() => document.addEventListener('click', handleOutsideClick))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick))

const isActive = (href) => {
  return page.url.startsWith(new URL(href, window.location.origin).pathname)
}

const flash = computed(() => page.props.flash || {})
const subscription = computed(() => page.props.subscription || {})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-cream-50 dark:bg-navy-950">

    <!-- Mobile sidebar overlay -->
    <Transition enter-active-class="transition-opacity duration-300" leave-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="mobileOpen" @click="mobileOpen = false" class="fixed inset-0 z-40 bg-navy-950/60 backdrop-blur-sm lg:hidden" />
    </Transition>

    <!-- Sidebar -->
    <aside :class="[
      'fixed inset-y-0 left-0 z-50 flex flex-col bg-navy-900 transition-all duration-300 ease-in-out',
      'lg:relative lg:translate-x-0',
      sidebarOpen ? 'w-64' : 'w-16',
      mobileOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0'
    ]" class="bg-tooth-pattern">

      <!-- Logo -->
      <div class="flex h-16 items-center justify-between px-4 border-b border-white/10">
        <Link :href="route('dashboard')" class="flex items-center gap-2.5 min-w-0">
          <div class="flex-shrink-0 w-8 h-8 bg-teal-500 rounded-xl flex items-center justify-center shadow-lg">
            <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5 text-white" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3C9 3 6.5 5 5.5 8C4.5 11 5 14 5.5 17C6 19.5 7 22 9.5 22.5C12 23 12 20 12 20C12 20 12 23 14.5 22.5C17 22 18 19.5 18.5 17C19 14 19.5 11 18.5 8C17.5 5 15 3 12 3Z" />
            </svg>
          </div>
          <Transition enter-active-class="transition-all duration-200" leave-active-class="transition-all duration-200"
            enter-from-class="opacity-0 w-0" leave-to-class="opacity-0 w-0">
            <span v-if="sidebarOpen" class="font-display text-white font-semibold text-lg tracking-tight truncate">Dentarix</span>
          </Transition>
        </Link>
        <button @click="sidebarOpen = !sidebarOpen" class="hidden lg:flex p-1 rounded-lg text-navy-400 hover:text-white hover:bg-white/10 transition-colors">
          <Bars3Icon class="w-5 h-5" />
        </button>
      </div>

      <!-- Clinic Info -->
      <div v-if="sidebarOpen && clinic" class="px-4 py-3 border-b border-white/10">
        <p class="text-xs text-navy-400 uppercase tracking-widest font-semibold mb-0.5">Clínica</p>
        <p class="text-sm text-white font-medium truncate">{{ clinic.name }}</p>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        <template v-for="item in navigation" :key="item.name">
          <Link
            v-if="(!item.adminOnly || user?.role === 'admin') && (!item.waOnly || clinic?.wa_plan)"
            :href="item.href"
            :class="['sidebar-link', isActive(item.href) ? 'active' : '']"
            :title="!sidebarOpen ? item.name : undefined">
            <div class="relative flex-shrink-0">
              <component :is="item.icon" class="w-5 h-5" />
              <span v-if="item.waOnly && waUnread > 0"
                class="absolute -top-1.5 -right-1.5 w-3.5 h-3.5 rounded-full bg-teal-400 text-[9px] font-bold text-white flex items-center justify-center">
                {{ waUnread > 9 ? '9+' : waUnread }}
              </span>
            </div>
            <Transition enter-active-class="transition-all duration-200" leave-active-class="transition-all duration-200"
              enter-from-class="opacity-0 w-0" leave-to-class="opacity-0 w-0">
              <span v-if="sidebarOpen" class="truncate flex items-center gap-2">
                {{ item.name }}
                <span v-if="item.waOnly && waUnread > 0"
                  class="ml-auto text-[10px] font-bold bg-teal-500 text-white px-1.5 py-0.5 rounded-full">
                  {{ waUnread }}
                </span>
              </span>
            </Transition>
          </Link>
        </template>
      </nav>

      <!-- Bottom: Settings + User -->
      <div class="border-t border-white/10 p-2 space-y-1">
        <Link v-if="user?.role === 'admin'" :href="route('clinic.settings')"
          :class="['sidebar-link', isActive(route('clinic.settings')) ? 'active' : '']">
          <CogIcon class="flex-shrink-0 w-5 h-5" />
          <span v-if="sidebarOpen" class="truncate">Configuración</span>
        </Link>

        <div :class="['flex items-center gap-3 px-3 py-2', sidebarOpen ? '' : 'justify-center']">
          <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'U')}&background=0F1F3D&color=00BFA6`"
            class="w-8 h-8 rounded-full flex-shrink-0" :alt="user?.name" />
          <div v-if="sidebarOpen" class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ user?.name }}</p>
            <p class="text-xs text-navy-400 capitalize truncate">{{ user?.role }}</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

      <!-- Top Header -->
      <header class="flex-shrink-0 h-16 bg-white dark:bg-navy-900 border-b border-navy-100 dark:border-navy-800 flex items-center justify-between px-4 lg:px-6 gap-4 shadow-sm">
        <!-- Mobile menu button -->
        <button @click="mobileOpen = true" class="lg:hidden p-2 rounded-lg text-navy-500 hover:text-navy-900 hover:bg-navy-100">
          <Bars3Icon class="w-5 h-5" />
        </button>

        <!-- Breadcrumb / Page title -->
        <div class="flex-1 min-w-0">
          <slot name="header" />
        </div>

        <!-- Header Actions -->
        <div class="flex items-center gap-2">
          <!-- Dark mode toggle -->
          <button @click="toggleDark" class="p-2 rounded-xl text-navy-500 hover:text-navy-900 hover:bg-navy-100 dark:hover:bg-navy-800 transition-colors">
            <SunIcon v-if="isDark" class="w-5 h-5" />
            <MoonIcon v-else class="w-5 h-5" />
          </button>

          <!-- Quick Actions -->
          <Link :href="route('patients.create')" class="btn-primary hidden sm:inline-flex">
            <span class="text-lg leading-none">+</span>
            Nuevo Paciente
          </Link>

          <!-- User menu -->
          <div class="relative" data-user-menu>
            <button @click="userMenuOpen = !userMenuOpen"
              class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-navy-100 dark:hover:bg-navy-800 transition-colors">
              <img :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || 'U')}&background=0F1F3D&color=00BFA6`"
                class="w-8 h-8 rounded-full" :alt="user?.name" />
              <ChevronDownIcon class="w-3.5 h-3.5 text-navy-400 hidden sm:block transition-transform duration-200"
                :class="userMenuOpen ? 'rotate-180' : ''" />
            </button>

            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 scale-95 translate-y-1"
              enter-to-class="opacity-100 scale-100 translate-y-0"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95">
              <div v-if="userMenuOpen"
                class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-navy-900 rounded-2xl shadow-card-lg border border-navy-100 dark:border-navy-800 overflow-hidden z-50">
                <!-- User info -->
                <div class="px-4 py-3 border-b border-navy-100 dark:border-navy-800">
                  <p class="text-sm font-semibold text-navy-900 dark:text-white truncate">{{ user?.name }}</p>
                  <p class="text-xs text-navy-400 capitalize">{{ user?.role }}</p>
                </div>
                <!-- Menu items -->
                <div class="p-1.5">
                  <Link :href="route('profile.edit')"
                    @click="userMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-navy-700 dark:text-navy-300 hover:bg-navy-50 dark:hover:bg-navy-800 transition-colors">
                    <UserCircleIcon class="w-4 h-4 text-navy-400" />
                    Mi Perfil
                  </Link>
                  <Link v-if="user?.role === 'admin'" :href="route('clinic.settings')"
                    @click="userMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-navy-700 dark:text-navy-300 hover:bg-navy-50 dark:hover:bg-navy-800 transition-colors">
                    <CogIcon class="w-4 h-4 text-navy-400" />
                    Configuración de Clínica
                  </Link>
                  <Link v-if="user?.role === 'admin'" :href="route('subscription.manage')"
                    @click="userMenuOpen = false"
                    class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm text-navy-700 dark:text-navy-300 hover:bg-navy-50 dark:hover:bg-navy-800 transition-colors">
                    <CreditCardIcon class="w-4 h-4 text-navy-400" />
                    Suscripción y Facturación
                  </Link>
                </div>
                <!-- Logout -->
                <div class="p-1.5 border-t border-navy-100 dark:border-navy-800">
                  <Link :href="route('logout')" method="post" as="button"
                    class="flex items-center gap-3 w-full px-3 py-2 rounded-xl text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <ArrowRightOnRectangleIcon class="w-4 h-4" />
                    Cerrar Sesión
                  </Link>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </header>

      <!-- Trial Banner -->
      <div v-if="subscription.on_trial && !subscription.subscribed" class="px-4 lg:px-6 pt-4">
        <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl text-sm"
          style="background:rgba(0,191,166,0.1); border:1px solid rgba(0,191,166,0.3); color:#0F1F3D">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" style="color:#00BFA6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>
              Período de prueba: <strong>{{ subscription.trial_days_left }} días restantes</strong>
              (vence el {{ subscription.trial_ends_at }})
            </span>
          </div>
          <Link :href="route('subscription.checkout')"
            class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-bold text-white"
            style="background:#00BFA6">
            Activar plan →
          </Link>
        </div>
      </div>

      <!-- Flash Messages -->
      <div v-if="flash.success || flash.error" class="px-4 lg:px-6 pt-4">
        <div v-if="flash.success" class="flex items-center gap-2 px-4 py-3 bg-teal-50 border border-teal-200 text-teal-800 rounded-xl text-sm animate-slide-up">
          <svg class="w-4 h-4 text-teal-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
          {{ flash.success }}
        </div>
        <div v-if="flash.error" class="flex items-center gap-2 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm animate-slide-up">
          <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
          {{ flash.error }}
        </div>
      </div>

      <!-- Page Content -->
      <main class="flex-1 overflow-y-auto p-4 lg:p-6">
        <div class="animate-fade-in">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>
