# OpenCart Filter — AJAX Attribute Filter for OpenCart 4.1

Installable OpenCart 4.1 extension that adds a configurable AJAX product attribute filter with an indexed lookup table.

## Features

- Admin-panel module settings under **Extensions → Extensions → Modules → AJAX Attribute Filter**.
- Automatic index creation on module install and after saving settings.
- Manual **Rebuild index** button in the module settings page.
- Optional event registration for product/category/attribute changes.
- Storefront module renders attribute dropdowns for the current category, or global dropdowns across all products when placed on pages without a category such as the home page.
- AJAX endpoint returns updated product cards and total product count without a full page reload; on pages without an existing `#product-list`, the module renders its own results area under the dropdowns.
- English and Russian language files.

## Installation

1. Build the package with `./tools/build_ocmod.sh` or zip only `install.json`, `README.md`, `admin/` and `catalog/` so those files/folders are at the archive root. The ZIP file name must be exactly `opencart_filter.ocmod.zip`. Do not rename it to `opencart-filter.ocmod.zip`: OpenCart uses the file name as the extension code, and a hyphenated code breaks the generated controller namespace, which causes "Page Not Found" on edit.
2. In the OpenCart admin panel, open **Extensions → Installer** and upload the ZIP.
3. Open **Extensions → Extensions**, choose **Modules**, install **AJAX Attribute Filter**.
4. Edit the module, enable it, choose attributes to show, and click **Save**. Saving rebuilds the index, including global rows used on the home page.
5. Add the module to **Category** and/or **Home** layout. On **Home** it filters across all indexed products.

## Troubleshooting

If the module is visible in **Extensions → Extensions → Modules** but the edit page opens **Page Not Found**, the package was usually uploaded under the wrong code/name. In **Extensions → Installer**, uninstall and delete old entries such as `opencart-filter`, `AJAXaf`, or duplicate filter installs; remove the old module instance from layouts if it is still assigned; rebuild `opencart_filter.ocmod.zip` with `./tools/build_ocmod.sh`; upload that exact file name; click **Install** in **Extensions → Installer**; then clear the admin cache. The installed extension code must be `opencart_filter`.

## Notes

The module is self-contained and does not overwrite core files. Module instance settings are stored by OpenCart in `oc_module` (with code `opencart_filter.attribute_filter`), not in `oc_setting`. OpenCart 4 installs these root `admin/` and `catalog/` folders into `extension/opencart_filter/` when the package is named `opencart_filter.ocmod.zip`; that is the structure required for the module to appear under **Extensions → Extensions → Modules**. The AJAX script replaces `#product-list` when that container exists in the active theme. If the page has no `#product-list` (for example, the home page), the module uses its own result container below the dropdowns. If a custom theme uses a different product-list container, adjust `catalog/view/template/module/attribute_filter.twig`.
