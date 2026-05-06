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

1. Build the package with `./tools/build_ocmod.sh` or zip only `install.json`, `README.md`, `admin/`, and `catalog/` so those files/folders are at the archive root. The ZIP file name should be `opencart_filter.ocmod.zip`, because OpenCart uses that name as the extension code and the module routes use `opencart_filter`.
2. In the OpenCart admin panel, open **Extensions → Installer** and upload the ZIP.
3. Open **Extensions → Extensions**, choose **Modules**, install **AJAX Attribute Filter**.
4. Edit the module, enable it, choose attributes to show, and click **Save**.
5. Add the module to a layout, usually **Category**.

## Troubleshooting

If the module is visible in **Extensions → Extensions → Modules** but the edit page opens **Page Not Found**, uninstall the old package from **Extensions → Installer**, delete any old `opencart_filter` install entry if it remains there, rebuild `opencart_filter.ocmod.zip` with `./tools/build_ocmod.sh`, upload it, click **Install** in **Extensions → Installer**, then clear the admin cache from the dashboard gear menu. This ensures OpenCart registers the `opencart_filter` extension namespace before the module edit route is opened.

## Notes

The module is intentionally self-contained and does not overwrite core files. OpenCart 4 installs these root `admin/` and `catalog/` folders into `extension/opencart_filter/` when the package is named `opencart_filter.ocmod.zip`; that is the structure required for the module to appear under **Extensions → Extensions → Modules**. The AJAX script replaces `#product-list` when that container exists in the active theme. If a custom theme uses a different product-list container, adjust `catalog/view/template/module/attribute_filter.twig`.
