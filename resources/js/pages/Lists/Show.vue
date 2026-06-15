<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    AtSign,
    MailCheck,
    Plus,
    Reply,
    Upload,
    Users,
} from 'lucide-vue-next';
import { ref } from 'vue';
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
import { index as listsRoute } from '@/routes/lists';
import {
    importMethod as importSubscribersRoute,
    store as storeSubscriberRoute,
} from '@/routes/lists/subscribers';

type ListDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    from_name: string | null;
    from_email: string | null;
    reply_to_email: string | null;
    requires_confirmation: boolean;
    subscribers_count: number;
    created_at: string | null;
};

type SubscriberRow = {
    id: number;
    email: string;
    name: string | null;
    status: string;
    subscribed_at: string | null;
};

defineProps<{
    list: ListDetail;
    subscribers: SubscriberRow[];
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

const statusClasses: Record<string, string> = {
    subscribed:
        'border-[hsl(var(--ds-accent)/0.3)] bg-[hsl(var(--ds-accent)/0.08)] text-[hsl(var(--ds-accent-ink))]',
    unconfirmed:
        'border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-soft))]',
    unsubscribed:
        'border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-faint))]',
    bounced:
        'border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-faint))]',
    complained:
        'border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-[hsl(var(--ds-ink-faint))]',
};

const statusLabel = (status: string) =>
    status.charAt(0).toUpperCase() + status.slice(1);

const addOpen = ref(false);
const importOpen = ref(false);
</script>

<template>
    <Head :title="list.name" />

    <div class="mx-auto w-full max-w-5xl px-4 py-10 md:px-7 lg:py-14">
        <!-- Back -->
        <Link
            :href="listsRoute()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-ink))]"
        >
            <ArrowLeft class="size-4" />
            All lists
        </Link>

        <!-- Masthead -->
        <header
            class="mt-4 flex flex-col gap-5 border-b border-[hsl(var(--ds-line))] pb-6 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="flex items-start gap-4">
                <span
                    class="font-display grid size-14 shrink-0 place-items-center rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-2xl text-[hsl(var(--ds-accent-ink))]"
                >
                    {{ list.name.charAt(0).toUpperCase() }}
                </span>
                <div class="min-w-0">
                    <p
                        class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                    >
                        Mailing list
                    </p>
                    <h1
                        class="font-display mt-1 text-4xl leading-[1] tracking-tight text-[hsl(var(--ds-ink))] md:text-5xl"
                    >
                        {{ list.name }}
                    </h1>
                    <p
                        v-if="list.description"
                        class="mt-2 max-w-xl text-sm text-[hsl(var(--ds-ink-soft))]"
                    >
                        {{ list.description }}
                    </p>
                </div>
            </div>

            <span
                v-if="list.requires_confirmation"
                class="inline-flex shrink-0 items-center gap-1.5 self-start rounded-full border border-[hsl(var(--ds-line))] px-3 py-1 text-[11px] font-semibold tracking-wide text-[hsl(var(--ds-ink-soft))] uppercase sm:self-auto"
            >
                <MailCheck class="size-3.5" />
                Double opt-in
            </span>
        </header>

        <!-- Sender details -->
        <dl
            class="mt-8 grid gap-px overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-line))] sm:grid-cols-3"
        >
            <div class="bg-[hsl(var(--ds-panel))] px-5 py-4">
                <dt
                    class="flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    <Users class="size-3.5" />
                    Subscribers
                </dt>
                <dd
                    class="font-display mt-1 text-3xl text-[hsl(var(--ds-ink))]"
                >
                    {{ formatCount(list.subscribers_count) }}
                </dd>
            </div>
            <div class="bg-[hsl(var(--ds-panel))] px-5 py-4">
                <dt
                    class="flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    <AtSign class="size-3.5" />
                    From
                </dt>
                <dd class="mt-1 truncate text-sm text-[hsl(var(--ds-ink))]">
                    <span class="font-medium">{{ list.from_name }}</span>
                    <span class="block truncate text-[hsl(var(--ds-ink-soft))]">
                        {{ list.from_email }}
                    </span>
                </dd>
            </div>
            <div class="bg-[hsl(var(--ds-panel))] px-5 py-4">
                <dt
                    class="flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    <Reply class="size-3.5" />
                    Reply to
                </dt>
                <dd
                    class="mt-1 truncate text-sm text-[hsl(var(--ds-ink-soft))]"
                >
                    {{ list.reply_to_email || '—' }}
                </dd>
            </div>
        </dl>

        <!-- Subscribers -->
        <section class="mt-10">
            <div class="mb-4 flex items-end justify-between gap-4">
                <div>
                    <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                        Recent subscribers
                    </h2>
                    <span
                        v-if="list.subscribers_count > subscribers.length"
                        class="text-xs text-[hsl(var(--ds-ink-faint))]"
                    >
                        Showing {{ subscribers.length }} of
                        {{ formatCount(list.subscribers_count) }}
                    </span>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <!-- Import CSV -->
                    <Dialog v-model:open="importOpen">
                        <DialogTrigger as-child>
                            <Button
                                variant="outline"
                                class="h-10 shrink-0 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] font-semibold text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                            >
                                <Upload class="size-4" />
                                Import
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="importSubscribersRoute.form(list.slug)"
                                class="space-y-6"
                                @success="importOpen = false"
                                v-slot="{ errors, processing }"
                            >
                                <DialogHeader class="space-y-2">
                                    <DialogTitle
                                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                                    >
                                        Import subscribers
                                    </DialogTitle>
                                    <DialogDescription>
                                        Upload a CSV export to import
                                        subscribers into
                                        <span class="font-medium">{{
                                            list.name
                                        }}</span
                                        >. Existing emails are matched and
                                        updated, not duplicated.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label
                                        for="file"
                                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                    >
                                        CSV file
                                    </Label>
                                    <Input
                                        id="file"
                                        name="file"
                                        type="file"
                                        accept=".csv,text/csv,text/plain"
                                        required
                                        class="h-11 cursor-pointer py-2.5 file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-[hsl(var(--ds-accent)/0.12)] file:px-3 file:py-1 file:text-sm file:font-semibold file:text-[hsl(var(--ds-accent-ink))]"
                                    />
                                    <InputError :message="errors.file" />
                                    <p
                                        class="text-xs text-[hsl(var(--ds-ink-faint))]"
                                    >
                                        Large files are imported in chunks, so
                                        thousands of subscribers are fine.
                                    </p>
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        :disabled="processing"
                                        data-test="import-subscribers-button"
                                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                                    >
                                        {{
                                            processing ? 'Importing…' : 'Import'
                                        }}
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>

                    <!-- Add single subscriber -->
                    <Dialog v-model:open="addOpen">
                        <DialogTrigger as-child>
                            <Button
                                variant="outline"
                                class="h-10 shrink-0 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] font-semibold text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                            >
                                <Plus class="size-4" />
                                Add subscriber
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <Form
                                v-bind="storeSubscriberRoute.form(list.slug)"
                                reset-on-success
                                class="space-y-6"
                                @success="addOpen = false"
                                v-slot="{ errors, processing }"
                            >
                                <DialogHeader class="space-y-2">
                                    <DialogTitle
                                        class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                                    >
                                        Add subscriber
                                    </DialogTitle>
                                    <DialogDescription>
                                        Add a subscriber to
                                        <span class="font-medium">{{
                                            list.name
                                        }}</span
                                        >.
                                    </DialogDescription>
                                </DialogHeader>

                                <div class="grid gap-2">
                                    <Label
                                        for="email"
                                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                    >
                                        Email address
                                    </Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        class="h-11"
                                        required
                                        autofocus
                                        autocomplete="off"
                                        placeholder="subscriber@example.com"
                                    />
                                    <InputError :message="errors.email" />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label
                                            for="first_name"
                                            class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                        >
                                            First name
                                        </Label>
                                        <Input
                                            id="first_name"
                                            name="first_name"
                                            class="h-11"
                                            autocomplete="given-name"
                                            placeholder="Jane"
                                        />
                                        <InputError
                                            :message="errors.first_name"
                                        />
                                    </div>
                                    <div class="grid gap-2">
                                        <Label
                                            for="last_name"
                                            class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                                        >
                                            Last name
                                        </Label>
                                        <Input
                                            id="last_name"
                                            name="last_name"
                                            class="h-11"
                                            autocomplete="family-name"
                                            placeholder="Doe"
                                        />
                                        <InputError
                                            :message="errors.last_name"
                                        />
                                    </div>
                                </div>

                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="submit"
                                        :disabled="processing"
                                        data-test="add-subscriber-button"
                                        class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                                    >
                                        Add subscriber
                                    </Button>
                                </DialogFooter>
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div
                v-if="!subscribers.length"
                class="rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-12 text-center text-sm text-[hsl(var(--ds-ink-soft))]"
            >
                No subscribers on this list yet.
            </div>

            <ul
                v-else
                class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
            >
                <li
                    v-for="subscriber in subscribers"
                    :key="subscriber.id"
                    class="flex items-center gap-4 px-5 py-3.5"
                >
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-sm font-medium text-[hsl(var(--ds-ink))]"
                        >
                            {{ subscriber.name || subscriber.email }}
                        </p>
                        <p
                            v-if="subscriber.name"
                            class="truncate text-xs text-[hsl(var(--ds-ink-faint))]"
                        >
                            {{ subscriber.email }}
                        </p>
                    </div>

                    <span
                        class="shrink-0 rounded-full border px-2.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                        :class="
                            statusClasses[subscriber.status] ??
                            statusClasses.unconfirmed
                        "
                    >
                        {{ statusLabel(subscriber.status) }}
                    </span>

                    <span
                        class="hidden w-24 shrink-0 text-right text-xs text-[hsl(var(--ds-ink-faint))] sm:block"
                    >
                        {{ formatDate(subscriber.subscribed_at) }}
                    </span>
                </li>
            </ul>
        </section>
    </div>
</template>
