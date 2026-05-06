# OpenCart Filter — AJAX Attribute Filter for OpenCart 4.1

Installable OpenCart 4.1 extension that adds a configurable AJAX product attribute filter with an indexed lookup table.

## Features

- Admin-panel module settings under **Extensions → Extensions → Modules → AJAX Attribute Filter**.
- Automatic index creation on module install and after saving settings.
- Manual **Rebuild index** button in the module settings page.
- Optional event registration for product/category/attribute changes.
- Storefront module renders attribute checkboxes for the current category.
- AJAX endpoint returns updated product cards and total product count without a full page reload.
- English and Russian language files.

## Installation

1. Zip the repository contents so `install.json` and `upload/` are at the archive root.
2. In the OpenCart admin panel, open **Extensions → Installer** and upload the ZIP.
3. Open **Extensions → Extensions**, choose **Modules**, install **AJAX Attribute Filter**.
4. Edit the module, enable it, choose attributes to show, and click **Save**.
5. Add the module to a layout, usually **Category**.

## Notes

The module is intentionally self-contained and does not overwrite core files. The AJAX script replaces `#product-list` when that container exists in the active theme. If a custom theme uses a different product-list container, adjust `catalog/view/template/module/attribute_filter.twig`.
