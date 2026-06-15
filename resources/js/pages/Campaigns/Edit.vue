<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import BlockEditor from '@/components/BlockEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { renderEmail } from '@/lib/renderBlocks';
import type { Block } from '@/lib/renderBlocks';
import {
    destroy as destroyCampaignRoute,
    index as campaignsRoute,
    update as updateCampaignRoute,
} from '@/routes/campaigns';

type Option = { value: number; label: string };

type Campaign = {
    id: number;
    name: string;
    subject: string | null;
    from_name: string | null;
    from_email: string | null;
    reply_to_email: string | null;
    segment_id: number | null;
    template_id: number | null;
    list: string | null;
    content: Block[];
    track_opens: boolean;
    track_clicks: boolean;
    frequency: string;
    scheduled_at: string | null;
    next_run_at: string | null;
    status: string;
    editable: boolean;
    stats: {
        sent_to_count: number;
        unique_open_count: number;
        unique_click_count: number;
        bounce_count: number;
        unsubscribe_count: number;
    };
};

type Dispatch = {
    id: number;
    status: string;
    scheduled_at: string | null;
    sent_at: string | null;
    sent_to_count: number;
    unique_open_count: number;
    unique_click_count: number;
};

type TemplateOption = { value: number; label: string; html: string };

const props = defineProps<{
    campaign: Campaign;
    segments: Option[];
    frequencies: { value: string; label: string }[];
    templates: TemplateOption[];
    dispatches: Dispatch[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Campaigns', href: campaignsRoute() }],
    },
});

const labelClass =
    'text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase';

const form = useForm<{
    name: string;
    subject: string;
    from_name: string;
    from_email: string;
    reply_to_email: string;
    segment_id: string;
    template_id: string;
    content: Block[];
    track_opens: boolean;
    track_clicks: boolean;
    frequency: string;
    scheduled_at: string;
}>({
    name: props.campaign.name,
    subject: props.campaign.subject ?? '',
    from_name: props.campaign.from_name ?? '',
    from_email: props.campaign.from_email ?? '',
    reply_to_email: props.campaign.reply_to_email ?? '',
    segment_id: props.campaign.segment_id
        ? String(props.campaign.segment_id)
        : 'none',
    template_id: props.campaign.template_id
        ? String(props.campaign.template_id)
        : 'none',
    content: props.campaign.content ?? [],
    track_opens: props.campaign.track_opens,
    track_clicks: props.campaign.track_clicks,
    frequency: props.campaign.frequency,
    scheduled_at: props.campaign.scheduled_at ?? '',
});

const formatDateTime = (value: string | null) =>
    value
        ? new Date(value).toLocaleString(undefined, {
              dateStyle: 'medium',
              timeStyle: 'short',
          })
        : '—';

const selectedTemplateHtml = computed(
    () =>
        props.templates.find((t) => String(t.value) === form.template_id)?.html,
);

const renderedHtml = computed(() =>
    renderEmail(form.content, selectedTemplateHtml.value),
);

const debouncedHtml = ref(renderedHtml.value);
let previewTimer: ReturnType<typeof setTimeout> | undefined;

watch(renderedHtml, (value) => {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(() => (debouncedHtml.value = value), 250);
});

const statusLabel = props.campaign.status.replace(/^\w/, (c) =>
    c.toUpperCase(),
);

const numberFormatter = new Intl.NumberFormat();
const formatCount = (value: number) => numberFormatter.format(value);

const submit = () => {
    form.transform((data) => ({
        ...data,
        segment_id: data.segment_id === 'none' ? null : data.segment_id,
        template_id: data.template_id === 'none' ? null : data.template_id,
        html: renderEmail(data.content, selectedTemplateHtml.value),
    })).put(updateCampaignRoute(props.campaign.id).url, {
        preserveScroll: true,
    });
};

const deleteOpen = ref(false);
const destroy = () =>
    router.delete(destroyCampaignRoute(props.campaign.id).url);

const isSent = props.campaign.stats.sent_to_count > 0;

const tab = ref<'content' | 'settings' | 'sending'>('content');
const tabs = [
    { value: 'content', label: 'Content' },
    { value: 'settings', label: 'Settings' },
    { value: 'sending', label: 'Sending' },
] as const;
</script>

