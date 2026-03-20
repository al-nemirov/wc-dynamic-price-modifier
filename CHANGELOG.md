# Changelog / История изменений

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [1.3] - 2025-03-19

### Added / Добавлено
- Dual-price display on product edit screen (database price vs. display price)
- Dual-price display for variations on the variation edit panel
- "Display Price" column in the admin products list with discount/markup percentage
- Excluded products highlighted in yellow with "EXCLUDED" badge in admin
- Admin CSS styles for price info boxes and badges

### Changed / Изменено
- Improved admin UI with visual indicators for active modifier and exclusions

---

## [1.2] - 2025-02-15

### Added / Добавлено
- Product exclusion feature: exclude specific products by ID from price modification
- Support for excluding variations via parent product ID
- Exclusion textarea in settings with flexible input format (commas, spaces, newlines)
- Preview table on settings page showing 15 products with prices and status

### Changed / Изменено
- Settings page redesigned with status notices (active/disabled)
- Cache clearing now also removes variation price transients

---

## [1.1] - 2025-01-20

### Added / Добавлено
- Configurable rounding options (no rounding, whole number, nearest 10, 50, 100)
- Support for variable products and variation prices
- Variation price hash modifier to ensure proper cache invalidation
- Auto cache clearing on settings save (product transients and variation prices)

### Changed / Изменено
- Price modification now applies to regular, sale, and variation prices
- Improved input sanitization with min/max constraints for percentage field

---

## [1.0] - 2024-12-10

### Added / Добавлено
- Initial release
- Percentage-based discount or markup for all WooCommerce products
- Admin settings page under WooCommerce menu
- Enable/disable toggle
- Action type selection (decrease/increase)
- WooCommerce dependency check on activation
- Frontend-only price modification (database prices unchanged)
- Admin notice when WooCommerce is not active
