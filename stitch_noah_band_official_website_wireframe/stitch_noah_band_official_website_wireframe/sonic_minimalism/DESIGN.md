---
name: Sonic Minimalism
colors:
  surface: '#fff8f7'
  surface-dim: '#f1d3d2'
  surface-bright: '#fff8f7'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#fff0ef'
  surface-container: '#ffe9e8'
  surface-container-high: '#ffe1e0'
  surface-container-highest: '#f9dcda'
  on-surface: '#271717'
  on-surface-variant: '#5b403f'
  inverse-surface: '#3e2c2b'
  inverse-on-surface: '#ffedeb'
  outline: '#8f6f6e'
  outline-variant: '#e4bebc'
  surface-tint: '#bb152c'
  primary: '#b7102a'
  on-primary: '#ffffff'
  primary-container: '#db313f'
  on-primary-container: '#fffbff'
  inverse-primary: '#ffb3b1'
  secondary: '#a03d40'
  on-secondary: '#ffffff'
  secondary-container: '#fd8584'
  on-secondary-container: '#741d22'
  tertiary: '#006860'
  on-tertiary: '#ffffff'
  tertiary-container: '#008379'
  on-tertiary-container: '#f3fffc'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdad8'
  primary-fixed-dim: '#ffb3b1'
  on-primary-fixed: '#410007'
  on-primary-fixed-variant: '#92001c'
  secondary-fixed: '#ffdad8'
  secondary-fixed-dim: '#ffb3b1'
  on-secondary-fixed: '#410007'
  on-secondary-fixed-variant: '#81262a'
  tertiary-fixed: '#8cf4e8'
  tertiary-fixed-dim: '#6fd8cc'
  on-tertiary-fixed: '#00201d'
  on-tertiary-fixed-variant: '#00504a'
  background: '#fff8f7'
  on-background: '#271717'
  surface-variant: '#f9dcda'
typography:
  display:
    fontFamily: Inter
    fontSize: 72px
    fontWeight: '800'
    lineHeight: '1.1'
    letterSpacing: -0.04em
  h1:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  h2:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: '1.3'
    letterSpacing: -0.01em
  h3:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: '0'
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
    letterSpacing: '0'
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
    letterSpacing: '0'
  label:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 8px
  section-padding: 120px
  container-max: 1200px
  gutter: 24px
  margin-mobile: 20px
---

## Brand & Style

This design system centers on a high-impact, minimalist aesthetic designed to position the band as a premium, modern entity. The brand personality is energetic yet controlled, utilizing a stark high-contrast palette to command attention. The emotional response is one of clarity and professionalism, removing unnecessary decorative elements to allow the photography and music to take center stage. 

The style is a blend of **Minimalism** and **High-Contrast Boldness**. It relies on expansive whitespace, a rigid grid, and a singular accent color to create a rhythmic visual experience. This approach ensures that the interface feels official and authoritative while maintaining the kinetic energy expected from a musical artist.

## Colors

The palette is intentionally restricted to create a distinctive visual signature. 

- **Primary Red (#E63946):** Reserved for high-priority actions, branding elements, and structural accents like the card borders. It serves as the "pulse" of the design.
- **Pure White (#FFFFFF):** The primary canvas color, used to maximize legibility and provide a sense of luxury through whitespace.
- **Dark Gray (#1D1D1D):** Used for all typography and iconography to ensure WCAG AA compliance and a grounded, sophisticated feel.
- **Light Gray (#F1F1F1):** Utilized for section alternates and container backgrounds to subtly distinguish content blocks without breaking the minimalist flow.

## Typography

This design system utilizes **Inter** across all levels to achieve a systematic, utilitarian aesthetic. 

- **Headlines:** Set in Bold or Extra Bold weights with tight letter-spacing to create a "wall of text" impact that feels modern and editorial. 
- **Body Text:** Set with generous line height to ensure maximum readability against the white background.
- **Labels:** Uppercase styling is applied to category pills and small identifiers to provide a clear visual contrast from body copy.

## Layout & Spacing

The layout follows a **Fixed Grid** philosophy for desktop environments, centering content within a 1200px container to maintain a clean, organized appearance. 

A 12-column system is used with 24px gutters. Spacing rhythm is based on an 8px scale. Section vertical padding is intentionally large (120px) to give content "room to breathe," reflecting the minimalist brand style. Components like cards and images should align strictly to the grid edges to maintain the architectural integrity of the layout.

## Elevation & Depth

In line with the clean, modern aesthetic, depth is handled with extreme subtlety. This design system avoids heavy drop shadows in favor of:

1.  **Tonal Separation:** Using the Neutral Light Gray (#F1F1F1) for full-width background sections to create depth without shadows.
2.  **Subtle Shadows:** For cards, a multi-layered shadow is used: `0 4px 20px rgba(29, 29, 29, 0.05)`. This creates a soft lift that feels integrated into the page rather than floating above it.
3.  **Flat Accents:** Depth is also signaled through the 4px solid red left-border on cards, providing a physical "edge" to the element without relying on 3D effects.

## Shapes

The shape language of this design system is **Soft (0.25rem)**. 

While the overall vibe is structured and sharp, a small 4px radius is applied to buttons, cards, and input fields. This prevents the UI from feeling overly aggressive or "brutalist," providing a hint of refinement and modern approachability. Category pills are the only exception, utilizing a full pill-shape (999px) to distinguish them as interactive metadata.

## Components

- **Sticky Navbar:** A fixed-position header with a white background and a 1px border-bottom (#F1F1F1). Navigation links use the `label` typography style.
- **Buttons:**
    - *Primary:* Solid Red (#E63946) with White text. Bold weight, no shadow.
    - *Secondary/Outlined:* 2px Red border, Red text, transparent background.
- **Event Cards:** White background, 4px solid Red left-border, and a subtle shadow. Content inside should follow a strict vertical hierarchy (Date, Title, Venue, CTA).
- **Category Pills:** Small, rounded-full elements using the Neutral background (#F1F1F1) with Dark Gray text for a subtle, tag-like appearance.
- **Input Fields:** 1px solid Gray borders that transition to 2px Red on focus.
- **Social Media Icons:** Minimalist glyphs in Dark Gray, scaling to Red on hover. 
- **Audio Player:** A persistent bottom bar or section-specific element using the Dark Gray background with Red playback progress bars.