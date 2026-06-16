<script setup lang="ts">
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    ChevronDown,
    ChevronUp,
    Code,
    Heading,
    Image,
    List,
    Minus,
    MousePointerClick,
    Plus,
    Trash2,
    Type,
    Upload,
} from 'lucide-vue-next';
import { onClickOutside } from '@vueuse/core';
import { computed, ref } from 'vue';
import RichTextField from '@/components/RichTextField.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Block } from '@/lib/renderBlocks';
import { images as uploadImageRoute } from '@/routes/templates';

const blocks = defineModel<Block[]>({ required: true });

type BlockType = Block['type'];

const BLOCK_TYPES: { type: BlockType; label: string; icon: unknown }[] = [
    { type: 'text', label: 'Text', icon: Type },
    { type: 'button', label: 'Button', icon: MousePointerClick },
    { type: 'code', label: 'Code', icon: Code },
    { type: 'delimiter', label: 'Delimiter', icon: Minus },
    { type: 'heading', label: 'Heading', icon: Heading },
    { type: 'image', label: 'Image', icon: Image },
    { type: 'list', label: 'List', icon: List },
];

const ALIGNABLE_TYPES: BlockType[] = ['text', 'heading', 'button'];

const defaultData = (type: BlockType): Record<string, unknown> => {
    switch (type) {
        case 'heading':
            return { level: 2, text: '', align: 'left' };
        case 'button':
            return { text: 'Click here', url: '', align: 'left' };
        case 'image':
            return { url: '', alt: '' };
        case 'list':
            return { style: 'unordered', text: '' };
        case 'code':
            return { code: '' };
        case 'delimiter':
            return {};
        default:
            return { text: '', align: 'left' };
    }
};

const newId = () =>
    typeof crypto !== 'undefined' && 'randomUUID' in crypto
        ? crypto.randomUUID()
        : `b${Date.now()}${Math.random().toString(36).slice(2)}`;

/* ----- Add menu ----- */
const menuOpen = ref(false);
const filter = ref('');
const addMenuRef = ref<HTMLElement | null>(null);

onClickOutside(addMenuRef, () => {
    menuOpen.value = false;
    filter.value = '';
});

const filteredTypes = computed(() => {
    const term = filter.value.trim().toLowerCase();

    return term
        ? BLOCK_TYPES.filter((option) =>
              option.label.toLowerCase().includes(term),
          )
        : BLOCK_TYPES;
});

const addBlock = (type: BlockType) => {
    blocks.value.push({ id: newId(), type, data: defaultData(type) });
    menuOpen.value = false;
    filter.value = '';
};

const removeBlock = (index: number) => blocks.value.splice(index, 1);

const move = (index: number, direction: -1 | 1) => {
    const target = index + direction;

    if (target < 0 || target >= blocks.value.length) {
        return;
    }

    const next = [...blocks.value];
    [next[index], next[target]] = [next[target], next[index]];
    blocks.value = next;
};

const blockLabel = (type: BlockType) =>
    BLOCK_TYPES.find((option) => option.type === type)?.label ?? type;

/* ----- Image upload ----- */
const uploadingId = ref<string | null>(null);

const csrfToken = () => {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
};

