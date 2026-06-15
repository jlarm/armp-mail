<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    index as listsRoute,
    show as showRoute,
    update as updateRoute,
} from '@/routes/lists';

type ListSettings = {
    name: string;
    slug: string;
    description: string | null;
    default_from_name: string | null;
    default_from_email: string | null;
    default_reply_to_email: string | null;
    requires_confirmation: boolean;
    redirect_after_subscribed: string | null;
    redirect_after_unsubscribed: string | null;
    campaign_mails_per_minute: number | null;
};

const props = defineProps<{
    list: ListSettings;
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

const form = useForm({
    name: props.list.name,
    slug: props.list.slug,
    description: props.list.description ?? '',
    default_from_name: props.list.default_from_name ?? '',
    default_from_email: props.list.default_from_email ?? '',
    default_reply_to_email: props.list.default_reply_to_email ?? '',
    requires_confirmation: props.list.requires_confirmation,
    redirect_after_subscribed: props.list.redirect_after_subscribed ?? '',
    redirect_after_unsubscribed: props.list.redirect_after_unsubscribed ?? '',
    campaign_mails_per_minute: props.list.campaign_mails_per_minute ?? '',
});

const submit = () => {
    form.put(updateRoute(props.list.slug).url, { preserveScroll: true });
};

const labelClass =
    'text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase';
const inputClass =
    'h-11 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.5)]';
</script>

<template>
    <Head :title="`${list.name} settings`" />

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
        <header class="mt-4 border-b border-[hsl(var(--ds-line))] pb-6">
            <p
                class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
            >
                List settings
            </p>
            <h1
                class="font-display mt-1 text-4xl leading-[1.05] tracking-tight text-[hsl(var(--ds-ink))] md:text-5xl"
            >
                {{ list.name }}
            </h1>
        </header>

        <form class="mt-8 space-y-12" @submit.prevent="submit">
            <!-- Details -->
            <section class="space-y-5">
                <header>
                    <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                        Details
                    </h2>
                    <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                        How this list is identified across the dispatch desk.
                    </p>
                </header>

                <div class="grid gap-2">
                    <Label for="name" :class="labelClass">Name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        :class="inputClass"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug" :class="labelClass">Slug</Label>
                    <Input
                        id="slug"
                        v-model="form.slug"
                        :class="inputClass"
                        required
                    />
                    <p class="text-xs text-[hsl(var(--ds-ink-faint))]">
                        Used in this list's URL. Changing it updates the link.
                    </p>
                    <InputError :message="form.errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="description" :class="labelClass"
                        >Description</Label
                    >
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="rounded-md border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.5)] px-3 py-2 text-sm text-[hsl(var(--ds-ink))] outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ds-accent)/0.4)]"
                        placeholder="An optional note about this list"
                    />
                    <InputError :message="form.errors.description" />
                </div>
            </section>

            <!-- Default sender -->
            <section class="space-y-5">
                <header>
                    <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                        Default sender
                    </h2>
                    <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                        The from and reply-to identity used for this list's
                        campaigns.
                    </p>
                </header>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="default_from_name" :class="labelClass"
                            >From name</Label
                        >
                        <Input
                            id="default_from_name"
                            v-model="form.default_from_name"
                            :class="inputClass"
                            required
                        />
                        <InputError :message="form.errors.default_from_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="default_from_email" :class="labelClass"
                            >From email</Label
                        >
                        <Input
                            id="default_from_email"
                            v-model="form.default_from_email"
                            type="email"
                            :class="inputClass"
                            required
                        />
                        <InputError :message="form.errors.default_from_email" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="default_reply_to_email" :class="labelClass"
                        >Reply-to email</Label
                    >
                    <Input
                        id="default_reply_to_email"
                        v-model="form.default_reply_to_email"
                        type="email"
                        :class="inputClass"
                        placeholder="Optional"
                    />
                    <InputError :message="form.errors.default_reply_to_email" />
                </div>
            </section>

            <!-- Subscription -->
            <section class="space-y-5">
                <header>
                    <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                        Subscription
                    </h2>
                    <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                        Confirmation behaviour and post-subscription redirects.
                    </p>
                </header>

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-4"
                >
                    <Checkbox
                        v-model="form.requires_confirmation"
                        class="mt-0.5"
                    />
                    <span>
                        <span
                            class="block text-sm font-medium text-[hsl(var(--ds-ink))]"
                            >Require confirmation (double opt-in)</span
                        >
                        <span
                            class="block text-xs text-[hsl(var(--ds-ink-soft))]"
                        >
                            New subscribers must confirm via email before they
                            count as subscribed.
                        </span>
                    </span>
                </label>

                <div class="grid gap-2">
                    <Label for="redirect_after_subscribed" :class="labelClass"
                        >Redirect after subscribed</Label
                    >
                    <Input
                        id="redirect_after_subscribed"
                        v-model="form.redirect_after_subscribed"
                        type="url"
                        :class="inputClass"
                        placeholder="https://example.com/thanks"
                    />
                    <InputError
                        :message="form.errors.redirect_after_subscribed"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="redirect_after_unsubscribed" :class="labelClass"
                        >Redirect after unsubscribed</Label
                    >
                    <Input
                        id="redirect_after_unsubscribed"
                        v-model="form.redirect_after_unsubscribed"
                        type="url"
                        :class="inputClass"
                        placeholder="https://example.com/goodbye"
                    />
                    <InputError
                        :message="form.errors.redirect_after_unsubscribed"
                    />
                </div>
            </section>

            <!-- Sending -->
            <section class="space-y-5">
                <header>
                    <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                        Sending
                    </h2>
                    <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                        Throttle how fast campaigns to this list are dispatched.
                    </p>
                </header>

                <div class="grid gap-2">
                    <Label for="campaign_mails_per_minute" :class="labelClass"
                        >Campaign mails per minute</Label
                    >
                    <Input
                        id="campaign_mails_per_minute"
                        v-model="form.campaign_mails_per_minute"
                        type="number"
                        min="1"
                        :class="inputClass"
                        placeholder="No limit"
                    />
                    <InputError
                        :message="form.errors.campaign_mails_per_minute"
                    />
                </div>
            </section>

            <!-- Actions -->
            <div
                class="flex items-center gap-4 border-t border-[hsl(var(--ds-line))] pt-6"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="save-list-button"
                    class="h-11 bg-[hsl(var(--ds-accent))] px-6 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                >
                    Save settings
                </Button>

                <Transition
                    enter-active-class="transition ease-out duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    leave-active-class="transition ease-in duration-200"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-show="form.recentlySuccessful"
                        class="flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))]"
                    >
                        <span
                            class="size-1.5 rounded-full bg-[hsl(var(--ds-accent))]"
                        />
                        Saved
                    </p>
                </Transition>
            </div>
        </form>
    </div>
</template>
