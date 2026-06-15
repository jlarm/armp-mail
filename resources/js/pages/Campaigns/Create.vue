<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
    index as campaignsRoute,
    store as storeCampaignRoute,
} from '@/routes/campaigns';

type Option = { value: number; label: string };

defineProps<{
    lists: Option[];
    templates: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Campaigns', href: campaignsRoute() }],
    },
});

const labelClass =
    'text-[11px] font-semibold tracking-[0.16em] text-[hsl(var(--ds-ink-soft))] uppercase';

const form = useForm({
    email_list_id: '',
    name: '',
    subject: '',
    template_id: '',
});

const submit = () => form.post(storeCampaignRoute().url);
</script>

<template>
    <Head title="New campaign" />

    <div class="mx-auto w-full max-w-2xl px-4 py-10 md:px-7 lg:py-14">
        <Link
            :href="campaignsRoute()"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-ink-faint))] transition-colors hover:text-[hsl(var(--ds-ink))]"
        >
            <ArrowLeft class="size-4" />
            Campaigns
        </Link>

        <header class="mt-4 border-b border-[hsl(var(--ds-line))] pb-6">
            <p
                class="text-[11px] font-semibold tracking-[0.32em] text-[hsl(var(--ds-accent-ink))] uppercase"
            >
                Dispatch
            </p>
            <h1
                class="font-display mt-1 text-4xl leading-[1.05] tracking-tight text-[hsl(var(--ds-ink))] md:text-5xl"
            >
                New campaign
            </h1>
        </header>

        <form class="mt-8 space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label :class="labelClass"
                    >List
                    <span class="text-[hsl(var(--ds-accent))]">*</span></Label
                >
                <Select v-model="form.email_list_id">
                    <SelectTrigger class="!h-11 border-[hsl(var(--ds-line))]">
                        <SelectValue placeholder="Choose a list" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in lists"
                            :key="option.value"
                            :value="String(option.value)"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.email_list_id" />
                <p
                    v-if="!lists.length"
                    class="text-xs text-[hsl(var(--ds-ink-faint))]"
                >
                    Create a list first to send a campaign.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="name" :class="labelClass"
                    >Name
                    <span class="text-[hsl(var(--ds-accent))]">*</span></Label
                >
                <Input
                    id="name"
                    v-model="form.name"
                    class="h-11"
                    required
                    autofocus
                    placeholder="e.g. June newsletter"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="subject" :class="labelClass">Subject</Label>
                <Input
                    id="subject"
                    v-model="form.subject"
                    class="h-11"
                    placeholder="Email subject line"
                />
                <InputError :message="form.errors.subject" />
            </div>

            <div class="grid gap-2">
                <Label :class="labelClass">Template</Label>
                <Select v-model="form.template_id">
                    <SelectTrigger class="!h-11 border-[hsl(var(--ds-line))]">
                        <SelectValue placeholder="Start from scratch" />
                    </SelectTrigger>
                    <SelectContent>
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
                    Optional. Seeds the campaign's HTML from a template.
                </p>
            </div>

            <div
                class="flex items-center gap-3 border-t border-[hsl(var(--ds-line))] pt-6"
            >
                <Button
                    type="submit"
                    :disabled="form.processing"
                    data-test="create-campaign-button"
                    class="h-11 bg-[hsl(var(--ds-accent))] px-6 font-semibold text-white hover:bg-[hsl(var(--ds-accent-ink))]"
                >
                    Create campaign
                </Button>
                <Button as-child variant="ghost" class="h-11">
                    <Link :href="campaignsRoute()">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
