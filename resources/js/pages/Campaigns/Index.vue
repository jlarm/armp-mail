<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    Plus,
    Search,
    Send,
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
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    create as createCampaignRoute,
    destroy as destroyCampaignRoute,
    edit as editCampaignRoute,
    index as campaignsRoute,
} from '@/routes/campaigns';

type CampaignRow = {
    id: number;
    name: string;
    subject: string | null;
    status: string;
    list: string | null;
    sent_to_count: number;
    unique_open_count: number;
    scheduled_at: string | null;
    sent_at: string | null;
    updated_at: string | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    campaigns: Paginated<CampaignRow>;
    filters: { search: string; status: string | null };
    statuses: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Campaigns', href: campaignsRoute() }],
    },
});

const search = ref(props.filters.search);
const status = ref(props.filters.status ?? 'all');

const applyFilters = () => {
    const params: Record<string, string> = {};

    if (search.value) {
        params.search = search.value;
    }

    if (status.value !== 'all') {
        params.status = status.value;
    }

    router.get(campaignsRoute().url, params, {
        only: ['campaigns', 'filters'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(status, applyFilters);

const statusClasses: Record<string, string> = {
    draft: 'border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-soft))]',
    sending:
        'border-[hsl(var(--ds-accent)/0.3)] bg-[hsl(var(--ds-accent)/0.08)] text-[hsl(var(--ds-accent-ink))]',
    sent: 'border-[hsl(var(--ds-accent)/0.3)] bg-[hsl(var(--ds-accent)/0.12)] text-[hsl(var(--ds-accent-ink))]',
    paused: 'border-[hsl(var(--ds-gold)/0.4)] bg-[hsl(var(--ds-gold)/0.12)] text-[hsl(var(--ds-gold))]',
    cancelled:
        'border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-faint))]',
};

const statusLabel = (value: string) =>
    props.statuses.find((option) => option.value === value)?.label ?? value;

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

const campaignToDelete = ref<CampaignRow | null>(null);
const deleting = ref(false);

const confirmDelete = () => {
    if (campaignToDelete.value === null) {
        return;
    }

    router.delete(destroyCampaignRoute(campaignToDelete.value.id).url, {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            campaignToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Campaigns" />

    <div class="mx-auto w-full max-w-5xl px-4 py-10 md:px-7 lg:py-14">
        <!-- Masthead -->
        <header
            class="flex flex-col gap-5 border-b border-[hsl(var(--ds-line))] pb-6 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                >
                    Dispatch
                </p>
                <h1
                    class="font-display mt-1 text-5xl leading-[1.02] tracking-tight text-[hsl(var(--ds-ink))] md:text-6xl"
                >
                    Campaigns
                </h1>
                <p class="mt-3 max-w-xl text-sm text-[hsl(var(--ds-ink-soft))]">
                    One-off broadcasts sent to a list or segment.
                </p>
            </div>

            <Button
                as-child
                class="h-11 shrink-0 bg-[hsl(var(--ds-accent))] px-5 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
            >
                <Link :href="createCampaignRoute()">
                    <Plus class="size-4" />
                    New campaign
                </Link>
            </Button>
        </header>

        <!-- Filters -->
        <div class="mt-8 mb-4 flex flex-col gap-2 sm:flex-row">
            <div class="relative flex-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[hsl(var(--ds-ink-faint))]"
                />
                <Input
                    v-model="search"
                    type="search"
                    class="h-11 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] pl-9"
                    placeholder="Search campaigns…"
                    aria-label="Search campaigns"
                />
            </div>
            <Select v-model="status">
                <SelectTrigger
                    class="!h-11 w-full border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] sm:w-48"
                    aria-label="Filter by status"
                >
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Empty -->
        <div
            v-if="!campaigns.data.length"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-16 text-center"
        >
            <span
                class="grid size-14 place-items-center rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink-faint))]"
            >
                <Send class="size-6" />
            </span>
            <h2 class="font-display mt-4 text-2xl text-[hsl(var(--ds-ink))]">
                {{
                    filters.search || filters.status
                        ? 'No matching campaigns'
                        : 'No campaigns yet'
                }}
            </h2>
            <p class="mt-1 max-w-sm text-sm text-[hsl(var(--ds-ink-soft))]">
                Create a campaign to broadcast to a list.
            </p>
        </div>

        <!-- Ledger -->
        <ul
            v-else
            class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
        >
            <li
                v-for="campaign in campaigns.data"
                :key="campaign.id"
                class="flex items-center gap-4 px-5 py-4"
            >
                <div class="min-w-0 flex-1">
                    <Link
                        :href="editCampaignRoute(campaign.id)"
                        class="font-display block truncate text-lg leading-tight text-[hsl(var(--ds-ink))] transition-colors hover:text-[hsl(var(--ds-accent-ink))]"
                    >
                        {{ campaign.name }}
                    </Link>
                    <p
                        class="mt-0.5 truncate text-xs text-[hsl(var(--ds-ink-faint))]"
                    >
                        {{ campaign.list ?? 'No list' }}
                        <template v-if="campaign.subject">
                            · {{ campaign.subject }}
                        </template>
                    </p>
                </div>

                <div
                    v-if="campaign.status === 'sent'"
                    class="hidden shrink-0 text-right sm:block"
                >
                    <span
                        class="font-display text-lg text-[hsl(var(--ds-ink))]"
                    >
                        {{ formatCount(campaign.sent_to_count) }}
                    </span>
                    <span
                        class="block text-[10px] font-semibold tracking-[0.18em] text-[hsl(var(--ds-ink-faint))] uppercase"
                        >sent</span
                    >
                </div>

                <span
                    class="shrink-0 rounded-full border px-2.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                    :class="
                        statusClasses[campaign.status] ?? statusClasses.draft
                    "
                >
                    {{ statusLabel(campaign.status) }}
                </span>

                <span
                    class="hidden w-24 shrink-0 text-right text-xs text-[hsl(var(--ds-ink-faint))] sm:block"
                >
                    {{ formatDate(campaign.sent_at ?? campaign.updated_at) }}
                </span>

                <Button
                    variant="ghost"
                    class="size-9 shrink-0 text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-accent))]"
                    :aria-label="`Delete campaign ${campaign.name}`"
                    @click="campaignToDelete = campaign"
                >
                    <Trash2 class="size-4" />
                </Button>
            </li>
        </ul>

        <!-- Pagination -->
        <nav
            v-if="campaigns.last_page > 1"
            class="mt-4 flex items-center justify-between gap-4"
            aria-label="Pagination"
        >
            <p class="text-xs text-[hsl(var(--ds-ink-faint))]">
                Page {{ campaigns.current_page }} of {{ campaigns.last_page }}
            </p>
            <div class="flex items-center gap-2">
                <Button
                    as-child
                    variant="outline"
                    :disabled="!campaigns.prev_page_url"
                    class="h-9 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                >
                    <Link
                        v-if="campaigns.prev_page_url"
                        :href="campaigns.prev_page_url"
                        preserve-scroll
                        preserve-state
                        :only="['campaigns', 'filters']"
                    >
                        <ChevronLeft class="size-4" />
                        Previous
                    </Link>
                    <span v-else><ChevronLeft class="size-4" />Previous</span>
                </Button>
                <Button
                    as-child
                    variant="outline"
                    :disabled="!campaigns.next_page_url"
                    class="h-9 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                >
                    <Link
                        v-if="campaigns.next_page_url"
                        :href="campaigns.next_page_url"
                        preserve-scroll
                        preserve-state
                        :only="['campaigns', 'filters']"
                    >
                        Next
                        <ChevronRight class="size-4" />
                    </Link>
                    <span v-else>Next<ChevronRight class="size-4" /></span>
                </Button>
            </div>
        </nav>

        <!-- Delete confirm -->
        <Dialog
            :open="campaignToDelete !== null"
            @update:open="(open) => !open && (campaignToDelete = null)"
        >
            <DialogContent>
                <DialogHeader class="space-y-2">
                    <DialogTitle
                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                    >
                        Delete campaign
                    </DialogTitle>
                    <DialogDescription>
                        Delete
                        <span class="font-medium">{{
                            campaignToDelete?.name
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
                        data-test="delete-campaign-button"
                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                        @click="confirmDelete"
                    >
                        Delete campaign
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
