<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Inbox, MailCheck, Plus, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
    index as listsRoute,
    show as showRoute,
    store as storeListRoute,
} from '@/routes/lists';

type EmailList = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    from_name: string | null;
    from_email: string | null;
    requires_confirmation: boolean;
    subscribers_count: number;
    created_at: string | null;
};

const props = defineProps<{
    lists: EmailList[];
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

const totalSubscribers = computed(() =>
    props.lists.reduce((sum, list) => sum + list.subscribers_count, 0),
);

const createOpen = ref(false);

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
</script>

<template>
    <Head title="Lists" />

    <div class="mx-auto w-full max-w-5xl px-4 py-10 md:px-7 lg:py-14">
        <!-- Masthead -->
        <header
            class="flex flex-col gap-6 border-b border-[hsl(var(--ds-line))] pb-6 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                >
                    Audience
                </p>
                <h1
                    class="font-display mt-2 text-5xl leading-[0.95] tracking-tight text-[hsl(var(--ds-ink))] md:text-6xl"
                >
                    Lists
                </h1>
                <p class="mt-3 max-w-xl text-sm text-[hsl(var(--ds-ink-soft))]">
                    The mailing lists your dispatch desk delivers to — each with
                    its own sender identity and subscribers.
                </p>
            </div>

            <Dialog v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button
                        class="h-11 shrink-0 bg-[hsl(var(--ds-accent))] px-5 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                    >
                        <Plus class="size-4" />
                        New list
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="storeListRoute.form()"
                        reset-on-success
                        class="space-y-6"
                        @success="createOpen = false"
                        v-slot="{ errors, processing }"
                    >
                        <DialogHeader class="space-y-2">
                            <DialogTitle
                                class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                            >
                                New list
                            </DialogTitle>
                            <DialogDescription>
                                Name your list and set the default sender it
                                dispatches from.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label
                                for="name"
                                class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                            >
                                List name
                            </Label>
                            <Input
                                id="name"
                                name="name"
                                class="h-11"
                                required
                                autofocus
                                placeholder="e.g. Weekly Dispatch"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label
                                for="default_from_name"
                                class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                            >
                                From name
                            </Label>
                            <Input
                                id="default_from_name"
                                name="default_from_name"
                                class="h-11"
                                required
                                autocomplete="name"
                                placeholder="e.g. The Dispatch Desk"
                            />
                            <InputError :message="errors.default_from_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label
                                for="default_from_email"
                                class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                            >
                                From email
                            </Label>
                            <Input
                                id="default_from_email"
                                name="default_from_email"
                                type="email"
                                class="h-11"
                                required
                                autocomplete="email"
                                placeholder="hello@example.com"
                            />
                            <InputError :message="errors.default_from_email" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                :disabled="processing"
                                data-test="create-list-button"
                                class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                            >
                                Create list
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </header>

        <!-- Summary ledger -->
        <dl
            class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-line))] sm:grid-cols-3"
        >
            <div class="bg-[hsl(var(--ds-panel))] px-5 py-4">
                <dt
                    class="text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    Total lists
                </dt>
                <dd
                    class="font-display mt-1 text-3xl text-[hsl(var(--ds-ink))]"
                >
                    {{ formatCount(lists.length) }}
                </dd>
            </div>
            <div class="bg-[hsl(var(--ds-panel))] px-5 py-4">
                <dt
                    class="text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    Subscribers
                </dt>
                <dd
                    class="font-display mt-1 text-3xl text-[hsl(var(--ds-ink))]"
                >
                    {{ formatCount(totalSubscribers) }}
                </dd>
            </div>
            <div
                class="col-span-2 bg-[hsl(var(--ds-panel))] px-5 py-4 sm:col-span-1"
            >
                <dt
                    class="text-[10px] font-semibold tracking-[0.2em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    Avg. per list
                </dt>
                <dd
                    class="font-display mt-1 text-3xl text-[hsl(var(--ds-ink))]"
                >
                    {{
                        formatCount(
                            lists.length
                                ? Math.round(totalSubscribers / lists.length)
                                : 0,
                        )
                    }}
                </dd>
            </div>
        </dl>

        <!-- Lists -->
        <section class="mt-8">
            <!-- Empty state -->
            <div
                v-if="!lists.length"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-16 text-center"
            >
                <span
                    class="grid size-14 place-items-center rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-ink-faint))]"
                >
                    <Inbox class="size-6" />
                </span>
                <h2
                    class="font-display mt-4 text-2xl text-[hsl(var(--ds-ink))]"
                >
                    No lists yet
                </h2>
                <p class="mt-1 max-w-sm text-sm text-[hsl(var(--ds-ink-soft))]">
                    Once you create a mailing list it will appear here, ready to
                    gather subscribers.
                </p>
            </div>

            <!-- Ledger of lists -->
            <ul
                v-else
                class="divide-y divide-[hsl(var(--ds-line))] overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
            >
                <li v-for="list in lists" :key="list.id">
                    <Link
                        :href="showRoute(list.slug)"
                        class="group flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-[hsl(var(--ds-accent)/0.04)] sm:flex-row sm:items-center sm:gap-5"
                    >
                        <!-- Seal + identity -->
                        <span
                            class="font-display grid size-11 shrink-0 place-items-center rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] text-lg text-[hsl(var(--ds-accent-ink))]"
                        >
                            {{ list.name.charAt(0).toUpperCase() }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3
                                    class="font-display truncate text-xl leading-tight text-[hsl(var(--ds-ink))]"
                                >
                                    {{ list.name }}
                                </h3>
                                <span
                                    v-if="list.requires_confirmation"
                                    class="inline-flex items-center gap-1 rounded-full border border-[hsl(var(--ds-line))] px-2 py-px text-[10px] font-semibold tracking-wide text-[hsl(var(--ds-ink-soft))] uppercase"
                                >
                                    <MailCheck class="size-3" />
                                    Double opt-in
                                </span>
                            </div>
                            <p
                                class="mt-0.5 truncate text-sm text-[hsl(var(--ds-ink-soft))]"
                            >
                                {{ list.description || 'No description' }}
                            </p>
                            <p
                                v-if="list.from_email"
                                class="mt-0.5 truncate text-xs text-[hsl(var(--ds-ink-faint))]"
                            >
                                From {{ list.from_name }} ·
                                {{ list.from_email }}
                            </p>
                        </div>

                        <!-- Subscriber count -->
                        <div
                            class="flex shrink-0 items-center gap-2 sm:flex-col sm:items-end sm:gap-0"
                        >
                            <span
                                class="font-display flex items-center gap-1.5 text-2xl leading-none text-[hsl(var(--ds-ink))]"
                            >
                                <Users
                                    class="size-4 text-[hsl(var(--ds-ink-faint))]"
                                />
                                {{ formatCount(list.subscribers_count) }}
                            </span>
                            <span
                                class="text-[10px] font-semibold tracking-[0.18em] text-[hsl(var(--ds-ink-faint))] uppercase"
                            >
                                subscribers
                            </span>
                        </div>

                        <!-- Created -->
                        <div
                            class="hidden w-28 shrink-0 text-right text-xs text-[hsl(var(--ds-ink-faint))] lg:block"
                        >
                            <span class="block tracking-[0.14em] uppercase"
                                >Created</span
                            >
                            <span
                                class="mt-0.5 block text-[hsl(var(--ds-ink-soft))]"
                            >
                                {{ formatDate(list.created_at) }}
                            </span>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