<template>
    <Head :title="campaign.name" />

    <div class="w-full px-4 py-10 md:px-7 lg:py-14">
        <Link
            :href="campaignsRoute()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-ink))]"
        >
            <ArrowLeft class="size-4" />
            Campaigns
        </Link>

        <header
            class="mt-4 flex flex-wrap items-end justify-between gap-4 border-b border-[hsl(var(--ds-line))] pb-6"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                >
                    {{ campaign.list ?? 'Campaign' }}
                </p>
                <h1
                    class="font-display mt-1 truncate text-4xl leading-[1.05] tracking-tight text-[hsl(var(--ds-ink))] md:text-5xl"
                >
                    {{ campaign.name }}
                </h1>
            </div>
            <span
                class="shrink-0 rounded-full border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] px-3 py-1 text-[11px] font-semibold tracking-wide text-[hsl(var(--ds-ink-soft))] uppercase"
            >
                {{ statusLabel }}
            </span>
        </header>

        <!-- Stats (once sent) -->
        <dl
            v-if="isSent"
            class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-line))] sm:grid-cols-5"
        >
            <div
                v-for="stat in [
                    { label: 'Sent', value: campaign.stats.sent_to_count },
                    { label: 'Opens', value: campaign.stats.unique_open_count },
                    {
                        label: 'Clicks',
                        value: campaign.stats.unique_click_count,
                    },
                    { label: 'Bounces', value: campaign.stats.bounce_count },
                    {
                        label: 'Unsubs',
                        value: campaign.stats.unsubscribe_count,
                    },
                ]"
                :key="stat.label"
                class="bg-[hsl(var(--ds-panel))] px-5 py-4"
            >
                <dt
                    class="text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    {{ stat.label }}
                </dt>
                <dd
                    class="font-display mt-1 text-2xl text-[hsl(var(--ds-ink))]"
                >
                    {{ formatCount(stat.value) }}
                </dd>
            </div>
        </dl>

        <p
            v-if="!campaign.editable"
            class="mt-8 rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.6)] px-4 py-3 text-sm text-[hsl(var(--ds-ink-soft))]"
        >
            This campaign is {{ campaign.status }} and can no longer be edited.
        </p>

        <!-- Sub-nav -->
        <nav
            class="mt-8 flex gap-1 border-b border-[hsl(var(--ds-line))]"
            aria-label="Campaign sections"
        >
            <button
                v-for="item in tabs"
                :key="item.value"
                type="button"
                class="relative -mb-px px-4 py-2.5 text-sm font-medium transition-colors"
                :class="
                    tab === item.value
                        ? 'text-[hsl(var(--ds-ink))]'
                        : 'text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-ink-soft))]'
                "
                @click="tab = item.value"
            >
                {{ item.label }}
                <span
                    v-if="tab === item.value"
                    class="absolute inset-x-2 -bottom-px h-0.5 rounded-full bg-[hsl(var(--ds-accent))]"
                />
            </button>
        </nav>

        <form class="mt-6 space-y-6" @submit.prevent="submit">
            <fieldset :disabled="!campaign.editable" class="space-y-6">
                <!-- Settings -->
                <div
                    v-show="tab === 'settings'"
                    class="grid items-start gap-4 sm:grid-cols-2"
                >
                    <div class="grid gap-2">
                        <Label for="name" :class="labelClass"
                            >Name
                            <span class="text-[hsl(var(--ds-accent))]"
                                >*</span
                            ></Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            class="h-11"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="subject" :class="labelClass">Subject</Label>
                        <Input
                            id="subject"
                            v-model="form.subject"
                            class="h-11"
                        />
                        <InputError :message="form.errors.subject" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="from_name" :class="labelClass"
                            >From name</Label
                        >
                        <Input
                            id="from_name"
                            v-model="form.from_name"
                            class="h-11"
                        />
                        <InputError :message="form.errors.from_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="from_email" :class="labelClass"
                            >From email</Label
                        >
                        <Input
                            id="from_email"
                            v-model="form.from_email"
                            type="email"
                            class="h-11"
                        />
                        <InputError :message="form.errors.from_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="reply_to_email" :class="labelClass"
                            >Reply-to</Label
                        >
                        <Input
                            id="reply_to_email"
                            v-model="form.reply_to_email"
                            type="email"
                            class="h-11"
                        />
                        <InputError :message="form.errors.reply_to_email" />
                    </div>
                    <div class="grid gap-2">
                        <Label :class="labelClass">Segment</Label>
                        <Select v-model="form.segment_id">
                            <SelectTrigger
                                class="!h-11 w-full border-[hsl(var(--ds-line))]"
                            >
                                <SelectValue placeholder="Whole list" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Whole list</SelectItem>
                                <SelectItem
                                    v-for="option in segments"
                                    :key="option.value"
                                    :value="String(option.value)"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.segment_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label :class="labelClass">Template</Label>
                        <Select v-model="form.template_id">
                            <SelectTrigger
                                class="!h-11 w-full border-[hsl(var(--ds-line))]"
                            >
                                <SelectValue placeholder="No template" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none"
                                    >No template</SelectItem
                                >
                                <SelectItem
                                    v-for="option in templates"
                                    :key="option.value"
                                    :value="String(option.value)"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-xs text-[hsl(var(--ds-ink-faint))]">
                            Wraps your content in the template's layout.
                        </p>
                        <InputError :message="form.errors.template_id" />
                    </div>
                </div>

                <!-- Content + preview -->
                <div
                    v-show="tab === 'content'"
                    class="grid gap-6 lg:grid-cols-2"
                >
                    <div class="space-y-3">
                        <div class="flex h-8 items-center">
                            <Label :class="labelClass">Content</Label>
                        </div>
                        <BlockEditor v-model="form.content" />
                        <InputError :message="form.errors.content" />
                    </div>
                    <div class="space-y-3 lg:sticky lg:top-24 lg:self-start">
                        <div class="flex h-8 items-center">
                            <Label :class="labelClass">Preview</Label>
                        </div>
                        <div
                            class="overflow-hidden rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] p-3"
                        >
                            <iframe
                                :srcdoc="debouncedHtml"
                                sandbox=""
                                title="Campaign preview"
                                class="h-[60vh] w-full rounded-lg border border-[hsl(var(--ds-line))] bg-white"
                            ></iframe>
                        </div>
                    </div>
                </div>

                <!-- Schedule + tracking -->
                <div v-show="tab === 'sending'" class="space-y-6">
                    <div class="grid items-start gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label :class="labelClass">Frequency</Label>
                            <Select v-model="form.frequency">
                                <SelectTrigger
                                    class="!h-11 w-full border-[hsl(var(--ds-line))]"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in frequencies"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.frequency" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="scheduled_at" :class="labelClass">{{
                                form.frequency === 'once'
                                    ? 'Send at'
                                    : 'First send'
                            }}</Label>
                            <Input
                                id="scheduled_at"
                                v-model="form.scheduled_at"
                                type="datetime-local"
                                class="h-11"
                            />
                            <p class="text-xs text-[hsl(var(--ds-ink-faint))]">
                                <template v-if="campaign.next_run_at">
                                    Next send:
                                    {{ formatDateTime(campaign.next_run_at) }}
                                </template>
                                <template v-else>
                                    Leave empty to keep as a draft.
                                </template>
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <Label :class="labelClass">Tracking</Label>
                        <label
                            class="flex items-center gap-3 text-sm text-[hsl(var(--ds-ink))]"
                        >
                            <Checkbox v-model="form.track_opens" />
                            Track opens
                        </label>
                        <label
                            class="flex items-center gap-3 text-sm text-[hsl(var(--ds-ink))]"
                        >
                            <Checkbox v-model="form.track_clicks" />
                            Track clicks
                        </label>
                    </div>

                    <!-- Send history -->
                    <div class="space-y-2">
                        <Label :class="labelClass">Send history</Label>
                        <div
                            v-if="!dispatches.length"
                            class="rounded-xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-4 py-6 text-center text-sm text-[hsl(var(--ds-ink-faint))]"
                        >
                            Not sent yet. Each scheduled send will appear here
                            with its own open and click stats.
                        </div>
                        <ul
                            v-else
                            class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
                        >
                            <li
                                v-for="dispatch in dispatches"
                                :key="dispatch.id"
                                class="flex items-center gap-4 px-4 py-3 text-sm"
                            >
                                <span class="flex-1 text-[hsl(var(--ds-ink))]">
                                    {{
                                        formatDateTime(
                                            dispatch.sent_at ??
                                                dispatch.scheduled_at,
                                        )
                                    }}
                                </span>
                                <span class="text-[hsl(var(--ds-ink-soft))]">
                                    {{ formatCount(dispatch.sent_to_count) }}
                                    sent
                                </span>
                                <span class="text-[hsl(var(--ds-ink-faint))]">
                                    {{
                                        formatCount(dispatch.unique_open_count)
                                    }}
                                    opens ·
                                    {{
                                        formatCount(dispatch.unique_click_count)
                                    }}
                                    clicks
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </fieldset>

            <!-- Actions -->
            <div
                class="flex flex-wrap items-center gap-3 border-t border-[hsl(var(--ds-line))] pt-6"
            >
                <Button
                    v-if="campaign.editable"
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-campaign-button"
                    class="h-11 bg-[hsl(var(--ds-accent))] px-6 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                >
                    Save campaign
                </Button>

                <Dialog v-model:open="deleteOpen">
                    <DialogTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            class="ml-auto h-11 text-[hsl(var(--ds-accent))] hover:bg-[hsl(var(--ds-accent)/0.08)] hover:text-[hsl(var(--ds-accent-ink))]"
                        >
                            <Trash2 class="size-4" />
                            Delete
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader class="space-y-2">
                            <DialogTitle
                                class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                                >Delete campaign</DialogTitle
                            >
                            <DialogDescription>
                                Delete
                                <span class="font-medium">{{
                                    campaign.name
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
                                data-test="delete-campaign-button"
                                class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                                @click="destroy"
                            >
                                Delete campaign
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </form>
    </div>
</template>
