<script setup lang="ts">
import { html } from '@codemirror/lang-html';
import { EditorState } from '@codemirror/state';
import { oneDark } from '@codemirror/theme-one-dark';
import { basicSetup, EditorView } from 'codemirror';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{ modelValue: string }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

const element = ref<HTMLDivElement>();
let view: EditorView | undefined;

onMounted(() => {
    view = new EditorView({
        parent: element.value,
        state: EditorState.create({
            doc: props.modelValue,
            extensions: [
                basicSetup,
                html(),
                oneDark,
                EditorView.lineWrapping,
                EditorView.updateListener.of((update) => {
                    if (!update.docChanged) {
                        return;
                    }

                    const value = update.state.doc.toString();

                    if (value !== props.modelValue) {
                        emit('update:modelValue', value);
                    }
                }),
            ],
        }),
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (view && value !== view.state.doc.toString()) {
            view.dispatch({
                changes: { from: 0, to: view.state.doc.length, insert: value },
            });
        }
    },
);

onBeforeUnmount(() => view?.destroy());
</script>

<template>
    <div
        ref="element"
        class="ds-code overflow-hidden rounded-xl border border-[hsl(var(--ds-line))]"
    ></div>
</template>

<style>
.ds-code .cm-editor {
    height: 60vh;
}
.ds-code .cm-editor.cm-focused {
    outline: none;
}
.ds-code .cm-scroller {
    font-family: ui-monospace, 'SF Mono', Menlo, monospace;
    font-size: 13px;
}
</style>
