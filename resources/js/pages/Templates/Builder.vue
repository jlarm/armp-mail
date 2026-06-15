<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Braces,
    Copy,
    Image,
    Info,
    Monitor,
    Send,
    Smartphone,
    Type,
    Upload,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import CodeEditor from '@/components/CodeEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import {
    campaign as campaignRoute,
    duplicate as duplicateRoute,
    images as uploadImageRoute,
    index as templatesRoute,
    store as storeTemplateRoute,
    test as testRoute,
    update as updateTemplateRoute,
} from '@/routes/templates';

type ListOption = { value: number; label: string };

const props = defineProps<{
    template?: { id: number; name: string; html: string };
    lists?: ListOption[];
}>();

const isEdit = props.template !== undefined;

const STARTERS: { name: string; html: string }[] = [
    {
        name: 'Blank',
        html: '<!DOCTYPE html>\n<html>\n  <body style="margin:0;font-family:Arial,sans-serif;">\n    [[[content]]]\n  </body>\n</html>',
    },
    {
        name: 'Simple',
        html: `<!DOCTYPE html>
<html>
  <head><meta charset="utf-8" /><title>[[[subject:text]]]</title></head>
  <body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td align="center" style="padding:32px 16px;">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;">
          <tr><td style="padding:32px;">[[[content]]]</td></tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>`,
    },
    {
        name: 'Hero + logo',
        html: `<!DOCTYPE html>
<html>
  <head><meta charset="utf-8" /><title>[[[subject:text]]]</title></head>
  <body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td align="center" style="padding:24px 16px;">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;">
          <tr><td align="center" style="padding:24px;background:#0f172a;">
            <img src="[[[logo:image]]]" alt="" height="48" />
          </td></tr>
          <tr><td style="padding:32px;">
            <h1 style="margin:0 0 16px;">[[[headline:text]]]</h1>
            [[[content]]]
          </td></tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>`,
    },
];

const form = useForm({
    name: props.template?.name ?? '',
    html: props.template?.html ?? STARTERS[1].html,
});

/* ----- Slot detection + sample-data preview ----- */
const slotPattern = /\[\[\[\s*([a-zA-Z0-9_-]+)(?::(text|image))?\s*\]\]\]/g;

const slots = computed(() => {
    const found = new Map<string, string>();

    for (const match of form.html.matchAll(slotPattern)) {
        found.set(match[1], match[2] ?? 'editor');
    }

    return [...found].map(([name, type]) => ({ name, type }));
});

const slotIcon = (type: string) =>
    type === 'image' ? Image : type === 'text' ? Type : Braces;

const fillSamples = (html: string) =>
    html.replace(slotPattern, (_match, name: string, type?: string) =>
        type === 'image'
            ? `https://picsum.photos/seed/${encodeURIComponent(name)}/600/240`
            : `Sample ${name.replace(/[-_]/g, ' ')}`,
    );

/* Debounced preview so the iframe doesn't re-render on every keystroke. */
const debouncedHtml = ref(form.html);
let previewTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => form.html,
    (value) => {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(() => (debouncedHtml.value = value), 250);
    },
);

const previewHtml = computed(() => fillSamples(debouncedHtml.value));

const device = ref<'desktop' | 'mobile'>('desktop');

/* ----- Save / autosave / unsaved guard ----- */
const autosaveState = ref<'idle' | 'saving' | 'saved'>('idle');
let autosaveTimer: ReturnType<typeof setTimeout> | undefined;

const submit = () => {
    if (props.template) {
        form.put(updateTemplateRoute(props.template.id).url, {
            preserveScroll: true,
        });
    } else {
        form.post(storeTemplateRoute().url);
    }
};

watch(
    () => [form.name, form.html],
    () => {
        if (!isEdit) {
            return;
        }

        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(() => {
            if (form.processing || !form.isDirty || !form.name || !form.html) {
                return;
            }

            autosaveState.value = 'saving';
            form.put(updateTemplateRoute(props.template!.id).url, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => (autosaveState.value = 'saved'),
                onError: () => (autosaveState.value = 'idle'),
            });
        }, 1500);
    },
);

