# Design System Strategy: SOS Consumidor

## 1. Overview & Creative North Star
**Creative North Star: The Judicial Guardian**

This design system is engineered to bridge the gap between high-stakes legal authority and accessible modern technology. In the legal-tech space, users are often in a state of stress; the UI must act as a calming, authoritative presence. We move away from the "generic corporate dashboard" by adopting a **High-End Editorial** approach. 

The system utilizes intentional asymmetry—placing heavy display typography against expansive white space—to create a sense of bespoke craftsmanship. By eschewing standard grid-locked boxes in favor of layered, tonal surfaces, we communicate a sophisticated narrative: that consumer rights are not just data points, but a premium service.

## 2. Colors & Surface Architecture
The palette is rooted in an authoritative Red (`primary: #af101a`), supported by a technical Blue (`secondary: #0752d0`).

### The "No-Line" Rule
To achieve a premium feel, **1px solid borders are strictly prohibited for sectioning.** Boundaries must be defined through background color shifts or tonal transitions.
- **Surface:** `#fff8f7` (Base page background)
- **Surface-Container-Low:** `#fff0ef` (Subtle sectioning)
- **Surface-Container-Highest:** `#f9dcd9` (High-priority callouts)

### Surface Hierarchy & Nesting
Treat the UI as a series of physical layers. Use the `surface-container` tiers to create depth. For example, a "Case Progress" card (`surface_container_lowest: #ffffff`) should sit on a "Dashboard" background (`surface_container_low: #fff0ef`). This "nested" depth provides structural clarity without the visual noise of dividers.

### The Glass & Gradient Rule
For floating elements like navigation bars or "Quick Action" menus, use **Glassmorphism**.
- **Execution:** Use `surface` at 80% opacity with a `backdrop-filter: blur(20px)`.
- **Signature Textures:** For Hero CTAs, use a subtle linear gradient from `primary` (#af101a) to `primary_container` (#d32f2f) at a 135-degree angle. This adds "visual soul" and depth that flat color cannot replicate.

## 3. Typography: The Manrope Scale
Manrope's geometric yet approachable nature is used here to convey "Modern Authority."

| Role | Token | Size | Weight | Character |
| :--- | :--- | :--- | :--- | :--- |
| **Hero Display** | `display-lg` | 3.5rem | 800 | Extra-bold for impact. Use for primary headlines. |
| **Section Head** | `headline-md` | 1.75rem | 600 | Authoritative but readable. |
| **Subsection** | `title-md` | 1.125rem | 500 | Medium weight for structural clarity. |
| **Main Copy** | `body-lg` | 1rem | 400 | Optimized for long-form legal reading. |
| **Caption/Tag** | `label-md` | 0.75rem | 600 | All-caps with 0.05em tracking for metadata. |

## 4. Elevation & Depth
In this system, elevation is a result of **Tonal Layering**, not structural lines.

- **The Layering Principle:** Depth is achieved by stacking. A card in `surface_container_lowest` (#ffffff) placed on a `surface_dim` (#f1d3d0) section creates a natural lift.
- **Ambient Shadows:** When a shadow is required (e.g., for a modal), it must be "Ambient."
    - **Specs:** `0px 24px 48px rgba(39, 24, 22, 0.08)`. Use a tint of `on_surface` rather than pure black to keep the light feeling natural.
- **The "Ghost Border" Fallback:** If a border is required for accessibility (e.g., input fields), use `outline_variant` (#e4beba) at **20% opacity**. Never use 100% opaque, high-contrast borders.

## 5. Components

### Buttons & Interaction
- **Primary:** Gradient fill (`primary` to `primary_container`), `8px` (0.5rem) corner radius. Use `on_primary` (#ffffff) for text.
- **Secondary:** Transparent background with a `Ghost Border` and `secondary` (#0752d0) text.
- **State Change:** On hover, primary buttons should shift +10% in saturation, never change color entirely.

### Cards & Content Lists
- **Rule:** **Forbid divider lines.** 
- Use vertical whitespace (Spacing `8`: 2rem) or background shifts to separate content. 
- A list of legal documents should be a series of `surface_container_low` tiles on a `surface` background.

### Custom Component: The "Case Status" Chip
Use `tertiary_container` (#cf3800) for "Urgent" states and `secondary_container` (#366dea) for "In Progress." These chips should be pill-shaped (`roundness: full`) to contrast against the 8px radius of the primary UI.

### Input Fields
- **Style:** `surface_container_lowest` (#ffffff) background.
- **Focus:** A 2px outer glow of `primary_fixed` (#ffdad6) instead of a hard border change. This mimics the "soft legal" aesthetic.

## 6. Do’s and Don’ts

### Do
- **Do** use generous whitespace (Spacing `12` or `16`) between major editorial sections.
- **Do** use the life-preserver icon from the logo as a subtle watermark or background element in hero sections (at 5% opacity).
- **Do** ensure all typography maintains a high contrast ratio against surface tiers for legal accessibility.

### Don’t
- **Don't** use 1px solid black or grey borders. They break the "editorial" flow.
- **Don't** use standard "drop shadows." Only use the Ambient Shadows defined in Section 4.
- **Don't** use sharp corners. Every container must strictly adhere to the `0.5rem` (8px) roundness scale to maintain the modern, approachable feel.
- **Don't** overcrowd the screen. If a view feels "busy," increase the surface-container tiering rather than adding lines.