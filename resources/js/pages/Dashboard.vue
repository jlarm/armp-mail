<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, MailCheck, Send, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { dashboard } from '@/routes';
import {
    edit as campaignEditRoute,
    index as campaignsRoute,
} from '@/routes/campaigns';
import { index as listsRoute } from '@/routes/lists';

type GrowthPoint = { date: string; total: number };

const props = defineProps<{
    newSubscribers: number;
    campaigns: { draft: number; scheduled: number; sent: number };
    latestCampaign: {
        id: number;
        name: string;
        opens: number;
        clicks: number;
        unsubs: number;
        bounces: number;
    } | null;
    audienceGrowth: GrowthPoint[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const numberFormatter = new Intl.NumberFormat();
const formatCount = (value: number) => numberFormatter.format(value);

const formatDay = (date: string) =>
    new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: '2-digit',
    });

/* ----- SVG line chart geometry ----- */
const W = 1000;
const H = 300;
const PAD = { top: 16, right: 16, bottom: 28, left: 56 };

const chart = computed(() => {
    const points = props.audienceGrowth;

    if (points.length < 2) {
        return null;
    }

    const totals = points.map((p) => p.total);
    const max = Math.max(...totals);
    const min = Math.min(...totals);
    const span = max - min || 1;

    const innerW = W - PAD.left - PAD.right;
    const innerH = H - PAD.top - PAD.bottom;

    const x = (i: number) => PAD.left + (i / (points.length - 1)) * innerW;
    const y = (v: number) => PAD.top + (1 - (v - min) / span) * innerH;

    const line = points.map((p, i) => `${x(i)},${y(p.total)}`).join(' ');
    const area = `${PAD.left},${PAD.top + innerH} ${line} ${PAD.left + innerW},${PAD.top + innerH}`;

    const yTicks = [0, 0.5, 1].map((t) => {
        const value = Math.round(min + span * (1 - t));

        return { y: PAD.top + t * innerH, label: formatCount(value) };
    });

    const step = Math.ceil(points.length / 8);
    const xLabels = points
        .map((p, i) => ({ x: x(i), label: formatDay(p.date), i }))
        .filter((p) => p.i % step === 0);

    return { line, area, yTicks, xLabels };
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto w-full max-w-6xl space-y-6 px-4 py-10 md:px-7 lg:py-14">
        <!-- Stat cards -->
        <div class="grid gap-4 lg:grid-cols-3">
            <!-- New subscribers -->
            <section
                class="flex flex-col rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-5"
            >
                <div
                    class="flex items-center gap-2 text-[hsl(var(--ds-accent-ink))]"
                >
                    <Users class="size-4" />
                    <span
                        class="text-[11px] font-semibold tracking-[0.2em] uppercase"
                        >New subscribers</span
                    >
                </div>
                <p
                    class="font-display mt-3 text-4xl leading-none text-[hsl(var(--ds-ink))]"
                >
                    {{ formatCount(newSubscribers) }}
                </p>
                <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                    Last 30 days
                </p>
                <Link
                    :href="listsRoute()"
                    class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))] hover:opacity-80"
                >
                    View email lists <ArrowRight class="size-4" />
                </Link>
            </section>

            <!-- Campaigns -->
            <section
                class="flex flex-col rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-5"
            >
                <div
                    class="flex items-center gap-2 text-[hsl(var(--ds-accent-ink))]"
                >
                    <Send class="size-4" />
                    <span
                        class="text-[11px] font-semibold tracking-[0.2em] uppercase"
                        >Campaigns</span
                    >
                </div>
                <div class="mt-3 flex gap-6">
                    <div
                        v-for="stat in [
                            { label: 'Draft', value: campaigns.draft },
                            { label: 'Scheduled', value: campaigns.scheduled },
                            { label: 'Sent', value: campaigns.sent },
                        ]"
                        :key="stat.label"
                    >
                        <p
                            class="font-display text-4xl leading-none text-[hsl(var(--ds-ink))]"
                        >
                            {{ formatCount(stat.value) }}
                        </p>
                        <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                            {{ stat.label }}
                        </p>
                    </div>
                </div>
                <Link
                    :href="campaignsRoute()"
                    class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))] hover:opacity-80"
                >
                    View all campaigns <ArrowRight class="size-4" />
                </Link>
            </section>

            <!-- Latest campaign -->
            <section
                class="flex flex-col rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-5"
            >
                <div
                    class="flex items-center gap-2 text-[hsl(var(--ds-accent-ink))]"
                >
                    <MailCheck class="size-4" />
                    <span
                        class="truncate text-[11px] font-semibold tracking-[0.2em] uppercase"
                    >
                        {{
                            latestCampaign
                                ? latestCampaign.name
                                : 'Latest campaign'
                        }}
                    </span>
                </div>

                <template v-if="latestCampaign">
                    <div class="mt-3 flex gap-5">
                        <div
                            v-for="stat in [
                                { label: 'Opens', value: latestCampaign.opens },
                                {
                                    label: 'Clicks',
                                    value: latestCampaign.clicks,
                                },
                                {
                                    label: 'Unsubs',
                                    value: latestCampaign.unsubs,
                                },
                                {
                                    label: 'Bounces',
                                    value: latestCampaign.bounces,
                                },
                            ]"
                            :key="stat.label"
                        >
                            <p
                                class="font-display text-3xl leading-none text-[hsl(var(--ds-ink))]"
                            >
                                {{ formatCount(stat.value) }}
                            </p>
                            <p
                                class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]"
                            >
                                {{ stat.label }}
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="campaignEditRoute(latestCampaign.id)"
                        class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-[hsl(var(--ds-accent-ink))] hover:opacity-80"
                    >
                        View campaign <ArrowRight class="size-4" />
                    </Link>
                </template>
                <p
                    v-else
                    class="mt-3 flex-1 text-sm text-[hsl(var(--ds-ink-soft))]"
                >
                    No campaigns sent yet.
                </p>
            </section>
        </div>

        <!-- Audience growth -->
        <section
            class="rounded-2xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-6"
        >
            <h2 class="font-display text-2xl text-[hsl(var(--ds-ink))]">
                Audience growth
            </h2>
            <p class="mt-1 text-sm text-[hsl(var(--ds-ink-soft))]">
                Total subscribers over the last 60 days.
            </p>

            <svg
                v-if="chart"
                :viewBox="`0 0 ${W} ${H}`"
                class="mt-4 h-72 w-full"
                preserveAspectRatio="none"
                role="img"
                aria-label="Audience growth chart"
            >
                <g v-for="tick in chart.yTicks" :key="tick.label">
                    <line
                        :x1="PAD.left"
                        :y1="tick.y"
                        :x2="W - PAD.right"
                        :y2="tick.y"
                        stroke="hsl(var(--ds-line))"
                        stroke-width="1"
                    />
                    <text
                        :x="PAD.left - 10"
                        :y="tick.y + 4"
                        text-anchor="end"
                        fill="hsl(var(--ds-ink-faint))"
                        font-size="13"
                    >
                        {{ tick.label }}
                    </text>
                </g>

                <polygon
                    :points="chart.area"
                    fill="hsl(var(--ds-accent) / 0.08)"
                />
                <polyline
                    :points="chart.line"
                    fill="none"
                    stroke="hsl(var(--ds-accent))"
                    stroke-width="2.5"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                />

                <text
                    v-for="label in chart.xLabels"
                    :key="label.label"
                    :x="label.x"
                    :y="H - 6"
                    text-anchor="middle"
                    fill="hsl(var(--ds-ink-faint))"
                    font-size="12"
                >
                    {{ label.label }}
                </text>
            </svg>

            <div
                v-else
                class="mt-4 rounded-xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-6 py-12 text-center text-sm text-[hsl(var(--ds-ink-soft))]"
            >
                Not enough data yet.
            </div>
        </section>
    </div>
</template>