const onBeforeUnload = (event: BeforeUnloadEvent) => {
    if (form.isDirty) {
        event.preventDefault();
        event.returnValue = '';
    }
};

let removeGuard: (() => void) | undefined;

onMounted(() => {
    window.addEventListener('beforeunload', onBeforeUnload);

    removeGuard = router.on('before', (event) => {
        if (event.detail.visit.method === 'get' && form.isDirty) {
            return window.confirm('You have unsaved changes. Leave anyway?');
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
    removeGuard?.();
});

/* ----- Image upload (fills :image slots) ----- */
const uploadingSlot = ref<string | null>(null);

const csrfToken = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
};

const onSlotFile = async (slotName: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploadingSlot.value = slotName;

    try {
        const body = new FormData();
        body.append('image', file);

        const response = await fetch(uploadImageRoute().url, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {
                'X-XSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Upload failed');
        }

        const { url } = (await response.json()) as { url: string };
        const pattern = new RegExp(
            `\\[\\[\\[\\s*${slotName}:image\\s*\\]\\]\\]`,
            'g',
        );
        form.html = form.html.replace(pattern, url);
    } finally {
        uploadingSlot.value = null;
        input.value = '';
    }
};

/* ----- Send test ----- */
const testOpen = ref(false);
const testForm = useForm({ email: '' });

const sendTest = () => {
    if (!props.template) {
        return;
    }

    testForm.post(testRoute(props.template.id).url, {
        preserveScroll: true,
        onSuccess: () => (testOpen.value = false),
    });
};

const duplicate = () => {
    if (props.template) {
        router.post(duplicateRoute(props.template.id).url);
    }
};

/* ----- Use in campaign ----- */
const campaignOpen = ref(false);
const campaignForm = useForm({
    email_list_id: '',
    name: props.template ? `${props.template.name} campaign` : '',
    subject: '',
});

const createCampaign = () => {
    if (!props.template) {
        return;
    }

    campaignForm.post(campaignRoute(props.template.id).url, {
        onSuccess: () => (campaignOpen.value = false),
    });
};
</script>

<template>
    <Head :title="isEdit ? template!.name : 'New template'" />

    <div class="w-full px-4 py-10 md:px-7 lg:py-14">
        <!-- Back -->
        <Link
            :href="templatesRoute()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-ink))]"
        >
            <ArrowLeft class="size-4" />
            Templates
        </Link>

        <!-- Masthead -->
        <header
            class="mt-4 flex flex-wrap items-end justify-between gap-4 border-b border-[hsl(var(--ds-line))] pb-6"
        >
            <div>
                <p
                    class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                >
                    Template
                </p>
                <h1
                    class="font-display mt-1 text-4xl leading-[1.05] tracking-tight text-[hsl(var(--ds-ink))] md:text-5xl"
                >
                    {{ isEdit ? template!.name : 'New template' }}
                </h1>
            </div>
            <span
                v-if="isEdit"
                class="text-xs text-[hsl(var(--ds-ink-faint))]"
                aria-live="polite"
            >
                <template v-if="autosaveState === 'saving'">Saving…</template>
                <template v-else-if="autosaveState === 'saved'"
                    >All changes saved</template
                >
            </span>
        </header>

        <!-- Info -->
        <div
            class="mt-8 flex gap-3 rounded-2xl border border-[hsl(var(--ds-accent)/0.25)] bg-[hsl(var(--ds-accent)/0.05)] px-5 py-4 text-sm text-[hsl(var(--ds-ink-soft))]"
        >
            <Info
                class="mt-0.5 size-4 shrink-0 text-[hsl(var(--ds-accent-ink))]"
            />
            <p>
                Create slots by wrapping a name in triple brackets, e.g.
                <code
                    class="rounded bg-[hsl(var(--ds-paper))] px-1 py-0.5 text-xs text-[hsl(var(--ds-ink))]"
                    >[[[content]]]</code
                >. Append
                <code
                    class="rounded bg-[hsl(var(--ds-paper))] px-1 py-0.5 text-xs text-[hsl(var(--ds-ink))]"
                    >:text</code
                >
                or
                <code
                    class="rounded bg-[hsl(var(--ds-paper))] px-1 py-0.5 text-xs text-[hsl(var(--ds-ink))]"
                    >:image</code
                >, e.g.
                <code
                    class="rounded bg-[hsl(var(--ds-paper))] px-1 py-0.5 text-xs text-[hsl(var(--ds-ink))]"
                    >[[[logo:image]]]</code
                >.
            </p>
        </div>

        <!-- Starter gallery (new templates only) -->
        <div v-if="!isEdit" class="mt-8 grid gap-2">
            <Label
                class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
            >
                Start from
            </Label>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="starter in STARTERS"
                    :key="starter.name"
                    type="button"
                    class="rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] px-3 py-1.5 text-sm font-medium text-[hsl(var(--ds-ink))] transition-colors hover:bg-[hsl(var(--ds-accent)/0.08)]"
                    @click="form.html = starter.html"
                >
                    {{ starter.name }}
                </button>
            </div>
        </div>

        <form class="mt-8 space-y-6" @submit.prevent="submit">
            <!-- Name -->
            <div class="grid max-w-xl gap-2">
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
                    placeholder="e.g. Christmas"
                />
                <InputError :message="form.errors.name" />
            </div>

            <!-- Editor + live preview -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Editor -->
                <div class="space-y-3">
                    <div class="flex h-8 items-center">
                        <Label
                            class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                        >
                            HTML
                        </Label>
                    </div>
                    <CodeEditor v-model="form.html" />
                    <InputError :message="form.errors.html" />

                    <!-- Detected slots -->
                    <div
                        v-if="slots.length"
                        class="flex flex-wrap gap-2 rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-3"
                    >
                        <span
                            v-for="slot in slots"
                            :key="slot.name"
                            class="inline-flex items-center gap-1.5 rounded-md border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] px-2.5 py-1 text-xs font-medium text-[hsl(var(--ds-ink))]"
                        >
                            <component
                                :is="slotIcon(slot.type)"
                                class="size-3.5 text-[hsl(var(--ds-ink-faint))]"
                            />
                            {{ slot.name }}
                            <span
                                class="rounded bg-[hsl(var(--ds-accent)/0.1)] px-1.5 py-px text-[10px] font-semibold tracking-wide text-[hsl(var(--ds-accent-ink))] uppercase"
                                >{{ slot.type }}</span
                            >
                            <label
                                v-if="slot.type === 'image'"
                                class="ml-0.5 inline-flex cursor-pointer items-center gap-1 text-[hsl(var(--ds-accent-ink))] hover:opacity-80"
                            >
                                <Upload class="size-3" />
                                {{
                                    uploadingSlot === slot.name
                                        ? 'Uploading…'
                                        : 'Upload'
                                }}
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    :disabled="uploadingSlot === slot.name"
                                    @change="onSlotFile(slot.name, $event)"
                                />
                            </label>
                        </span>
                    </div>
                </div>

                <!-- Live preview -->
                <div class="space-y-3 lg:sticky lg:top-24 lg:self-start">
                    <div class="flex h-8 items-center justify-between">
                        <Label
                            class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                        >
                            Preview
                        </Label>
                        <div
                            class="flex rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-0.5"
                        >
                            <button
                                type="button"
                                class="grid size-7 place-items-center rounded-md"
                                :class="
                                    device === 'desktop'
                                        ? 'bg-[hsl(var(--ds-accent)/0.12)] text-[hsl(var(--ds-accent-ink))]'
                                        : 'text-[hsl(var(--ds-ink-faint))]'
                                "
                                aria-label="Desktop preview"
                                @click="device = 'desktop'"
                            >
                                <Monitor class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="grid size-7 place-items-center rounded-md"
                                :class="
                                    device === 'mobile'
                                        ? 'bg-[hsl(var(--ds-accent)/0.12)] text-[hsl(var(--ds-accent-ink))]'
                                        : 'text-[hsl(var(--ds-ink-faint))]'
                                "
                                aria-label="Mobile preview"
                                @click="device = 'mobile'"
                            >
                                <Smartphone class="size-4" />
                            </button>
                        </div>
                    </div>
                    <div
                        class="grid place-items-center overflow-hidden rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] p-3"
                    >
                        <iframe
                            :srcdoc="previewHtml"
                            sandbox=""
                            title="Template preview"
                            class="h-[60vh] rounded-lg border border-[hsl(var(--ds-line))] bg-white transition-[width] duration-200"
                            :class="
                                device === 'mobile' ? 'w-[375px]' : 'w-full'
                            "
                        ></iframe>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div
                class="flex flex-wrap items-center gap-3 border-t border-[hsl(var(--ds-line))] pt-6"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-template-button"
                    class="h-11 bg-[hsl(var(--ds-accent))] px-6 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                >
                    Save template
                </Button>

                <template v-if="isEdit">
                    <!-- Send test -->
                    <Dialog v-model:open="testOpen">
                        <DialogTrigger as-child>
                            <Button
                                type="button"
                                variant="outline"
                                class="h-11 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] font-semibold text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                            >
                                <Send class="size-4" />
                                Send test
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <form class="space-y-6" @submit.prevent="sendTest">
                                <DialogHeader class="space-y-2">
                                    <DialogTitle
                                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                                        >Send a test</DialogTitle
                                    >
                                    <DialogDescription>
                                        Sends this template (with sample data)
                                        to an address.
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="grid gap-2">
                                    <Label
                                        for="test-email"
                                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                        >Email</Label
                                    >
                                    <Input
                                        id="test-email"
                                        v-model="testForm.email"
                                        type="email"
                                        class="h-11"
                                        required
                                        placeholder="you@example.com"
                                    />
                                    <InputError
                                        :message="testForm.errors.email"
                                    />
                                </div>
                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            >Cancel</Button
                                        >
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        :disabled="testForm.processing"
                                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                                        >Send test</Button
                                    >
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] font-semibold text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                        @click="duplicate"
                    >
                        <Copy class="size-4" />
                        Duplicate
                    </Button>

                    <!-- Use in campaign -->
                    <Dialog v-model:open="campaignOpen">
                        <DialogTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                class="ml-auto h-11 text-[hsl(var(--ds-accent-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                            >
                                <Send class="size-4" />
                                Use in campaign
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <form
                                class="space-y-6"
                                @submit.prevent="createCampaign"
                            >
                                <DialogHeader class="space-y-2">
                                    <DialogTitle
                                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                                        >New campaign from template</DialogTitle
                                    >
                                    <DialogDescription>
                                        Creates a draft campaign seeded with
                                        this template's HTML and the list's
                                        sender.
                                    </DialogDescription>
                                </DialogHeader>
                                <div class="grid gap-2">
                                    <Label
                                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                        >List</Label
                                    >
                                    <Select
                                        v-model="campaignForm.email_list_id"
                                    >
                                        <SelectTrigger
                                            class="!h-11 border-[hsl(var(--ds-line))]"
                                        >
                                            <SelectValue
                                                placeholder="Choose a list"
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="option in lists ?? []"
                                                :key="option.value"
                                                :value="String(option.value)"
                                            >
                                                {{ option.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        :message="
                                            campaignForm.errors.email_list_id
                                        "
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label
                                        for="campaign-name"
                                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                        >Campaign name</Label
                                    >
                                    <Input
                                        id="campaign-name"
                                        v-model="campaignForm.name"
                                        class="h-11"
                                        required
                                    />
                                    <InputError
                                        :message="campaignForm.errors.name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label
                                        for="campaign-subject"
                                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                        >Subject</Label
                                    >
                                    <Input
                                        id="campaign-subject"
                                        v-model="campaignForm.subject"
                                        class="h-11"
                                        placeholder="Optional"
                                    />
                                </div>
                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            >Cancel</Button
                                        >
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        :disabled="campaignForm.processing"
                                        data-test="create-campaign-button"
                                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                                        >Create draft</Button
                                    >
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </template>
            </div>
        </form>
    </div>
</template>
