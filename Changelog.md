# Changelog for v0.1.x

This changelog details the bug fixes included in this release.

## **v0.1.1 (18nd September 2024)** - *Release*
### Changed
- Updated the test cases.
- French translation updated.

### Added
- Added MariaDB compatibility (thanks to @helgvor-stoll).
- Added Docker support (thanks to @jdecode).

## **v0.1.1 (22nd August 2024)** - *Release*

### Bug Fixes

* Fixed date format validation issues in both API and import processes.
* Added validation to ensure unique values during imports, even for empty fields.
* Resolved an issue where error reports were generated in an incorrect file format.
* Fixed an issue where the history tab failed to load for users without necessary permissions.
* Added validation to prevent non-existent options in select, multiselect, and checkbox fields during imports.
* Restricted import and export field separators to ',', ';', or '|'.
* Added a warning message for incorrect separator usage in import files.
* Fixed a bug where the category delete action did not function correctly during imports.
* Added a warning when the export folder lacks read/write permissions.
* Added a navigation button from the job tracking page to the job edit page.
* Fixed random filenames being generated for export files in export jobs.
* Corrected the export of channel-specific attribute values in product export files.
* Hid the field separator option for XLS and XLSX exports.
* Fixed an issue where the product count was not displaying correctly in category exports.
* Specified allowed file formats for category and product imports.
* Fixed a bug that allowed the same product to be added multiple times in the association section via the UI.
* Fixed boolean value history not displaying in category and product history sections.
* Ensured that at least one product image is visible when searching in the association section or viewing variants.
* Fixed a bug where the product count displayed as zero in the category datagrid.
* Corrected an issue where channel filtering by root category label showed no records in the channel datagrid.
* Changed status code to 200 for successful responses in attribute group, simple, and configurable product APIs.
* Fixed an issue where product prices were saved incorrectly when multiple currencies were added and the attribute was not channel-specific.
* Resolved an issue that prevented the generation of the auth token via API without first executing the "passport:keys" command.
* Fixed an issue that prevented multiple filters from being applied simultaneously in any datagrid.
* Fixed ACL permissions that allowed access to the create page of attribute groups and attributes without proper permissions.
* Added missing assigned and unassigned history generation in attribute families.
* Added missing history generation for import and export jobs.
* Fixed the search functionality in datagrids for import and export.
* Corrected an issue where the type column code was displayed instead of the label in import and export datagrids.
* Added a missing translation for the upload icon in the export datagrid.
* Fixed an issue where the variant product create API did not work when the common section lacked the variant attribute.
* Resolved a potential XSS attack vulnerability through imports and API for WYSIWYG text area fields.
* Restricted category fields from being created with the codes 'locale', 'parent', or 'code'.
* Fixed the validation message in the file upload section of import jobs.
* Fixed an issue where category fields did not display in sort order according to position value in the exported category file.
* Fixed media file path functionality not working in category and product imports.
* Fixed the "Download Sample" link being displayed below the import type field on the import edit page.
* Fixed an issue where Magic AI configuration credentials were not being saved.
* Fixed an issue where role history content was not fully visible on small screen sizes.
* Restricted the deletion of the logged-in user.
