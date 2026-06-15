<script setup lang="ts">
import { Bold, Code, Italic, Link2, Underline } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const model = defineModel<string>({ required: true });

const editor = ref<HTMLDivElement>();
const show = ref(false);
const top = ref(0);
const left = ref(0);

const sync = () => {
    if (editor.value) {
        model.value = editor.value.innerHTML;
    }
};

const updateToolbar = () => {
    const selection = window.getSelection();

    if (
        !selection ||
        selection.isCollapsed ||
        !editor.value ||
        !selection.anchorNode ||
        !editor.value.contains(selection.anchorNode)
    ) {
        show.value = false;

        return;
    }

    const rect = selection.getRangeAt(0).getBoundingClientRect();
    const host = editor.value.getBoundingClientRect();

    top.value = rect.top - host.top - 44;
    left.value = rect.left - host.left + rect.width / 2;
    show.value = true;
};

const exec = (command: string, value?: string) => {
    document.execCommand(command, false, value);
    editor.value?.focus();
    sync();
    updateToolbar();
};

const addLink = () => {
    const url = window.prompt('Link URL');

    if (url) {
        exec('createLink', url);
    }
};

const wrapCode = () => {
    const selection = window.getSelection();

    if (!selection || selection.isCollapsed) {
        return;
    }

    const code = document.createElement('code');
    code.style.cssText =
        'background:#f1f5f9;padding:2px 4px;border-radius:4px;font-size:0.9em;';

    try {
        selection.getRangeAt(0).surroundContents(code);
        sync();
    } catch {
        // Selection spanned multiple elements; ignore.
    }
};

const hideSoon = () => window.setTimeout(() => (show.value = false), 150);

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = model.value;
    }

    document.addEventListener('selectionchange', updateToolbar);
});

onBeforeUnmount(() =>
    document.removeEventListener('selectionchange', updateToolbar),
);

watch(model, (value) => {
    if (editor.value && value !== editor.value.innerHTML) {
        editor.value.innerHTML = value;
    }
});
</script>

<template>
    <div class="relative">
        <div
            ref="editor"
            contenteditable="true"
            role="textbox"
            class="ds-rich min-h-20 rounded-md border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-paper)/0.5)] px-3 py-2 text-sm leading-relaxed text-[hsl(var(--ds-ink))] outline-none focus-visible:ring-2 focus-visible:ring-[hsl(var(--ds-accent)/0.4)]"
            @input="sync"
            @mouseup="updateToolbar"
            @keyup="updateToolbar"
            @blur="hideSoon"
        ></div>

        <div
            v-if="show"
            class="absolute z-30 flex -translate-x-1/2 items-center gap-0.5 rounded-lg border border-[hsl(var(--ds-line))] bg-[hsl(var(--ds-panel))] p-1 shadow-lg"
            :style="{ top: `${top}px`, left: `${left}px` }"
        >
            <button
                type="button"
                class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.1)]"
                aria-label="Bold"
                @mousedown.prevent="exec('bold')"
            >
                <Bold class="size-4" />
            </button>
            <button
                type="button"
                class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.1)]"
                aria-label="Italic"
                @mousedown.prevent="exec('italic')"
            >
                <Italic class="size-4" />
            </button>
            <button
                type="button"
                class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.1)]"
                aria-label="Underline"
                @mousedown.prevent="exec('underline')"
            >
                <Underline class="size-4" />
            </button>
            <span class="mx-0.5 h-4 w-px bg-[hsl(var(--ds-line))]"></span>
            <button
                type="button"
                class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.1)]"
                aria-label="Link"
                @mousedown.prevent="addLink"
            >
                <Link2 class="size-4" />
            </button>
            <button
                type="button"
                class="grid size-7 place-items-center rounded text-[hsl(var(--ds-ink))] hover:bg-[hsl(var(--ds-accent)/0.1)]"
                aria-label="Inline code"
                @mousedown.prevent="wrapCode"
            >
                <Code class="size-4" />
            </button>
        </div>
    </div>
</template>

<style>
.ds-rich a {
    color: hsl(var(--ds-accent-ink));
    text-decoration: underline;
}
.ds-rich:empty::before {
    content: 'Write some text…';
    color: hsl(var(--ds-ink-faint));
}
</style>
