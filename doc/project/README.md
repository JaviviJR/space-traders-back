# Project PDFs

Visual explainers generated from scripts. The source of truth stays in
`doc/*.md` and the code; these PDFs only summarize it, so regenerate them when
the area they cover changes.

## Layout

- `script/`: one generator script per document.
- `<feature>/`: the generated PDFs, one folder per functional area (e.g.
  `auth/`). Create a new folder when documenting a new area.
- `package.json` and `node_modules/` sit at this folder's root, shared by every
  script. `node_modules/` is git-ignored.

## Build

Install the dependencies once (the user runs it):

```bash
npm install --prefix doc/project
```

Then regenerate:

```bash
npm run build --prefix doc/project
```

Scripts use [pdfkit](https://pdfkit.org/) with the standard PDF fonts, which
only cover Latin-1: no Unicode arrows or symbols outside it in the text.