const uploadImage = async (block: Block, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploadingId.value = block.id;

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

        if (response.ok) {
            const { url } = (await response.json()) as { url: string };
            block.data.url = url;
        }
    } finally {
        uploadingId.value = null;
        input.value = '';
    }
};
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="!blocks.length"
            class="rounded-xl border border-dashed border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel)/0.5)] px-4 py-8 text-center text-sm text-[hsl(var(--ds-ink-faint))]"
        >
            Add a block to start building your email.
        </div>

        <!-- Blocks -->
        <div
            v-for="(block, index) in blocks"
            :key="block.id"
            class="group rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))]"
        >
            <div
                class="flex items-center justify-between border-b border-[hsl(var(--ds-line))] px-3 py-2"
            >
                <span
                    class="text-[10px] font-semibold tracking-[0.18em] text-[hsl(var(--ds-ink-faint))] uppercase"
                >
                    {{ blockLabel(block.type) }}
                </span>
                <div class="flex items-center gap-1">
                    <template v-if="ALIGNABLE_TYPES.includes(block.type)">
                        <button
                            v-for="a in (['left', 'center', 'right'] as const)"
                            :key="a"
                            type="button"
                            :aria-label="`Align ${a}`"
                            :class="[
                                'grid size-7 place-items-center rounded',
                                block.data.align === a
                                    ? 'text-[hsl(var(--ds-accent))]'
                                    : 'text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-ink))]',
                            ]"
                            @click="block.data.align = a"
                        >
                            <AlignLeft v-if="a === 'left'" class="size-4" />
                            <AlignCenter v-else-if="a === 'center'" class="size-4" />
                            <AlignRight v-else class="size-4" />
                        </button>
                        <div class="mx-0.5 h-4 w-px bg-[hsl(var(--ds-line))]" />
                    </template>
                    <button
                        type="button"
                        class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-ink))] disabled:opacity-40"
                        :disabled="index === 0"
                        aria-label="Move up"
                        @click="move(index, -1)"
                    >
                        <ChevronUp class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-ink))] disabled:opacity-40"
                        :disabled="index === blocks.length - 1"
                        aria-label="Move down"
                        @click="move(index, 1)"
                    >
                        <ChevronDown class="size-4" />
                    </button>
                    <button
                        type="button"
                        class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink-faint))] hover:text-[hsl(var(--ds-accent))]"
                        aria-label="Remove block"
                        @click="removeBlock(index)"
                    >
                        <Trash2 class="size-4" />
                    </button>
                </div>
            </div>

            <div class="space-y-3 p-3">
                <!-- Text -->
                <RichTextField
                    v-if="block.type === 'text'"
                    v-model="block.data.text"
                />

                <!-- Heading -->
                <template v-else-if="block.type === 'heading'">
                    <Select v-model="block.data.level">
                        <SelectTrigger
                            class="!h-10 w-32 border-[hsl(var(--ds-line))]"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="1">Heading 1</SelectItem>
                            <SelectItem :value="2">Heading 2</SelectItem>
                            <SelectItem :value="3">Heading 3</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        v-model="block.data.text"
                        class="h-10"
                        placeholder="Heading text"
                    />
                </template>

                <!-- Button -->
                <template v-else-if="block.type === 'button'">
                    <Input
                        v-model="block.data.text"
                        class="h-10"
                        placeholder="Button label"
                    />
                    <Input
                        v-model="block.data.url"
                        class="h-10"
                        placeholder="https://example.com"
                    />
                </template>

                <!-- Image -->
                <template v-else-if="block.type === 'image'">
                    <div class="flex gap-2">
                        <Input
                            v-model="block.data.url"
                            class="h-10 flex-1"
                            placeholder="Image URL"
                        />
                        <label
                            class="inline-flex h-10 cursor-pointer items-center gap-1.5 rounded-md border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper))] px-3 text-sm font-medium text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                        >
                            <Upload class="size-4" />
                            {{
                                uploadingId === block.id
                                    ? 'Uploading…'
                                    : 'Upload'
                            }}
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                :disabled="uploadingId === block.id"
                                @change="uploadImage(block, $event)"
                            />
                        </label>
                    </div>
                    <Input
                        v-model="block.data.alt"
                        class="h-10"
                        placeholder="Alt text"
                    />
                    <img
                        v-if="block.data.url"
                        :src="String(block.data.url)"
                        alt=""
                        class="max-h-40 rounded-md border border-[hsl(var(--ds-line))]"
                    />
                </template>

                <!-- List -->
                <template v-else-if="block.type === 'list'">
                    <Select v-model="block.data.style">
                        <SelectTrigger
                            class="!h-10 w-40 border-[hsl(var(--ds-line))]"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="unordered">Bulleted</SelectItem>
                            <SelectItem value="ordered">Numbered</SelectItem>
                        </SelectContent>
                    </Select>
                    <textarea
                        v-model="block.data.text"
                        rows="3"
                        placeholder="One item per line"
                        class="block w-full resize-y rounded-md border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.5)] px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ds-accent)/0.4)]"
                    ></textarea>
                </template>

                <!-- Code -->
                <textarea
                    v-else-if="block.type === 'code'"
                    v-model="block.data.code"
                    rows="4"
                    spellcheck="false"
                    placeholder="<div>…</div>"
                    class="block w-full resize-y rounded-md border border-[hsl(var(--ds-line))] bg-[hsl(27_12%_8%)] px-3 py-2 font-mono text-[13px] text-[hsl(40_30%_92%)] outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ds-accent)/0.4)]"
                ></textarea>

                <!-- Delimiter -->
                <div
                    v-else-if="block.type === 'delimiter'"
                    class="flex items-center justify-center py-2 text-[hsl(var(--ds-ink-faint))]"
                >
                    <Minus class="size-5" />
                    <Minus class="-ml-2 size-5" />
                </div>
            </div>
        </div>

        <!-- Add block -->
        <div ref="addMenuRef" class="relative">
            <button
                type="button"
                class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-[hsl(var(--ds-line))] py-2.5 text-sm font-medium text-[hsl(var(--ds-ink-soft))] transition-colors hover:bg-[hsl(var(--ds-accent)/0.06)] hover:text-[hsl(var(--ds-ink))]"
                @click="menuOpen = !menuOpen"
            >
                <Plus class="size-4" />
                Add block
            </button>

            <div
                v-if="menuOpen"
                class="absolute bottom-full left-0 z-20 mb-1 w-64 overflow-hidden rounded-xl border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] shadow-lg"
            >
                <div class="border-b border-[hsl(var(--ds-line))] p-2">
                    <Input
                        v-model="filter"
                        autofocus
                        placeholder="Filter"
                        class="h-9"
                    />
                </div>
                <ul class="max-h-64 overflow-y-auto py-1">
                    <li v-for="option in filteredTypes" :key="option.type">
                        <button
                            type="button"
                            class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.08)]"
                            @click="addBlock(option.type)"
                        >
                            <component
                                :is="option.icon"
                                class="size-4 text-[hsl(var(--ds-ink-faint))]"
                            />
                            {{ option.label }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
