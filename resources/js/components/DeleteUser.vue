<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { TriangleAlert } from 'lucide-vue-next';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
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
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-[hsl(var(--ds-accent)/0.35)] bg-[hsl(var(--ds-accent)/0.05)]"
    >
        <div class="flex items-start gap-4 px-6 py-6">
            <span
                class="grid size-11 shrink-0 place-items-center rounded-xl border border-[hsl(var(--ds-accent)/0.3)] bg-[hsl(var(--ds-panel))] text-[hsl(var(--ds-accent-ink))]"
            >
                <TriangleAlert class="size-5" />
            </span>
            <div class="flex-1 space-y-3">
                <div>
                    <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                        Return to sender
                    </h2>
                    <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                        Delete your account and every campaign, list, and record
                        tied to it. This cannot be undone.
                    </p>
                </div>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                            class="h-10 bg-[hsl(var(--ds-accent))] font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                            >Delete account</Button
                        >
                    </DialogTrigger>
                    <DialogContent>
                        <Form
                            v-bind="ProfileController.destroy.form()"
                            reset-on-success
                            @error="() => passwordInput?.focus()"
                            :options="{
                                preserveScroll: true,
                            }"
                            class="space-y-6"
                            v-slot="{ errors, processing, reset, clearErrors }"
                        >
                            <DialogHeader class="space-y-3">
                                <DialogTitle
                                    >Are you sure you want to delete your
                                    account?</DialogTitle
                                >
                                <DialogDescription>
                                    Once your account is deleted, all of its
                                    resources and data will also be permanently
                                    deleted. Please enter your password to
                                    confirm you would like to permanently delete
                                    your account.
                                </DialogDescription>
                            </DialogHeader>

                            <div class="grid gap-2">
                                <Label for="password" class="sr-only"
                                    >Password</Label
                                >
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    ref="passwordInput"
                                    placeholder="Password"
                                />
                                <InputError :message="errors.password" />
                            </div>

                            <DialogFooter class="gap-2">
                                <DialogClose as-child>
                                    <Button
                                        variant="secondary"
                                        @click="
                                            () => {
                                                clearErrors();
                                                reset();
                                            }
                                        "
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    :disabled="processing"
                                    data-test="confirm-delete-user-button"
                                >
                                    Delete account
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    </div>
</template>
