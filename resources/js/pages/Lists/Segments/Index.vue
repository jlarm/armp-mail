<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Search, Trash2, Users } from 'lucide-vue-next';
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
    create as createSegmentRoute,
    destroy as destroySegmentRoute,
    edit as editSegmentRoute,
    index as segmentsRoute,
} from '@/routes/lists/segments';

type Condition = {
    type: string;
    comparison?: string;
    attribute?: string;
    value?: unknown;
};

type SegmentRow = {
    id: number;
    name: string;
    match: string;
    conditions: Condition[];
    population: number;
    created_at: string | null;
};

const props = defineProps<{
    list: { name: string; slug: string };
    segments: SegmentRow[];
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Lists', href: listsRoute() }],
    },
});

const TYPE_LABELS: Record<string, string> = {
    clicked_automation_mail: 'clicked automation mail link',
    clicked_campaign: 'clicked campaign link',
    opened_automation_mail: 'opened automation mail',
    opened_campaign: 'opened campaign',
    received_campaign: 'received campaign',
    engagement: 'engagement',
    attribute: 'attribute',
    email: 'email',
    subscribed_at: 'subscribed at',
    not_in_list: 'not in list',
    tags: 'tags',
};

const conditionSummary = (segment: SegmentRow) => {
    const joiner = segment.match === 'any' ? ' or ' : ' and ';

    return segment.conditions
        .map((condition) => TYPE_LABELS[condition.type] ?? condition.type)
        .join(joiner);
};

const search = ref(props.filters.search);

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get(
            segmentsRoute(props.list.slug).url,
            value ? { search: value } : {},
            {
                only: ['segments', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 300);
});

const numberFormatter = new Intl.NumberFormat();
const formatCount = (value: number) => numberFormatter.format(value);

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

const segmentToDelete = ref<SegmentRow | null>(null);
const deleting = ref(false);

const confirmDelete = () => {
    if (segmentToDelete.value === null) {
        return;
    }

    router.delete(
        destroySegmentRoute({
            list: props.list.slug,
            segment: segmentToDelete.value.id,
        }).url,
        {
            preserveScroll: true,
            onStart: () => (deleting.value = true),
            onFinish: () => {
                deleting.value = false;
                segmentToDelete.value = null;
            },
        },
    );
};
</script>

<template>
    <Head :title="`${list.name} segments`" />

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
        <header
            class="mt-4 flex flex-col gap-5 border-b border-[hsl(var(--ds-line))] pb-6 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                >
                    {{ list.name }}
                </p>
                <h1
                    class="font-display mt-1 text-4xl leading-[1.05] tracking-tight text-[hsl(var(--ds-ink))] md:text-5xl"
                >
                    Segments
                </h1>
                <p class="mt-3 max-w-xl text-sm text-[hsl(var(--ds-ink-soft))]">
                    A segment is a group of conditions that can be targeted by a
                    campaign.
                </p>
            </div>

            <Button
                as-child
                class="h-11 shrink-0 bg-[hsl(var(--ds-accent))] px-5 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
            >
                <Link :href="createSegmentRoute(list.slug)">
                    <Plus class="size-4" />
                    New segment
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
                placeholder="Search segments…"
                aria-label="Search segments"
            />
        </div>

        <!-- Empty -->
        <div
            v-if="!segments.length"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-16 text-center"
        >
            <span
                class="grid size-14 place-items-center rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink-faint))]"
            >
                <Users class="size-6" />
            </span>
            <h2 class="font-display mt-4 text-2xl text-[hsl(var(--ds-ink))]">
                {{
                    filters.search ? 'No matching segments' : 'No segments yet'
                }}
            </h2>
            <p class="mt-1 max-w-sm text-sm text-[hsl(var(--ds-ink-soft))]">
                A segment is a group of conditions that can be targeted by an
                email campaign.
            </p>
        </div>

        <!-- Ledger -->
        <ul
            v-else
            class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
        >
            <li
                v-for="segment in segments"
                :key="segment.id"
                class="flex items-center gap-4 px-5 py-4"
            >
                <Link
                    :href="
                        editSegmentRoute({
                            list: list.slug,
                            segment: segment.id,
                        })
                    "
                    class="min-w-0 flex-1"
                >
                    <p
                        class="font-display truncate text-lg leading-tight text-[hsl(var(--ds-ink))] transition-colors hover:text-[hsl(var(--ds-accent-ink))]"
                    >
                        {{ segment.name }}
                    </p>
                    <p
                        class="mt-0.5 truncate text-xs text-[hsl(var(--ds-ink-faint))]"
                    >
                        {{ conditionSummary(segment) }}
                    </p>
                </Link>

                <div class="shrink-0 text-right">
                    <span
                        class="font-display flex items-center justify-end gap-1.5 text-xl leading-none text-[hsl(var(--ds-ink))]"
                    >
                        <Users class="size-4 text-[hsl(var(--ds-ink-faint))]" />
                        {{ formatCount(segment.population) }}
                    </span>
                    <span
                        class="text-[10px] font-semibold tracking-[0.18em] text-[hsl(var(--ds-ink-faint))] uppercase"
                        >population</span
                    >
                </div>

                <span
                    class="hidden w-24 shrink-0 text-right text-xs text-[hsl(var(--ds-ink-faint))] sm:block"
                >
                    {{ formatDate(segment.created_at) }}
                </span>

                <Button
                    variant="ghost"
                    class="size-9 shrink-0 text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-accent))]"
                    :aria-label="`Delete segment ${segment.name}`"
                    @click="segmentToDelete = segment"
                >
                    <Trash2 class="size-4" />
                </Button>
            </li>
        </ul>

        <!-- Delete confirm -->
        <Dialog
            :open="segmentToDelete !== null"
            @update:open="(open) => !open && (segmentToDelete = null)"
        >
            <DialogContent>
                <DialogHeader class="space-y-2">
                    <DialogTitle
                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                    >
                        Delete segment
                    </DialogTitle>
                    <DialogDescription>
                        Delete
                        <span class="font-medium">{{
                            segmentToDelete?.name
                        }}</span
                        >? Subscribers and their tags are not affected.
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
                        data-test="delete-segment-button"
                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                        @click="confirmDelete"
                    >
                        Delete segment
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
