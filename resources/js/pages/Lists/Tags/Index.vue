<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronLeft,
    ChevronRight,
    Search,
    Tag,
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
import { index as listsRoute, show as showRoute } from '@/routes/lists';
import {
    destroy as destroyTagRoute,
    index as tagsRoute,
} from '@/routes/lists/tags';

type TagRow = { name: string; subscribers_count: number };

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    list: { name: string; slug: string };
    tags: Paginated<TagRow>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Lists',
                href: listsRoute(),
            },
        ],
    },
});

const search = ref(props.filters.search);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get(
            tagsRoute(props.list.slug).url,
            value ? { search: value } : {},
            {
                only: ['tags', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 300);
});

const numberFormatter = new Intl.NumberFormat();
const formatCount = (value: number) => numberFormatter.format(value);

const tagToDelete = ref<string | null>(null);
const deleting = ref(false);

const confirmDelete = () => {
    if (tagToDelete.value === null) {
        return;
    }

    router.delete(destroyTagRoute(props.list.slug).url, {
        data: { tag: tagToDelete.value },
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            tagToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head :title="`${list.name} tags`" />

    <div class="mx-auto w-full max-w-4xl px-4 py-10 md:px-7 lg:py-14">
        <!-- Back -->
        <Link
            :href="showRoute(list.slug)"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-ink))]"
        >
            <ArrowLeft class="size-4" />
            {{ list.name }}
        </Link>

        <!-- Masthead -->
        <header class="mt-4 border-b border-[hsl(var(--ds-line))] pb-6">
            <p
                class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
            >
                {{ list.name }}
            </p>
            <h1
                class="font-display mt-1 text-4xl leading-[1.05] tracking-tight text-[hsl(var(--ds-ink))] md:text-5xl"
            >
                Tags
            </h1>
            <p class="mt-3 max-w-xl text-sm text-[hsl(var(--ds-ink-soft))]">
                Tags applied to this list's subscribers. Deleting a tag removes
                it from every subscriber it's on.
            </p>
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
                placeholder="Search tags…"
                aria-label="Search tags"
            />
        </div>

        <!-- Empty -->
        <div
            v-if="!tags.data.length"
            class="rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-12 text-center text-sm text-[hsl(var(--ds-ink-soft))]"
        >
            {{
                filters.search
                    ? 'No tags match your search.'
                    : 'No tags on this list yet.'
            }}
        </div>

        <!-- Ledger -->
        <ul
            v-else
            class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
        >
            <li
                v-for="tag in tags.data"
                :key="tag.name"
                class="flex items-center gap-4 px-5 py-3.5"
            >
                <span
                    class="grid size-9 shrink-0 place-items-center rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-faint))]"
                >
                    <Tag class="size-4" />
                </span>
                <span
                    class="min-w-0 flex-1 truncate text-sm font-medium text-[hsl(var(--ds-ink))]"
                >
                    {{ tag.name }}
                </span>
                <span
                    class="font-display shrink-0 text-lg text-[hsl(var(--ds-ink))]"
                >
                    {{ formatCount(tag.subscribers_count) }}
                    <span
                        class="ml-1 text-[10px] font-semibold tracking-[0.18em] text-[hsl(var(--ds-ink-faint))] uppercase"
                        >subs</span
                    >
                </span>
                <Button
                    variant="ghost"
                    class="size-9 shrink-0 text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-accent))]"
                    :aria-label="`Delete tag ${tag.name}`"
                    @click="tagToDelete = tag.name"
                >
                    <Trash2 class="size-4" />
                </Button>
            </li>
        </ul>

        <!-- Pagination -->
        <nav
            v-if="tags.last_page > 1"
            class="mt-4 flex items-center justify-between gap-4"
            aria-label="Pagination"
        >
            <p class="text-xs text-[hsl(var(--ds-ink-faint))]">
                Page {{ tags.current_page }} of {{ tags.last_page }}
            </p>
            <div class="flex items-center gap-2">
                <Button
                    as-child
                    variant="outline"
                    :disabled="!tags.prev_page_url"
                    class="h-9 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                >
                    <Link
                        v-if="tags.prev_page_url"
                        :href="tags.prev_page_url"
                        preserve-scroll
                        preserve-state
                        :only="['tags', 'filters']"
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
                    :disabled="!tags.next_page_url"
                    class="h-9 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                >
                    <Link
                        v-if="tags.next_page_url"
                        :href="tags.next_page_url"
                        preserve-scroll
                        preserve-state
                        :only="['tags', 'filters']"
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
            :open="tagToDelete !== null"
            @update:open="(open) => !open && (tagToDelete = null)"
        >
            <DialogContent>
                <DialogHeader class="space-y-2">
                    <DialogTitle
                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                    >
                        Delete tag
                    </DialogTitle>
                    <DialogDescription>
                        Remove
                        <span class="font-medium">{{ tagToDelete }}</span>
                        from every subscriber on this list. This cannot be
                        undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        :disabled="deleting"
                        data-test="delete-tag-button"
                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                        @click="confirmDelete"
                    >
                        Delete tag
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
