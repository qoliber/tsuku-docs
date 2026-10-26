import { tsukuHostLanguages, tsukuLanguages } from './tsuku-grammar.js'

export default {
  title: 'Tsuku',
  description: 'A lightweight PHP templating library for transforming data into ANY text format',

  // vendor/ holds the Tsuku library that renders the examples (composer install) and snippets/ the
  // example sources (scripts/render-examples.php); neither contains pages
  srcExclude: ['vendor/**', 'snippets/**'],

  markdown: {
    // Template tabs use ```tsuku / ```tsuku-html / ... (see .vitepress/tsuku-grammar.js)
    languages: [...tsukuHostLanguages, ...tsukuLanguages]
  },

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'Examples', link: '/examples/' },
      { text: 'API', link: '/api/' },
      { text: 'About', link: '/about' },
      {
        text: 'v1.4.2',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'Upgrading from 1.3', link: '/guide/upgrading' }
        ]
      },
      { text: 'GitHub', link: 'https://github.com/qoliber/tsuku' }
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'What is Tsuku?', link: '/guide/what-is-tsuku' },
            { text: 'Getting Started', link: '/guide/getting-started' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Upgrading from 1.3', link: '/guide/upgrading' }
          ]
        },
        {
          text: 'Template Syntax',
          items: [
            { text: 'Variables', link: '/guide/variables' },
            { text: 'Loops', link: '/guide/loops' },
            { text: 'Conditionals', link: '/guide/conditionals' },
            { text: 'Pattern Matching', link: '/guide/pattern-matching' },
            { text: 'Whitespace Control', link: '/guide/whitespace-control' },
            { text: 'Line Continuation', link: '/guide/line-continuation' },
            { text: 'Escapes & Special Characters', link: '/guide/escaping' }
          ]
        },
        {
          text: 'Functions & Directives',
          items: [
            { text: 'Built-in Functions', link: '/guide/built-in-functions' },
            { text: 'Custom Functions', link: '/guide/custom-functions' },
            { text: 'Custom Directives', link: '/guide/custom-directives' },
            { text: 'Widget Support', link: '/guide/widget-support' }
          ]
        },
        {
          text: 'Advanced',
          items: [
            { text: 'Streaming Large Datasets', link: '/guide/streaming' },
            { text: 'Strictness Modes & Errors', link: '/guide/strictness' },
            { text: 'Performance & Benchmarks', link: '/guide/performance' },
            { text: 'Security & Escaping', link: '/guide/security' },
            { text: 'Architecture', link: '/guide/architecture' },
            { text: 'Best Practices', link: '/guide/best-practices' }
          ]
        }
      ],
      '/examples/': [
        {
          text: 'Examples',
          items: [
            { text: 'Overview', link: '/examples/' },
            { text: 'CSV Exports', link: '/examples/csv-exports' },
            { text: 'XML Generation', link: '/examples/xml-generation' },
            { text: 'JSON APIs', link: '/examples/json-apis' },
            { text: 'HTML Generation', link: '/examples/html-generation' },
            { text: 'Configuration Files', link: '/examples/configuration-files' },
            { text: 'E-commerce', link: '/examples/ecommerce' }
          ]
        }
      ],
      '/api/': [
        {
          text: 'API Reference',
          items: [
            { text: 'Overview', link: '/api/' },
            { text: 'Tsuku Class', link: '/api/tsuku' },
            { text: 'Template Syntax Reference', link: '/api/syntax' },
            { text: 'Function Registry', link: '/api/function-registry' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/qoliber/tsuku' }
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2025 qoliber'
    },

    search: {
      provider: 'local'
    }
  }
}
