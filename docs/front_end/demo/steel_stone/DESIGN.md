# Design System Document: Architectural Precision

## 1. Overview & Creative North Star: "The Structural Monolith"
This design system moves away from the generic "blue-and-red" corporate template to embrace **The Structural Monolith**. Inspired by the precision of modern architecture, the system utilizes massive, editorial typography and tonal layering to convey weight, stability, and permanence. 

The "template" look is intentionally broken through **Assymetric Anchoring**. While the layout adheres to a grid, elements are allowed to "hang" or "float" across sections—mimicking architectural cantilevers. We replace rigid lines with shifts in material weight, creating a digital environment that feels as solid and intentional as a skyscraper.

---

## 2. Colors & Materiality
We leverage a sophisticated palette where deep, saturated tones meet airy, expansive neutrals.

### The "No-Line" Rule
Traditional 1px borders are strictly prohibited for sectioning. Boundaries are defined by transitions between `surface` tiers. To separate a section, shift from `surface` to `surface-container-low`. The change should be felt, not seen as a stroke.

### Surface Hierarchy & Nesting
Treat the UI as a series of physical materials:
- **Foundational Layer:** `surface` (#f8f9ff) serves as our site-wide canvas.
- **Structural Insets:** Use `surface-container` to define content areas.
- **The "Elevated Slab":** Place `surface-container-lowest` (#ffffff) cards on top of `surface-container-low` (#eff4ff) backgrounds to create a soft, natural lift.

### The "Glass & Gradient" Rule
To add "soul" to the corporate aesthetic, the `primary` (#610005) and `secondary` (#4b57aa) colors should never be purely flat in large areas. Use a subtle linear gradient (Top-Left to Bottom-Right) transitioning from the core color to its `container` variant. 
*Example: A Hero CTA button should transition from `primary` to `primary_container` at a 15-degree angle.*

---

## 3. Typography: Editorial Authority
The type scale is designed to feel like an architectural blueprint—precise, rhythmic, and commanding.

- **Display & Headlines (Plus Jakarta Sans):** Chosen for its geometric clarity. Use `display-lg` (3.5rem) with tight letter-spacing (-0.02em) for hero statements to project "Big Industry" authority.
- **Body & Labels (Inter):** Inter provides a technical, high-legibility contrast. Use `body-lg` (1rem) for general descriptions to maintain an airy, modern feel.
- **The Hierarchy Rule:** Headings should be 2.5x the size of the body text to ensure a clear, editorial hierarchy that guides the user through complex construction data.

---

## 4. Elevation & Depth: Tonal Layering
We reject the "drop shadow" defaults of the early web.

- **The Layering Principle:** Depth is achieved by stacking. A `surface-container-highest` element placed on a `surface` background provides all the "elevation" needed without a single shadow.
- **Ambient Shadows:** For floating elements (like Navigation or Modals), use an ultra-diffused shadow: `box-shadow: 0 20px 40px rgba(18, 28, 42, 0.06)`. The tint is derived from `on_surface` to ensure it feels like a natural shadow cast on stone.
- **The "Ghost Border":** If a button or input requires a boundary for accessibility, use `outline_variant` at **15% opacity**. This creates a "suggestion" of a line that disappears into the background.
- **Glassmorphism:** Navigation bars should use `surface` at 80% opacity with a `backdrop-filter: blur(12px)`. This allows the vibrant imagery of construction projects to bleed through the UI, softening the interface.

---

## 5. Components

### Buttons: The "Beams"
- **Primary:** Gradient from `primary` to `primary_container`. Border radius `md` (0.75rem). No shadow, but a subtle `outline` at 10% opacity for definition.
- **Secondary:** `surface_container_highest` with `on_surface` text. This feels heavy and "concrete-like."
- **Tertiary:** Pure text with an `icon-end`. On hover, a 2px underline emerges from the center—mimicking a surveyor’s line.

### Cards: The "Modules"
- **Rules:** No dividers. Use `spacing-8` (2rem) of internal padding.
- **Visuals:** Cards must use `surface-container-lowest` on top of `surface-container-low`.
- **Hover:** On hover, the card doesn't just lift; it should swap to a `surface-bright` state with a subtle `secondary` (blue) 2px accent on the left edge.

### Input Fields: The "Blueprints"
- **State:** Labels use `label-md` in `on_surface_variant`. 
- **Style:** Background is `surface_container_low`. On focus, the background transitions to `surface_container_lowest` and the "Ghost Border" becomes 100% `secondary`.

### Architectural Components (Context Specific)
- **Project Stats:** Large `display-sm` numbers in `secondary` with `label-sm` descriptors.
- **The "Blueprint Grid":** Use a subtle background pattern of dots or a light grid (using `outline_variant` at 5% opacity) in the hero section to reinforce the construction theme.

---

## 6. Do's and Don'ts

### Do:
- **Embrace White Space:** Use `spacing-24` (6rem) between major sections. Construction is about scale; your layout should feel massive.
- **Use High-Contrast Images:** Place full-bleed construction photography behind semi-transparent `surface` containers.
- **Asymmetric Balance:** Align a headline to the left and a supporting paragraph to the far right of the grid to create visual tension.

### Don't:
- **Don't use 1px black borders.** It cheapens the "High-End" architectural feel.
- **Don't use pure black text.** Always use `on_surface` (#121c2a) for a softer, more premium deep-blue-ink look.
- **Don't crowd the grid.** If a section feels "full," increase the spacing by 1.5x.