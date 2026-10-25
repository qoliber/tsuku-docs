<script setup>
// Shows an example's HTML output the way a browser renders it.
//
// The output goes into a sandboxed iframe (srcdoc). The sandbox allows no scripts, forms or
// popups; "allow-same-origin" only lets this component read the rendered height. Links get
// target="_blank", which the sandbox blocks, so clicking around a preview goes nowhere.
// HTML fragments get html-preview.css (product cards, tables, forms) in the current light or
// dark colours; a complete document such as an e-mail keeps its own styles.
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useData } from 'vitepress'
import previewCss from './html-preview.css?raw'

const props = defineProps({
  html: { type: String, required: true },
  label: { type: String, default: 'Preview' }
})

const { isDark } = useData()
const frame = ref(null)
const height = ref(160)
const mounted = ref(false)
let resizeObserver = null

const srcdoc = computed(() => {
  const theme = isDark.value ? 'dark' : 'light'
  const base = '<base target="_blank"><meta name="viewport" content="width=device-width, initial-scale=1">'
  if (/^\s*(<!doctype|<html)/i.test(props.html)) {
    return props.html
      .replace(/<html([^>]*)>/i, `<html$1 data-preview="document">`)
      .replace(/<head([^>]*)>/i, `<head$1>${base}<style>html { overflow-y: hidden; }</style>`)
  }
  return `<!doctype html><html class="${theme}" data-preview="fragment"><head><meta charset="utf-8">${base}` +
    `<style>${previewCss}</style></head><body>${props.html}</body></html>`
})

function measure() {
  const doc = frame.value?.contentDocument
  if (doc?.documentElement) {
    height.value = Math.max(40, Math.ceil(doc.documentElement.getBoundingClientRect().height))
  }
}

function onLoad() {
  const doc = frame.value?.contentDocument
  if (!doc) {
    return
  }
  measure()
  // images and fonts change the height after the first layout
  doc.addEventListener('load', measure, true)
}

onMounted(() => {
  mounted.value = true
  resizeObserver = new ResizeObserver(measure)
  resizeObserver.observe(frame.value)
})

onBeforeUnmount(() => resizeObserver?.disconnect())
</script>

<template>
  <figure class="html-preview">
    <figcaption class="html-preview-bar">
      <span class="html-preview-dots" aria-hidden="true"><i /><i /><i /></span>
      {{ label }}
    </figcaption>
    <iframe
      ref="frame"
      :srcdoc="mounted ? srcdoc : undefined"
      sandbox="allow-same-origin"
      :title="label"
      :style="{ height: `${height}px` }"
      @load="onLoad"
    />
  </figure>
</template>

<style scoped>
.html-preview {
  margin: 16px 0;
  border: 1px solid var(--vp-c-divider);
  border-radius: 8px;
  overflow: hidden;
  background: var(--vp-c-bg);
}

.html-preview-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 12px;
  border-bottom: 1px solid var(--vp-c-divider);
  background: var(--vp-c-bg-soft);
  color: var(--vp-c-text-2);
  font-size: 12px;
  font-weight: 500;
  line-height: 20px;
}

.html-preview-dots {
  display: inline-flex;
  gap: 5px;
}

.html-preview-dots i {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: var(--vp-c-divider);
}

.html-preview iframe {
  display: block;
  width: 100%;
  border: 0;
  background: transparent;
}
</style>
