# Design System Strategy: The Sovereign Advocate

## 1. Overview & Creative North Star
**Creative North Star: The Digital Curator**
This design system moves away from the cold, clinical "spreadsheet" aesthetic common in LegalTech. Instead, it adopts the persona of a high-end editorial publication—think *The Economist* meets *Stripe*. We achieve "Trustworthy" not through heavy borders and dark colors, but through expansive whitespace, authoritative typography, and a "layered glass" architecture.

The goal is to transform complex legal jargon into a guided, premium experience. We break the standard grid by using intentional asymmetry: wide margins for reading focus and offset "floating" components that suggest agility and AI-driven intelligence. This is a system of depth, not lines.

---

## 2. Color & Tonal Architecture
We utilize a sophisticated palette that balances the gravitas of Law with the kinetic energy of AI.

### The "No-Line" Rule
**Strict Mandate:** Designers are prohibited from using 1px solid borders for sectioning or container definition. Boundaries must be defined solely through:
- **Tonal Shifts:** Placing a `surface-container-low` card on a `surface` background.
- **Negative Space:** Using the Spacing Scale (e.g., `8` or `12`) to create mental groupings.

### Surface Hierarchy & Nesting
Treat the UI as a series of physical layers. Use the surface tiers to create "nested" depth:
- **Base Layer:** `surface` (#f9f9ff) – The primary canvas.
- **Content Blocks:** `surface-container-low` (#f0f3ff) – For secondary content regions.
- **Interactive Cards:** `surface-container-lowest` (#ffffff) – To make primary actions "pop" against the background.
- **Overlays/Navigation:** `surface-container-highest` (#d8e3fb) – Reserved for the most prominent elevated elements.

### The "Glass & Gradient" Rule
To signify the "AI" layer, use **Glassmorphism**. Floating AI modules (like chatbots or insight chips) should use a semi-transparent `surface-variant` with a `backdrop-blur` of 12px–20px. 
**Signature Texture:** Use a subtle linear gradient from `primary` (#000666) to `primary-container` (#1a237e) for Hero CTAs. This creates a "deep ink" feel that looks more premium than a flat fill.

---

## 3. Typography: The Editorial Voice
We use a dual-font strategy to balance heritage and technology.

*   **Display & Headlines (Manrope):** Chosen for its geometric precision and modern "tech" terminals. Use `display-lg` for hero statements to command authority.
*   **Body & UI (Inter):** The workhorse. Its high x-height ensures legal documents remain readable even at `body-sm`.

**Hierarchy as Identity:**
- **Authority:** Use `headline-lg` in `on_surface` for section titles. 
- **Guidance:** Use `title-md` in `secondary` (#515f74) for subheaders to create a soft visual hierarchy that doesn't overwhelm the user.
- **The "AI" Accent:** Small labels or AI-generated insights should use `label-md` with `tertiary_fixed_dim` text on a `tertiary_container` background.

---

## 4. Elevation & Depth
Depth in this system is organic, mimicking natural light rather than digital "dropshadows."

*   **The Layering Principle:** Stack containers. An input field (`surface_container_lowest`) sitting inside a sidebar (`surface_container_low`) provides all the contrast needed without a single stroke.
*   **Ambient Shadows:** For floating elements, use a "Cloud Shadow":
    *   *Y: 8px, Blur: 32px, Color:* `on_surface` at 6% opacity.
    *   This mimics a soft, ambient lift.
*   **The "Ghost Border" Fallback:** If a border is required for accessibility (e.g., in a high-density table), use `outline-variant` (#c6c5d4) at **15% opacity**. It should be felt, not seen.

---

## 5. Components

### Buttons
- **Primary:** Gradient fill (`primary` to `primary-container`). `Rounded-md` (0.75rem). No border.
- **Secondary:** `surface-container-high` background with `on-primary-fixed-variant` text.
- **Tertiary (Ghost):** No background. `on-surface` text with an underline that appears on hover.

### Cards & Lists
**Forbid the use of divider lines.**
- Separate list items using `spacing-4` (1rem) vertical gaps or alternating background shifts between `surface` and `surface-container-low`.
- **Legal Document Cards:** Use `rounded-lg` (1rem) and a `surface-container-lowest` fill.

### Input Fields
- **Default State:** `surface-container-highest` background, no border. `rounded-sm`.
- **Focus State:** A 2px "Halo" using `primary_fixed` (#e0e0ff) instead of a hard border.
- **Error State:** Background shifts to `error_container`, text to `on_error_container`.

### AI Insights (Custom Component)
- **Style:** A "Frosted Glass" card using `tertiary_container` at 80% opacity with a `backdrop-blur`.
- **Purpose:** To display AI-driven advice or "Consumer Rights" summaries. It should feel like it's floating above the "static" legal data.

---

## 6. Do’s and Don’ts

### Do
- **Do** use `20` (5rem) or `24` (6rem) spacing for top-level section margins to create an elite, spacious feel.
- **Do** use `tertiary` (Electric Purple/Blue) sparingly—only for AI-generated moments or "Eureka" insights.
- **Do** prioritize high contrast for body text (`on_surface` on `surface`) to ensure legal accessibility.

### Don’t
- **Don’t** use pure black (#000000). Use `on_surface` (#111c2d) for a deeper, more professional "Navy" tone.
- **Don’t** use sharp 0px corners. Even the most professional legal documents benefit from the `DEFAULT` (0.5rem) radius to appear approachable.
- **Don’t** use standard "Blue" links. Use `primary` with a 2px offset underline to maintain the editorial look.