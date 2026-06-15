<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2, Users, X } from 'lucide-vue-next';
import { onMounted, reactive, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    index as segmentsRoute,
    store as storeSegmentRoute,
    update as updateSegmentRoute,
} from '@/routes/lists/segments';

type Option = { value: number; label: string };
type Condition = { _id: number; type: string; [key: string]: any };

type ExistingSegment = {
    id: number;
    name: string;
    match: string;
    conditions: Array<Record<string, any>>;
};

const props = defineProps<{
    list: { name: string; slug: string };
    options: {
        tags: string[];
        attributes: string[];
        campaigns: Option[];
        automationMails: Option[];
        lists: Option[];
    };
    segment?: ExistingSegment;
    preview?: {
        total: number;
        subscribers: { id: number; email: string; name: string | null }[];
    };
}>();

const isEdit = props.segment !== undefined;
const numberFormatter = new Intl.NumberFormat();

const TYPE_LABELS: Record<string, string> = {
    clicked_automation_mail: 'Subscriber Clicked Automation Mail Link',
    clicked_campaign: 'Subscriber Clicked Campaign Link',
    opened_automation_mail: 'Subscriber Opened Automation Mail',
    opened_campaign: 'Subscriber Opened Campaign',
    received_campaign: 'Subscriber Received Campaign',
    engagement: 'Subscriber Engagement',
    attribute: 'Subscriber Attribute',
    email: 'Subscriber Email',
    subscribed_at: 'Subscriber Subscribed At',
    not_in_list: 'Subscriber Not In List',
    tags: 'Subscriber Tags',
};

const MENU = [
    {
        heading: 'Actions',
        types: [
            'clicked_automation_mail',
            'clicked_campaign',
            'opened_automation_mail',
            'opened_campaign',
            'received_campaign',
            'engagement',
        ],
    },
    {
        heading: 'Attributes',
        types: ['attribute', 'email', 'subscribed_at', 'not_in_list'],
    },
    { heading: 'Tags', types: ['tags'] },
];

const COMPARISONS: Record<string, { value: string; label: string }[]> = {
    tags: [
        { value: 'any', label: 'Has any of' },
        { value: 'all', label: 'Has all of' },
        { value: 'none', label: 'Has none of' },
    ],
    email: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Does not equal' },
        { value: 'contains', label: 'Contains' },
        { value: 'starts_with', label: 'Starts with' },
        { value: 'ends_with', label: 'Ends with' },
    ],
    attribute: [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Does not equal' },
        { value: 'contains', label: 'Contains' },
    ],
    subscribed_at: [
        { value: 'after', label: 'After' },
        { value: 'before', label: 'Before' },
    ],
};

const ENGAGEMENT = [
    { value: 'engaged', label: 'Engaged (opened or clicked)' },
    { value: 'disengaged', label: 'Received but never engaged' },
    { value: 'never_received', label: 'Never received a mail' },
];

let nextId = 1;

const defaults = (type: string): Condition => {
    const base = { _id: nextId++, type };

    switch (type) {
        case 'tags':
            return { ...base, comparison: 'any', value: [] };
        case 'email':
            return { ...base, comparison: 'equals', value: '' };
        case 'attribute':
            return { ...base, attribute: '', comparison: 'equals', value: '' };
        case 'subscribed_at':
            return { ...base, comparison: 'after', value: '' };
        case 'engagement':
            return { ...base, value: 'engaged' };
        default:
            return { ...base, value: '' };
    }
};

const form = useForm<{ name: string; match: string; conditions: Condition[] }>({
    name: props.segment?.name ?? '',
    match: props.segment?.match ?? 'all',
    conditions: (props.segment?.conditions ?? []).map(
        (condition): Condition =>
            ({ ...condition, _id: nextId++ }) as Condition,
    ),
});

const tagInputs = reactive<Record<number, string>>({});

const addCondition = (type: string) => {
    form.conditions.push(defaults(type));
};

const removeCondition = (index: number) => {
    form.conditions.splice(index, 1);
};

const addTag = (condition: Condition) => {
    const value = (tagInputs[condition._id] ?? '').trim();
    const tags = condition.value as string[];

    if (value && !tags.includes(value)) {
        tags.push(value);
    }

    tagInputs[condition._id] = '';
};

