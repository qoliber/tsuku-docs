// Shiki (TextMate) grammars for Tsuku templates.
//
// `tsuku` highlights Tsuku over plain text (CSV, TSV, text, .env, nginx, TOML). The
// `tsuku-<host>` variants highlight the host format (HTML, XML, JSON, YAML) and inject the
// Tsuku rules everywhere inside it - also inside attribute values, JSON strings and CSS -
// the same way the PHP grammar injects `<?php` into HTML.
//
// Only hosts whose grammar scans token by token work: the TOML, INI and nginx grammars
// match whole lines such as `{name} = "..."` or `location = {path} {` in one regex, so a
// {variable} inside is never highlighted and TOML marks the rest of the line invalid.
//
// Colours come from the default VitePress themes (github-light / github-dark):
//   directives and operators   keyword            (red)
//   functions                  entity.name        (purple)
//   {variables} and data paths variable           (orange)
//   strings, numbers, @@, \    string / constant  (blue)

const NAME = '[A-Za-z_][A-Za-z0-9_]*'
const PATH = `${NAME}(?:\\.[A-Za-z0-9_]+)*`
const BLOCK_DIRECTIVES = 'for|if|elseif|unless|match|case'

const repository = {
  tsuku: {
    patterns: [
      { include: '#escape' },
      { include: '#inline-condition' },
      { include: '#block-directive' },
      { include: '#function' },
      { include: '#bare-directive' },
      { include: '#variable' },
      { include: '#line-continuation' }
    ]
  },
  // @@ prints a literal @
  escape: {
    match: '@@',
    name: 'constant.character.escape.tsuku'
  },
  // a backslash at the end of a line joins it with the next one
  'line-continuation': {
    match: '\\\\$',
    name: 'constant.character.escape.line-continuation.tsuku'
  },
  // {plain.path} - anything else in braces is text
  variable: {
    match: `\\{${PATH}\\}`,
    name: 'variable.parameter.tsuku'
  },
  // @?{condition "yes" : "no"}
  'inline-condition': {
    begin: '@\\?\\{',
    end: '\\}',
    beginCaptures: { 0: { name: 'keyword.control.conditional.tsuku' } },
    endCaptures: { 0: { name: 'keyword.control.conditional.tsuku' } },
    name: 'meta.tsuku.inline-condition',
    patterns: [
      { match: '(?<=\\s)\\?(?=\\s)', name: 'keyword.operator.ternary.tsuku' },
      { match: ':', name: 'keyword.operator.ternary.tsuku' },
      { include: '#expression' }
    ]
  },
  // @for(...) @if(...) @elseif(...) @unless(...) @match(...) @case(...)
  'block-directive': {
    begin: `(@)(${BLOCK_DIRECTIVES})\\s*(\\()`,
    end: '\\)',
    beginCaptures: {
      1: { name: 'keyword.control.tsuku punctuation.definition.keyword.tsuku' },
      2: { name: 'keyword.control.tsuku' },
      3: { name: 'punctuation.section.parens.begin.tsuku' }
    },
    endCaptures: { 0: { name: 'punctuation.section.parens.end.tsuku' } },
    name: 'meta.tsuku.directive',
    patterns: [
      { match: '\\bas\\b', name: 'keyword.other.as.tsuku' },
      { include: '#expression' }
    ]
  },
  // @else @default @end and custom block directives used without arguments
  'bare-directive': {
    match: `(@)(${NAME})(?![A-Za-z0-9_(])`,
    captures: {
      1: { name: 'keyword.control.tsuku punctuation.definition.keyword.tsuku' },
      2: { name: 'keyword.control.tsuku' }
    }
  },
  // @name(arguments) - built-in or registered functions (custom directives with arguments look the same)
  function: {
    begin: `(@)(${NAME})(\\()`,
    end: '\\)',
    beginCaptures: {
      1: { name: 'entity.name.function.tsuku punctuation.definition.function.tsuku' },
      2: { name: 'entity.name.function.tsuku' },
      3: { name: 'punctuation.section.arguments.begin.tsuku' }
    },
    endCaptures: { 0: { name: 'punctuation.section.arguments.end.tsuku' } },
    name: 'meta.tsuku.function-call',
    patterns: [{ include: '#expression' }]
  },
  expression: {
    patterns: [
      { include: '#string' },
      { include: '#function' },
      { include: '#parens' },
      { match: '\\b(and|or|not|in)\\b', name: 'keyword.operator.logical.tsuku' },
      { match: '==|!=|>=|<=|>|<|&&|\\|\\||!', name: 'keyword.operator.comparison.tsuku' },
      { match: '-?\\b\\d+(?:\\.\\d+)?\\b', name: 'constant.numeric.tsuku' },
      { match: PATH, name: 'variable.parameter.tsuku' },
      { match: ',', name: 'punctuation.separator.comma.tsuku' }
    ]
  },
  parens: {
    begin: '\\(',
    end: '\\)',
    patterns: [{ include: '#expression' }]
  },
  string: {
    patterns: [
      { match: '"(?:[^"\\\\]|\\\\.)*"', name: 'string.quoted.double.tsuku' },
      { match: "'(?:[^'\\\\]|\\\\.)*'", name: 'string.quoted.single.tsuku' }
    ]
  }
}

function language(name, displayName, host) {
  const scopeName = host ? `source.tsuku.${host.lang}` : 'source.tsuku'
  const grammar = {
    name,
    displayName,
    scopeName,
    patterns: [{ include: '#tsuku' }],
    repository
  }
  if (host) {
    grammar.embeddedLangs = [host.lang]
    grammar.patterns.push({ include: host.scope })
    // L: = the Tsuku rules win over the host rules at the same position; not inside Tsuku's own constructs
    grammar.injections = {
      [`L:${scopeName} - meta.tsuku`]: { patterns: [{ include: '#tsuku' }] }
    }
  }
  return grammar
}

// Host grammars must be loaded before the languages that embed them.
export const tsukuHostLanguages = ['html', 'xml', 'json', 'yaml']

export const tsukuLanguages = [
  language('tsuku', 'Tsuku'),
  language('tsuku-html', 'Tsuku (HTML)', { lang: 'html', scope: 'text.html.basic' }),
  language('tsuku-xml', 'Tsuku (XML)', { lang: 'xml', scope: 'text.xml' }),
  language('tsuku-json', 'Tsuku (JSON)', { lang: 'json', scope: 'source.json' }),
  language('tsuku-yaml', 'Tsuku (YAML)', { lang: 'yaml', scope: 'source.yaml' })
]
