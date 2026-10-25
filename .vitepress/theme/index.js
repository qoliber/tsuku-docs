import DefaultTheme from 'vitepress/theme'
import HtmlPreview from './HtmlPreview.vue'

export default {
  extends: DefaultTheme,
  enhanceApp({ app }) {
    app.component('HtmlPreview', HtmlPreview)
  }
}
