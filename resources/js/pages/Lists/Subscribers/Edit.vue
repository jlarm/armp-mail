<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Info, Plus, Trash2, X } from 'lucide-vue-next';
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
import { index as listsRoute, show as showRoute } from '@/routes/lists';
import {
    destroy as destroyRoute,
    unsubscribe as unsubscribeRoute,
    update as updateRoute,
} from '@/routes/lists/subscribers';

type Attribute = { key: string; value: string };

type SubscriberDetail = {
    id: number;
    email: string;
    first_name: string | null;
    last_name: string | null;
    tags: string[];
    attributes: Attribute[];
    status: string;
    subscribed_at: string | null;
    unsubscribed_at: string | null;
};

const props = defineProps<{
    list: { name: string; slug: string };
    subscriber: SubscriberDetail;
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

const routeArgs = { list: props.list.slug, subscriber: props.subscriber.id };

const form = useForm({
    email: props.subscriber.email,
    first_name: props.subscriber.first_name ?? '',
    last_name: props.subscriber.last_name ?? '',
    tags: [...props.subscriber.tags],
    attributes: props.subscriber.attributes.map((attribute) => ({
        ...attribute,
    })),
});

const tagInput = ref('');

const addTag = () => {
    const value = tagInput.value.trim();

    if (value && !form.tags.includes(value)) {
        form.tags.push(value);
    }

    tagInput.value = '';
};

const removeTag = (index: number) => {
    form.tags.splice(index, 1);
};

const addAttribute = () => {
    form.attributes.push({ key: '', value: '' });
};

const removeAttribute = (index: number) => {
    form.attributes.splice(index, 1);
};

const submit = () => {
    form.put(updateRoute(routeArgs).url, { preserveScroll: true });
};

const unsubscribe = () => {
    router.post(unsubscribeRoute(routeArgs).url, {}, { preserveScroll: true });
};

const destroy = () => {
    router.delete(destroyRoute(routeArgs).url);
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

const placeholderSyntax = '{{ subscriber.<key> }}';
</script>

<template>
    <Head :title="`Edit ${subscriber.email}`" />

    <div class="mx-auto w-full max-w-2xl px-4 py-10 md:px-7 lg:py-14">
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
            class="mt-4 flex items-end justify-between gap-4 border-b border-[hsl(var(--ds-line))] pb-6"
        >
            <div class="min-w-0">
                <p
                    class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
                >
                    Subscriber
                </p>
                <h1
                    class="font-display mt-1 truncate text-4xl leading-[1] tracking-tight text-[hsl(var(--ds-ink))]"
                >
                    {{ subscriber.email }}
                </h1>
            </div>
            <span
                class="shrink-0 rounded-full border px-3 py-1 text-[11px] font-semibold tracking-wide uppercase"
                :class="
                    statusClasses[subscriber.status] ??
                    statusClasses.unconfirmed
                "
            >
                {{ statusLabel(subscriber.status) }}
            </span>
        </header>

        <form class="mt-8 space-y-8" @submit.prevent="submit">
            <!-- Identity -->
            <div class="space-y-5">
                <div class="grid gap-2">
                    <Label
                        for="email"
                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                    >
                        Email
                        <span class="text-[hsl(var(--ds-accent))]">*</span>
                    </Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="h-11"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.email" />
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
                            v-model="form.first_name"
                            class="h-11"
                            autocomplete="given-name"
                        />
                        <InputError :message="form.errors.first_name" />
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
                            v-model="form.last_name"
                            class="h-11"
                            autocomplete="family-name"
                        />
                        <InputError :message="form.errors.last_name" />
                    </div>
                </div>
            </div>

            <!-- Tags -->
            <div class="grid gap-2">
                <Label
                    class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                >
                    Tags
                </Label>
                <div
                    class="flex flex-wrap items-center gap-2 rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-2"
                >
                    <span
                        v-for="(tag, index) in form.tags"
                        :key="tag"
                        class="inline-flex items-center gap-1.5 rounded-md bg-[hsl(var(--ds-accent)/0.1)] py-1 pr-1 pl-2.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))]"
                    >
                        {{ tag }}
                        <button
                            type="button"
                            class="grid size-4 place-items-center rounded text-[hsl(var(--ds-accent-ink))] transition-colors hover:bg-[hsl(var(--ds-accent)/0.2)]"
                            aria-label="Remove tag"
                            @click="removeTag(index)"
                        >
                            <X class="size-3" />
                        </button>
                    </span>
                    <input
                        v-model="tagInput"
                        type="text"
                        class="min-w-32 flex-1 bg-transparent px-1.5 py-1 text-sm text-[hsl(var(--ds-ink))] outline-none placeholder:text-[hsl(var(--ds-ink-faint))]"
                        :placeholder="
                            form.tags.length ? '' : 'Add a tag and press Enter'
                        "
                        @keydown.enter.prevent="addTag"
                        @blur="addTag"
                    />
                </div>
            </div>

            <!-- Extra attributes -->
            <div class="space-y-3">
                <div>
                    <Label
                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                    >
                        Extra attributes
                    </Label>
                    <p
                        class="mt-2 flex items-start gap-2 text-sm text-[hsl(var(--ds-ink-soft))]"
                    >
                        <Info
                            class="mt-0.5 size-4 shrink-0 text-[hsl(var(--ds-accent-ink))]"
                        />
                        <span>
                            Add or remove attributes to use in campaigns or
                            automations with
                            <code
                                v-text="placeholderSyntax"
                                class="rounded bg-[hsl(var(--ds-paper))] px-1 py-0.5 text-xs"
                            />.
                        </span>
                    </p>
                </div>

                <div
                    v-for="(attribute, index) in form.attributes"
                    :key="index"
                    class="flex items-start gap-2"
                >
                    <Input
                        v-model="attribute.key"
                        class="h-11 flex-1"
                        placeholder="Key"
                        aria-label="Attribute key"
                    />
                    <Input
                        v-model="attribute.value"
                        class="h-11 flex-1"
                        placeholder="Value"
                        aria-label="Attribute value"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        class="size-11 shrink-0 text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-accent))]"
                        aria-label="Remove attribute"
                        @click="removeAttribute(index)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))] transition-opacity hover:opacity-80"
                    @click="addAttribute"
                >
                    <Plus class="size-4" />
                    Add attribute
                </button>
            </div>

            <!-- Actions -->
            <div
                class="flex flex-wrap items-center gap-3 border-t border-[hsl(var(--ds-line))] pt-6"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-subscriber-button"
                    class="h-11 bg-[hsl(var(--ds-accent))] px-6 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                >
                    Save subscriber
                </Button>

                <Button
                    type="button"
                    variant="secondary"
                    :disabled="subscriber.status === 'unsubscribed'"
                    class="h-11"
                    @click="unsubscribe"
                >
                    {{
                        subscriber.status === 'unsubscribed'
                            ? 'Unsubscribed'
                            : 'Unsubscribe'
                    }}
                </Button>

                <Dialog>
                    <DialogTrigger as-child>
                        <Button
                            type="button"
                            variant="ghost"
                            class="ml-auto h-11 text-[hsl(var(--ds-accent))] hover:bg-[hsl(var(--ds-accent)/0.08)] hover:text-[hsl(var(--ds-accent-ink))]"
                        >
                            Delete
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader class="space-y-2">
                            <DialogTitle
                                class="font-display text-2xl text-[hsl(var(--ds-ink))]"
                            >
                                Delete subscriber
                            </DialogTitle>
                            <DialogDescription>
                                This permanently deletes
                                <span class="font-medium">{{
                                    subscriber.email
                                }}</span>
                                and removes them from every list. This cannot be
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
                                data-test="delete-subscriber-button"
                                class="bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                                @click="destroy"
                            >
                                Delete subscriber
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </form>
    </div>
</template>
