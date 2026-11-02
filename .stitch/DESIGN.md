# Design System: KFS Runners Race Control
**Project ID:** 3487148057369341536

## 1. Visual Theme & Atmosphere
An athletic operations workspace for the KFS Runners race team. It should feel dependable, energetic, and calm under pressure: a soft neutral canvas, crisp white work surfaces, deep club-green navigation and primary actions, and a controlled red accent reserved for KFS identity and destructive or urgent states. Use clear hierarchy and generous space around operational data rather than decorative clutter.

## 2. Color Palette & Roles
- **Trackside Mist (#F5F8F6):** main page canvas, keeps long data views easy on the eyes.
- **Clean White (#FFFFFF):** cards, tables, and form surfaces.
- **Club Pine (#052F29):** sidebar, high-emphasis headings, and key navigation moments.
- **Race Green (#064B3E):** primary actions, active navigation, and confirmed states.
- **Finish Red (#F22632):** KFS brand accent, selected emphasis, and destructive actions only.
- **Field Green (#EAF4EF):** quiet active surfaces, success highlights, and subtle chart fills.
- **Warm Sand (#F7EEE2):** pending or caution states.
- **Runner Ink (#14312E):** primary text.
- **Trail Slate (#70817B):** secondary labels and supporting text.
- **Lane Divider (#E5ECE9):** quiet separators and control outlines.

## 3. Typography Rules
Use Plus Jakarta Sans for English interface text, with Cairo for Arabic and RTL pages. Headlines are compact and confident; body copy remains plain and readable. Use bold numerals for KPIs, and a monospaced style for race numbers, references, and other identifiers. Labels should be short, restrained, and consistently aligned.

## 4. Component Stylings
- **Buttons:** compact rounded rectangles with clear verb-first labels. Primary actions use Race Green and white text; secondary actions use white with a Lane Divider border; destructive actions use Finish Red and should stay visually separated from routine tasks.
- **Cards and Containers:** white surfaces with subtle borders, 12–16px rounded corners, and very light shadows. KPI cards use one small colored icon or status accent, never a heavy gradient.
- **Inputs and Filters:** white, low-contrast controls with visible focus rings and enough height for touch use. Filters should be grouped together above the data they affect.
- **Tables:** compact but breathable rows, sticky-feeling headers, subtle separators, and pill status labels. Race bib numbers and registration references use monospaced text.
- **Status:** green for paid/confirmed, sand/amber for pending, red for failed or destructive, and muted slate for neutral information.

## 5. Layout Principles
Use a persistent desktop navigation rail and a flexible main workspace. Keep a single clear page title, an explicit primary action, and compact summary cards above analytics. Place the race-date/countdown control as a compact, useful banner. Put registrations in a full-width table that can scroll horizontally on small screens; retain bib number, runner name, age and age group, distance, and payment state. At tablet widths, collapse the sidebar into a drawer and reduce analytics to one column. At mobile widths, prioritize KPIs and the registration list, preserve touch targets, and prevent page-level horizontal overflow. Support both LTR English and RTL Arabic.
