<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronsUpDown,
    Layers,
    LayoutGrid,
    LayoutTemplate,
    Mailbox,
    Menu,
    PanelLeft,
    Send,
    ShieldBan,
    Users,
    Workflow,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as lists } from '@/routes/lists';
import { index as templates } from '@/routes/templates';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

type DeskItem = NavItem & { soon?: boolean };
type DeskSection = { label: string; items: DeskItem[] };

/**
 * The "Dispatch Desk" navigation. Live destinations link out; the remaining
 * entries mirror the models already in the codebase (campaigns, automations,
 * transactional mail, subscribers, suppressions) and are flagged as upcoming
 * rather than pointing at routes that don't exist yet.
 */
const sections: DeskSection[] = [
    {
        label: 'Overview',
        items: [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }],
    },
    {
        label: 'Dispatch',
        items: [
            { title: 'Campaigns', href: dashboard(), icon: Send, soon: true },
            {
                title: 'Automations',
                href: dashboard(),
                icon: Workflow,
                soon: true,
            },
            {
                title: 'Transactional',
                href: dashboard(),
                icon: Mailbox,
                soon: true,
            },
        ],
    },
    {
        label: 'Library',
        items: [
            { title: 'Templates', href: templates(), icon: LayoutTemplate },
        ],
    },
    {
        label: 'Audience',
        items: [
            { title: 'Lists', href: lists(), icon: Users },
            { title: 'Segments', href: dashboard(), icon: Layers, soon: true },
            {
                title: 'Suppressions',
                href: dashboard(),
                icon: ShieldBan,
                soon: true,
            },
        ],
    },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const { isCurrentUrl } = useCurrentUrl();

/* ----- Rail state: persisted collapse (desktop) + slide-over (mobile) ----- */
const SIDEBAR_COOKIE = 'sidebar_state';
const collapsed = ref(false);
const mobileOpen = ref(false);
const isDesktop = ref(true);

const railCollapsed = computed(() => isDesktop.value && collapsed.value);

let mediaQuery: MediaQueryList | null = null;
let removeNavigateListener: (() => void) | null = null;

const handleMediaChange = (event: MediaQueryListEvent | MediaQueryList) => {
    isDesktop.value = event.matches;

    if (event.matches) {
        mobileOpen.value = false;
    }
};

onMounted(() => {
    collapsed.value = document.cookie.includes(`${SIDEBAR_COOKIE}=false`);

    mediaQuery = window.matchMedia('(min-width: 1024px)');
    isDesktop.value = mediaQuery.matches;
    mediaQuery.addEventListener('change', handleMediaChange);

    removeNavigateListener = router.on('navigate', () => {
        mobileOpen.value = false;
    });
});

onUnmounted(() => {
    mediaQuery?.removeEventListener('change', handleMediaChange);
    removeNavigateListener?.();
});

const toggleCollapse = () => {
    collapsed.value = !collapsed.value;
    const isOpen = !collapsed.value;
    document.cookie = `${SIDEBAR_COOKIE}=${isOpen}; path=/; max-age=${60 * 60 * 24 * 7}; SameSite=Lax`;
};
</script>

