<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Mail, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/composables/useInitials';
import { edit } from '@/routes/profile';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <!-- Identity card -->
    <article
        class="overflow-hidden rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] shadow-[0_1px_2px_hsl(24_16%_13%/0.04)]"
    >
        <!-- Letterhead -->
        <div
            class="flex items-center gap-4 border-b border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-accent)/0.04)] px-6 py-5"
        >
            <span
                class="font-display grid size-14 place-items-center rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] text-2xl text-[hsl(var(--ds-accent-ink))]"
            >
                {{ getInitials(user.name) }}
            </span>
            <div class="min-w-0">
                <p
                    class="text-[10px] font-semibold tracking-[0.24em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    Return address
                </p>
                <p
                    class="font-display truncate text-2xl leading-tight text-[hsl(var(--ds-ink))]"
                >
                    {{ user.name }}
                </p>
                <p class="truncate text-sm text-[hsl(var(--ds-ink-soft))]">
                    {{ user.email }}
                </p>
            </div>
        </div>

        <div class="px-6 py-6">
            <header class="mb-6">
                <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                    Profile information
                </h2>
                <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                    Update the name and email address mail is dispatched from.
                </p>
            </header>

            <Form
                v-bind="ProfileController.update.form()"
                class="space-y-6"
                v-slot="{ errors, processing, recentlySuccessful }"
            >
                <div class="grid gap-2">
                    <Label
                        for="name"
                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                    >
                        Name
                    </Label>
                    <div class="relative">
                        <UserRound
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[hsl(var(--ds-ink-faint))]"
                        />
                        <Input
                            id="name"
                            class="h-11 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.5)] pl-9"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                    </div>
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="email"
                        class="text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase"
                    >
                        Email address
                    </Label>
                    <div class="relative">
                        <Mail
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-[hsl(var(--ds-ink-faint))]"
                        />
                        <Input
                            id="email"
                            type="email"
                            class="h-11 border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.5)] pl-9"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button
                        :disabled="processing"
                        data-test="update-profile-button"
                        class="h-11 bg-[hsl(var(--ds-accent))] px-6 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                    >
                        Save changes
                    </Button>

                    <Transition
                        enter-active-class="transition ease-out duration-300"
                        enter-from-class="opacity-0 translate-x-2"
                        leave-active-class="transition ease-in duration-200"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-show="recentlySuccessful"
                            class="flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))]"
                        >
                            <span
                                class="size-1.5 rounded-full bg-[hsl(var(--ds-accent))]"
                            />
                            Saved
                        </p>
                    </Transition>
                </div>
            </Form>
        </div>
    </article>

    <DeleteUser />
</template>
