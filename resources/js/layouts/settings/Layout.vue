<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Palette, ShieldCheck, UserRound } from 'lucide-vue-next';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    { title: 'Profile', href: editProfile(), icon: UserRound },
    { title: 'Security', href: editSecurity(), icon: ShieldCheck },
    { title: 'Appearance', href: editAppearance(), icon: Palette },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="mx-auto w-full max-w-5xl px-4 py-10 md:px-7 lg:py-14">
        <!-- Masthead -->
        <header class="border-b border-[hsl(var(--ds-line))] pb-6">
            <p
                class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
            >
                Account
            </p>
            <h1
                class="font-display mt-2 text-5xl leading-[0.95] tracking-tight text-[hsl(var(--ds-ink))] md:text-6xl"
            >
                Settings
            </h1>
            <p class="mt-3 max-w-xl text-sm text-[hsl(var(--ds-ink-soft))]">
                Manage how your dispatch desk is addressed — your profile,
                security credentials, and the look of the workspace.
            </p>
        </header>

        <div class="mt-8 flex flex-col gap-10 lg:flex-row lg:gap-14">
            <!-- Tab rail -->
            <aside class="lg:w-56 lg:shrink-0">
                <nav
                    class="flex gap-1 overflow-x-auto lg:flex-col lg:gap-0.5"
                    aria-label="Settings"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="group relative flex shrink-0 items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm font-medium transition-colors"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] shadow-[0_1px_2px_hsl(24_16%_13%/0.06)]'
                                : 'text-[hsl(var(--ds-ink-soft))] hover:bg-[hsl(var(--ds-panel)/0.6)] hover:text-[hsl(var(--ds-ink))]'
                        "
                    >
                        <span
                            v-if="isCurrentOrParentUrl(item.href)"
                            class="absolute top-1/2 left-0 hidden h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-[hsl(var(--ds-accent))] lg:block"
                        />
                        <component
                            :is="item.icon"
                            class="size-[18px]"
                            :class="
                                isCurrentOrParentUrl(item.href)
                                    ? 'text-[hsl(var(--ds-accent))]'
                                    : 'text-[hsl(var(--ds-ink-faint))] group-hover:text-[hsl(var(--ds-ink-soft))]'
                            "
                        />
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <!-- Content -->
            <section class="min-w-0 flex-1 space-y-12">
                <slot />
            </section>
        </div>
    </div>
</template>