<template>
    <div
        class="relative flex min-h-svh w-full bg-[hsl(var(--ds-paper))] [font-feature-settings:'ss01'] text-[hsl(var(--ds-ink))]"
    >
        <!-- Ambient paper grain -->
        <div class="ds-grain pointer-events-none fixed inset-0 z-0"></div>

        <!-- Mobile backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-40 bg-[hsl(24_16%_8%/0.5)] backdrop-blur-sm lg:hidden"
                @click="mobileOpen = false"
            />
        </Transition>

        <!-- ===================== Sidebar rail ===================== -->
        <aside
            :class="[
                mobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
                railCollapsed ? 'lg:w-[5.5rem]' : 'lg:w-72',
            ]"
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-rail))] transition-[transform,width] duration-300 ease-[cubic-bezier(0.32,0.72,0,1)] lg:static lg:z-0"
        >
            <!-- Masthead -->
            <div
                class="flex h-[4.75rem] shrink-0 items-center gap-3 border-b border-dashed border-[hsl(var(--ds-line))] px-5"
                :class="railCollapsed && 'lg:justify-center lg:px-0'"
            >
                <Link
                    :href="dashboard()"
                    class="group flex items-center gap-3 outline-none"
                >
                    <span
                        class="relative grid size-10 shrink-0 place-items-center rounded-full border border-dashed border-[hsl(var(--ds-accent)/0.5)] bg-[hsl(var(--ds-accent)/0.08)] transition group-hover:rotate-[-6deg]"
                    >
                        <span
                            class="font-display grid size-7 place-items-center rounded-full bg-[hsl(var(--ds-accent))] text-lg leading-none text-white shadow-[0_2px_8px_-2px_hsl(var(--ds-accent)/0.7)]"
                        >
                            a
                        </span>
                    </span>
                    <span
                        v-show="!railCollapsed"
                        class="flex flex-col leading-none"
                    >
                        <span class="font-display text-2xl tracking-tight">
                            armp<span class="text-[hsl(var(--ds-accent))]"
                                >.</span
                            >mail
                        </span>
                        <span
                            class="mt-1 text-[10px] font-medium tracking-[0.28em] text-[hsl(var(--ds-ink-faint))] uppercase"
                        >
                            Dispatch Desk
                        </span>
                    </span>
                </Link>
            </div>

            <!-- Navigation -->
            <nav
                class="flex-1 space-y-7 overflow-y-auto px-3 py-6"
                aria-label="Primary"
            >
                <div v-for="section in sections" :key="section.label">
                    <p
                        class="px-3 pb-2 text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                        :class="
                            railCollapsed && 'lg:text-center lg:tracking-normal'
                        "
                    >
                        <span v-if="!railCollapsed">{{ section.label }}</span>
                        <span v-else class="hidden lg:inline">—</span>
                        <span v-if="railCollapsed" class="lg:hidden">{{
                            section.label
                        }}</span>
                    </p>

                    <ul class="space-y-0.5">
                        <li v-for="item in section.items" :key="item.title">
                            <!-- Live link -->
                            <Link
                                v-if="!item.soon"
                                :href="item.href"
                                :title="railCollapsed ? item.title : undefined"
                                class="group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                                :class="[
                                    isCurrentUrl(item.href)
                                        ? 'bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] shadow-[0_1px_2px_hsl(24_16%_13%/0.06)]'
                                        : 'text-[hsl(var(--ds-ink-soft))] hover:bg-[hsl(var(--ds-panel)/0.6)] hover:text-[hsl(var(--ds-ink))]',
                                    railCollapsed &&
                                        'lg:justify-center lg:px-0',
                                ]"
                            >
                                <span
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute top-1/2 left-0 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-[hsl(var(--ds-accent))]"
                                />
                                <component
                                    :is="item.icon"
                                    class="size-[18px] shrink-0 transition-colors"
                                    :class="
                                        isCurrentUrl(item.href)
                                            ? 'text-[hsl(var(--ds-accent))]'
                                            : 'text-[hsl(var(--ds-ink-faint))] group-hover:text-[hsl(var(--ds-ink-soft))]'
                                    "
                                />
                                <span
                                    v-show="!railCollapsed"
                                    class="truncate"
                                    >{{ item.title }}</span
                                >
                            </Link>

                            <!-- Upcoming (not yet routed) -->
                            <div
                                v-else
                                :title="
                                    railCollapsed
                                        ? `${item.title} — coming soon`
                                        : undefined
                                "
                                class="flex cursor-default items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[hsl(var(--ds-ink-faint))]"
                                :class="
                                    railCollapsed && 'lg:justify-center lg:px-0'
                                "
                            >
                                <component
                                    :is="item.icon"
                                    class="size-[18px] shrink-0 opacity-60"
                                />
                                <span
                                    v-show="!railCollapsed"
                                    class="flex flex-1 items-center justify-between truncate"
                                >
                                    {{ item.title }}
                                    <span
                                        class="ml-2 rounded-full border border-[hsl(var(--ds-line))] px-1.5 py-px text-[9px] font-semibold tracking-wider text-[hsl(var(--ds-ink-faint))] uppercase"
                                    >
                                        Soon
                                    </span>
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- User -->
            <div class="border-t border-[hsl(var(--ds-line))] p-3">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            data-test="sidebar-menu-button"
                            class="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition-colors hover:bg-[hsl(var(--ds-panel))] data-[state=open]:bg-[hsl(var(--ds-panel))]"
                            :class="
                                railCollapsed && 'lg:justify-center lg:px-0'
                            "
                        >
                            <Avatar
                                class="size-9 rounded-lg border border-[hsl(var(--ds-line))]"
                            >
                                <AvatarImage
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                />
                                <AvatarFallback
                                    class="rounded-lg bg-[hsl(var(--ds-accent)/0.12)] text-xs font-semibold text-[hsl(var(--ds-accent-ink))]"
                                >
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div
                                v-show="!railCollapsed"
                                class="grid flex-1 leading-tight"
                            >
                                <span class="truncate text-sm font-semibold">{{
                                    user.name
                                }}</span>
                                <span
                                    class="truncate text-xs text-[hsl(var(--ds-ink-faint))]"
                                    >{{ user.email }}</span
                                >
                            </div>
                            <ChevronsUpDown
                                v-show="!railCollapsed"
                                class="size-4 text-[hsl(var(--ds-ink-faint))]"
                            />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="w-60 rounded-lg"
                        :side="railCollapsed ? 'right' : 'top'"
                        align="end"
                        :side-offset="8"
                    >
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </aside>

        <!-- ===================== Main column ===================== -->
        <div class="relative z-10 flex min-w-0 flex-1 flex-col">
            <header
                class="sticky top-0 z-30 flex h-[4.75rem] shrink-0 items-center gap-3 border-b border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.8)] px-4 backdrop-blur-md md:px-7"
            >
                <!-- Mobile menu -->
                <button
                    type="button"
                    class="grid size-9 place-items-center rounded-lg border border-[hsl(var(--ds-line))] text-[hsl(var(--ds-ink-soft))] transition-colors hover:bg-[hsl(var(--ds-panel))] lg:hidden"
                    aria-label="Open navigation"
                    @click="mobileOpen = true"
                >
                    <Menu class="size-[18px]" />
                </button>

                <!-- Desktop collapse -->
                <button
                    type="button"
                    class="hidden size-9 place-items-center rounded-lg border border-[hsl(var(--ds-line))] text-[hsl(var(--ds-ink-soft))] transition-colors hover:bg-[hsl(var(--ds-panel))] lg:grid"
                    aria-label="Toggle sidebar"
                    @click="toggleCollapse"
                >
                    <PanelLeft class="size-[18px]" />
                </button>

                <!-- Breadcrumbs -->
                <nav
                    v-if="breadcrumbs.length"
                    aria-label="Breadcrumb"
                    class="flex min-w-0 items-center gap-2 text-sm"
                >
                    <template
                        v-for="(crumb, index) in breadcrumbs"
                        :key="toUrl(crumb.href ?? crumb.title)"
                    >
                        <span
                            v-if="index > 0"
                            class="text-[hsl(var(--ds-ink-faint))]"
                            aria-hidden="true"
                            >/</span
                        >
                        <Link
                            v-if="crumb.href && index < breadcrumbs.length - 1"
                            :href="crumb.href"
                            class="truncate text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-ink))]"
                        >
                            {{ crumb.title }}
                        </Link>
                        <span
                            v-else
                            class="truncate font-medium text-[hsl(var(--ds-ink))]"
                        >
                            {{ crumb.title }}
                        </span>
                    </template>
                </nav>
            </header>

            <main class="relative flex-1">
                <slot />
            </main>
        </div>

        <!-- Mobile drawer close (floats over slide-over) -->
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
        >
            <button
                v-if="mobileOpen"
                type="button"
                class="fixed top-4 right-4 z-[60] grid size-9 place-items-center rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] lg:hidden"
                aria-label="Close navigation"
                @click="mobileOpen = false"
            >
                <X class="size-[18px]" />
            </button>
        </Transition>

        <Toaster />
    </div>
</template>