const removeTag = (condition: Condition, index: number) => {
    (condition.value as string[]).splice(index, 1);
};

const cleanedConditions = () =>
    form.conditions.map((condition) => {
        const rest = { ...condition } as Partial<Condition>;
        delete rest._id;

        return rest;
    });

/* Live preview of matching subscribers, recomputed server-side as the
   conditions change. */
let previewTimeout: ReturnType<typeof setTimeout> | undefined;

const refreshPreview = () => {
    router.reload({
        only: ['preview'],
        data: { match: form.match, conditions: cleanedConditions() },
    });
};

const schedulePreview = () => {
    clearTimeout(previewTimeout);
    previewTimeout = setTimeout(refreshPreview, 400);
};

watch(() => [form.match, form.conditions], schedulePreview, { deep: true });
onMounted(refreshPreview);

const submit = () => {
    form.transform((data) => ({
        ...data,
        conditions: cleanedConditions(),
    }));

    if (props.segment) {
        form.put(
            updateSegmentRoute({
                list: props.list.slug,
                segment: props.segment.id,
            }).url,
        );
    } else {
        form.post(storeSegmentRoute(props.list.slug).url);
    }
};
</script>

<template>
    <Head :title="`${isEdit ? 'Edit' : 'New'} segment · ${list.name}`" />

    <div class="mx-auto w-full max-w-3xl px-4 py-10 md:px-7 lg:py-14">
        <!-- Back -->
        <Link
            :href="segmentsRoute(list.slug)"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-ink))]"
        >
            <ArrowLeft class="size-4" />
            Segments
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
                {{ isEdit ? 'Edit segment' : 'New segment' }}
            </h1>
        </header>

        <form class="mt-8 space-y-8" @submit.prevent="submit">
            <!-- Name -->
            <div class="grid gap-2">
                <Label
                    for="name"
                    class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                >
                    Name <span class="text-[hsl(var(--ds-accent))]">*</span>
                </Label>
                <Input
                    id="name"
                    v-model="form.name"
                    class="h-11"
                    required
                    autofocus
                    placeholder="e.g. Engaged owners"
                />
                <InputError :message="form.errors.name" />
            </div>

            <!-- Conditions -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <Label
                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                    >
                        Conditions
                        <span class="text-[hsl(var(--ds-accent))]">*</span>
                    </Label>
                    <Select v-model="form.match">
                        <SelectTrigger
                            class="!h-9 w-36 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-xs"
                            aria-label="Match mode"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Match all</SelectItem>
                            <SelectItem value="any">Match any</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <InputError :message="form.errors.conditions" />

                <template
                    v-for="(condition, index) in form.conditions"
                    :key="condition._id"
                >
                    <p
                        v-if="index > 0"
                        class="text-center text-[11px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                    >
                        {{ form.match === 'any' ? 'or' : 'and' }}
                    </p>

                    <div
                        class="overflow-hidden rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
                    >
                        <div
                            class="flex items-center justify-between border-b border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.6)] px-4 py-2.5"
                        >
                            <span
                                class="text-sm font-semibold text-[hsl(var(--ds-ink))]"
                            >
                                {{ TYPE_LABELS[condition.type] }}
                            </span>
                            <button
                                type="button"
                                class="text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-accent))]"
                                aria-label="Remove condition"
                                @click="removeCondition(index)"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>

                        <div
                            class="flex flex-col gap-4 p-4 sm:flex-row sm:flex-wrap sm:items-end"
                        >
                            <!-- Comparison (tags/email/attribute/subscribed_at) -->
                            <div
                                v-if="COMPARISONS[condition.type]"
                                class="grid gap-1.5 sm:w-44"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Comparison</Label
                                >
                                <Select v-model="condition.comparison">
                                    <SelectTrigger
                                        class="!h-10 border-[hsl(var(--ds-line))]"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in COMPARISONS[
                                                condition.type
                                            ]"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Attribute key -->
                            <div
                                v-if="condition.type === 'attribute'"
                                class="grid gap-1.5 sm:w-44"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Attribute</Label
                                >
                                <Input
                                    v-model="condition.attribute"
                                    list="segment-attributes"
                                    class="h-10"
                                    placeholder="e.g. role"
                                />
                            </div>

                            <!-- Value: text (email / attribute) -->
                            <div
                                v-if="
                                    condition.type === 'email' ||
                                    condition.type === 'attribute'
                                "
                                class="grid gap-1.5 sm:min-w-[12rem] sm:flex-1"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Value</Label
                                >
                                <Input
                                    v-model="condition.value"
                                    class="h-10"
                                    placeholder="Value"
                                />
                            </div>

                            <!-- Value: date -->
                            <div
                                v-else-if="condition.type === 'subscribed_at'"
                                class="grid gap-1.5 sm:w-56"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Date</Label
                                >
                                <Input
                                    v-model="condition.value"
                                    type="date"
                                    class="h-10"
                                />
                            </div>

                            <!-- Value: engagement -->
                            <div
                                v-else-if="condition.type === 'engagement'"
                                class="grid gap-1.5 sm:min-w-[16rem] sm:flex-1"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Value</Label
                                >
                                <Select v-model="condition.value">
                                    <SelectTrigger
                                        class="!h-10 border-[hsl(var(--ds-line))]"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in ENGAGEMENT"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Value: list select (not_in_list) -->
                            <div
                                v-else-if="condition.type === 'not_in_list'"
                                class="grid gap-1.5 sm:min-w-[16rem] sm:flex-1"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >List</Label
                                >
                                <Select v-model="condition.value">
                                    <SelectTrigger
                                        class="!h-10 border-[hsl(var(--ds-line))]"
                                    >
                                        <SelectValue
                                            placeholder="Choose a list"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in options.lists"
                                            :key="option.value"
                                            :value="String(option.value)"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Value: campaign select -->
                            <div
                                v-else-if="
                                    condition.type === 'received_campaign' ||
                                    condition.type === 'opened_campaign' ||
                                    condition.type === 'clicked_campaign'
                                "
                                class="grid gap-1.5 sm:min-w-[16rem] sm:flex-1"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Campaign</Label
                                >
                                <Select v-model="condition.value">
                                    <SelectTrigger
                                        class="!h-10 border-[hsl(var(--ds-line))]"
                                    >
                                        <SelectValue
                                            placeholder="Choose a campaign"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in options.campaigns"
                                            :key="option.value"
                                            :value="String(option.value)"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="!options.campaigns.length"
                                    class="text-xs text-[hsl(var(--ds-ink-faint))]"
                                >
                                    No campaigns on this list yet.
                                </p>
                            </div>

                            <!-- Value: automation mail select -->
                            <div
                                v-else-if="
                                    condition.type ===
                                        'opened_automation_mail' ||
                                    condition.type === 'clicked_automation_mail'
                                "
                                class="grid gap-1.5 sm:min-w-[16rem] sm:flex-1"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Automation mail</Label
                                >
                                <Select v-model="condition.value">
                                    <SelectTrigger
                                        class="!h-10 border-[hsl(var(--ds-line))]"
                                    >
                                        <SelectValue
                                            placeholder="Choose a mail"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in options.automationMails"
                                            :key="option.value"
                                            :value="String(option.value)"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p
                                    v-if="!options.automationMails.length"
                                    class="text-xs text-[hsl(var(--ds-ink-faint))]"
                                >
                                    No automation mails on this list yet.
                                </p>
                            </div>

                            <!-- Value: tags chip input -->
                            <div
                                v-else-if="condition.type === 'tags'"
                                class="grid gap-1.5 sm:min-w-[16rem] sm:flex-1"
                            >
                                <Label
                                    class="text-xs text-[hsl(var(--ds-ink-soft))]"
                                    >Tags</Label
                                >
                                <div
                                    class="flex flex-wrap items-center gap-2 rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.5)] p-2"
                                >
                                    <span
                                        v-for="(
                                            tag, tagIndex
                                        ) in condition.value"
                                        :key="tag"
                                        class="inline-flex items-center gap-1.5 rounded-md bg-[hsl(var(--ds-accent)/0.1)] py-1 pr-1 pl-2.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))]"
                                    >
                                        {{ tag }}
                                        <button
                                            type="button"
                                            class="grid size-4 place-items-center rounded hover:bg-[hsl(var(--ds-accent)/0.2)]"
                                            aria-label="Remove tag"
                                            @click="
                                                removeTag(condition, tagIndex)
                                            "
                                        >
                                            <X class="size-3" />
                                        </button>
                                    </span>
                                    <input
                                        v-model="tagInputs[condition._id]"
                                        list="segment-tags"
                                        type="text"
                                        class="min-w-32 flex-1 bg-transparent px-1.5 py-1 text-sm text-[hsl(var(--ds-ink))] outline-none placeholder:text-[hsl(var(--ds-ink-faint))]"
                                        placeholder="Add a tag and press Enter"
                                        @keydown.enter.prevent="
                                            addTag(condition)
                                        "
                                        @blur="addTag(condition)"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Add condition -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-10 w-full border-dashed border-[hsl(var(--ds-line))] bg-transparent font-semibold text-[hsl(var(--ds-ink-soft))] hover:bg-[hsl(var(--ds-accent)/0.06)] hover:text-[hsl(var(--ds-ink))]"
                        >
                            <Plus class="size-4" />
                            Add condition
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-72" align="start">
                        <template
                            v-for="(group, groupIndex) in MENU"
                            :key="group.heading"
                        >
                            <DropdownMenuSeparator v-if="groupIndex > 0" />
                            <DropdownMenuLabel
                                class="text-[10px] tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                            >
                                {{ group.heading }}
                            </DropdownMenuLabel>
                            <DropdownMenuItem
                                v-for="type in group.types"
                                :key="type"
                                class="cursor-pointer"
                                @select="addCondition(type)"
                            >
                                {{ TYPE_LABELS[type] }}
                            </DropdownMenuItem>
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- Actions -->
            <div
                class="flex items-center gap-3 border-t border-[hsl(var(--ds-line))] pt-6"
            >
                <Button
                    type="submit"
                    :disabled="form.processing || !form.conditions.length"
                    data-test="save-segment-button"
                    class="h-11 bg-[hsl(var(--ds-accent))] px-6 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                >
                    {{ isEdit ? 'Save segment' : 'Create segment' }}
                </Button>
                <Button as-child variant="ghost" class="h-11">
                    <Link :href="segmentsRoute(list.slug)">Cancel</Link>
                </Button>
            </div>
        </form>

        <!-- Matching subscribers -->
        <section class="mt-12">
            <div class="mb-4 flex items-baseline justify-between gap-4">
                <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                    Matching subscribers
                </h2>
                <span
                    v-if="preview"
                    class="text-sm font-medium text-[hsl(var(--ds-ink-soft))]"
                >
                    {{ numberFormatter.format(preview.total) }}
                    {{ preview.total === 1 ? 'match' : 'matches' }}
                </span>
            </div>

            <div
                v-if="!form.conditions.length"
                class="rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-12 text-center text-sm text-[hsl(var(--ds-ink-soft))]"
            >
                Add a condition to preview matching subscribers.
            </div>

            <div
                v-else-if="preview && !preview.subscribers.length"
                class="rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-12 text-center text-sm text-[hsl(var(--ds-ink-soft))]"
            >
                No subscribers match these conditions.
            </div>

            <template v-else-if="preview">
                <ul
                    class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
                >
                    <li
                        v-for="subscriber in preview.subscribers"
                        :key="subscriber.id"
                        class="flex items-center gap-3 px-5 py-3"
                    >
                        <Users
                            class="size-4 shrink-0 text-[hsl(var(--ds-ink-faint))]"
                        />
                        <span
                            class="min-w-0 flex-1 truncate text-sm font-medium text-[hsl(var(--ds-ink))]"
                        >
                            {{ subscriber.name || subscriber.email }}
                        </span>
                        <span
                            v-if="subscriber.name"
                            class="truncate text-xs text-[hsl(var(--ds-ink-faint))]"
                        >
                            {{ subscriber.email }}
                        </span>
                    </li>
                </ul>
                <p
                    v-if="preview.total > preview.subscribers.length"
                    class="mt-3 text-center text-xs text-[hsl(var(--ds-ink-faint))]"
                >
                    Showing the first {{ preview.subscribers.length }} of
                    {{ numberFormatter.format(preview.total) }}.
                </p>
            </template>
        </section>

        <!-- Shared autocomplete sources -->
        <datalist id="segment-tags">
            <option v-for="tag in options.tags" :key="tag" :value="tag" />
        </datalist>
        <datalist id="segment-attributes">
            <option
                v-for="attribute in options.attributes"
                :key="attribute"
                :value="attribute"
            />
        </datalist>
    </div>
</template>
