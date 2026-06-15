<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Copy,
    LayoutTemplate,
    Plus,
    Search,
    Trash2,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    create as createTemplateRoute,
    destroy as destroyTemplateRoute,
    duplicate as duplicateTemplateRoute,
    edit as editTemplateRoute,
    index as templatesRoute,
} from '@/routes/templates';

type TemplateRow = { id: number; name: string; updated_at: string | null };

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    templates: Paginated<TemplateRow>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Templates', href: templatesRoute() }],
    },
});

const search = ref(props.filters.search);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get(templatesRoute().url, value ? { search: value } : {}, {
            only: ['templates', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 300);
});

const formatDate = (value: string | null) => {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const duplicate = (template: TemplateRow) => {
    router.post(duplicateTemplateRoute(template.id).url);
};

const templateToDelete = ref<TemplateRow | null>(null);
const deleting = ref(false);

const confirmDelete = () => {
    if (templateToDelete.value === null) {
        return;
    }

    router.delete(destroyTemplateRoute(templateToDelete.value.id).url, {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            templateToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Templates" />

    <div class="mx-auto w-full max-w-4xl px-4 py-10 md:px-7 lg:py-14">
        <!-- Masthead -->
        <header
            class="flex flex-col gap-5 border-b border-[hsl(var(--ds-line))] pb-6 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                >
                    Library
                </p>
                <h1
                    class="font-display mt-1 text-5xl leading-[1.02] tracking-tight text-[hsl(var(--ds-ink))] md:text-6xl"
                >
                    Templates
                </h1>
                <p class="mt-3 max-w-xl text-sm text-[hsl(var(--ds-ink-soft))]">
                    Reusable HTML layouts to start campaigns, automations, and
                    transactional mails from.
                </p>
            </div>

            <Button
                as-child
                class="h-11 shrink-0 bg-[hsl(var(--ds-accent))] px-5 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
            >
                <Link :href="createTemplateRoute()">
                    <Plus class="size-4" />
                    New template
                </Link>
            </Button>
        </header>

        <!-- Search -->
        <div class="relative mt-8 mb-4">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[hsl(var(--ds-ink-faint))]"
            />
            <Input
                v-model="search"
                type="search"
                class="h-11 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] pl-9"
                placeholder="Search templates…"
                aria-label="Search templates"
            />
        </div>

        <!-- Empty -->
        <div
            v-if="!templates.data.length"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-16 text-center"
        >
            <span
                class="grid size-14 place-items-center rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink-faint))]"
            >
                <LayoutTemplate class="size-6" />
            </span>
            <h2 class="font-display mt-4 text-2xl text-[hsl(var(--ds-ink))]">
                {{
                    filters.search
                        ? 'No matching templates'
                        : 'No templates yet'
                }}
            </h2>
            <p class="mt-1 max-w-sm text-sm text-[hsl(var(--ds-ink-soft))]">
                Create a template to reuse across your campaigns.
            </p>
        </div>

        <!-- Ledger -->
        <ul
            v-else
            class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
        >
            <li
                v-for="template in templates.data"
                :key="template.id"
                class="flex items-center gap-4 px-5 py-4"
            >
                <span
                    class="grid size-9 shrink-0 place-items-center rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-faint))]"
                >
                    <LayoutTemplate class="size-4" />
                </span>
                <Link
                    :href="editTemplateRoute(template.id)"
                    class="font-display min-w-0 flex-1 truncate text-lg leading-tight text-[hsl(var(--ds-ink))] transition-colors hover:text-[hsl(var(--ds-accent-ink))]"
                >
                    {{ template.name }}
                </Link>

                <span
                    class="hidden w-28 shrink-0 text-right text-xs text-[hsl(var(--ds-ink-faint))] sm:block"
                >
                    {{ formatDate(template.updated_at) }}
                </span>

                <Button
                    variant="ghost"
                    class="size-9 shrink-0 text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-ink))]"
                    :aria-label="`Duplicate template ${template.name}`"
                    @click="duplicate(template)"
                >
                    <Copy class="size-4" />
                </Button>
                <Button
                    variant="ghost"
                    class="size-9 shrink-0 text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-accent))]"
                    :aria-label="`Delete template ${template.name}`"
                    @click="templateToDelete = template"
                >
                    <Trash2 class="size-4" />
                </Button>
            </li>
        </ul>

        <!-- Pagination -->
        <nav
            v-if="templates.last_page > 1"
            class="mt-4 flex items-center justify-between gap-4"
            aria-label="Pagination"
        >
            <p class="text-xs text-[hsl(var(--ds-ink-faint))]">
                Page {{ templates.current_page }} of {{ templates.last_page }}
            </p>
            <div class="flex items-center gap-2">
                <Button
                    as-child
                    variant="outline"
                    :disabled="!templates.prev_page_url"
                    class="h-9 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                >
                    <Link
                        v-if="templates.prev_page_url"
                        :href="templates.prev_page_url"
                        preserve-scroll
                        preserve-state
                        :only="['templates', 'filters']"
                    >
                        <ChevronLeft class="size-4" />
                        Previous
                    </Link>
                    <span v-else>
                        <ChevronLeft class="size-4" />
                        Previous
                    </span>
                </Button>
                <Button
                    as-child
                    variant="outline"
                    :disabled="!templates.next_page_url"
                    class="h-9 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                >
                    <Link
                        v-if="templates.next_page_url"
                        :href="templates.next_page_url"
                        preserve-scroll
                        preserve-state
                        :only="['templates', 'filters']"
                    >
                        Next
                        <ChevronRight class="size-4" />
                    </Link>
                    <span v-else>
                        Next
                        <ChevronRight class="size-4" />
                    </span>
                </Button>
            </div>
        </nav>

        <!-- Delete confirm -->
        <Dialog
            :open="templateToDelete !== null"
            @update:open="(open) => !open && (templateToDelete = null)"
        >
            <DialogContent>
                <DialogHeader class="space-y-2">
                    <DialogTitle
                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                    >
                        Delete template
                    </DialogTitle>
                    <DialogDescription>
                        Delete
                        <span class="font-medium">{{
                            templateToDelete?.name
                        }}</span
                        >? This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary"
                            >Cancel</Button
                        >
                    </DialogClose>
                    <Button
                        type="button"
                        :disabled="deleting"
                        data-test="delete-template-button"
                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                        @click="confirmDelete"
                    >
                        Delete template
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
