# v1.0.x

## v1.0.0

### Features
- Added **PostgreSQL Support** for improved cross-database compatibility. Fixes issue: [#45](https://github.com/unopim/unopim/issues/45)
- Implemented **System Prompt Management** for configuring AI prompt behavior and max token settings.
- Added **Custom Prompts** for Magic AI content generation and **Product Values Translation** to translate an attribute value in multiple other languages with Magic AI.  
- Introduced **Product Completeness**, providing completeness score and evaluation of product data quality based on completeness settings.
- Added **Product Bulk Edit** with advanced multi-product editing capabilities for faster workflows.
- Added **Product Update Webhook**, enabling external systems to react to real-time product updates.
- Added **Video Support** in the gallery attribute. Fixes issue: [#84](https://github.com/unopim/unopim/issues/84)
* Completeness calculation jobs use the system queue. Start queue worker using: `php artisan queue:work --queue=system,default`

### Improvements
- Optimized **Category Tree Rendering**, significantly reducing load times for large catalogs. Fixes issue: [#176](https://github.com/unopim/unopim/issues/176)
- Updated **Import Job Configuration**:
  - Timeout set to `0` for long-running jobs.
  - Batch size increased to `100`.
  - Stats calculation for job progress uses query instead of eager loading all batches for counting completed state.

### Bug Fixes

- Corrected handling of the `@lang` directive to ensure consistent and secure output rendering on the import job page.  

### Dependency Updates
- Bump enshrined/svg-sanitize from `0.16.0` to `0.22.0`
- Bump phpoffice/phpspreadsheet from `1.29.9` to `1.30.0`

# v0.3.x

## v0.3.2 - 2025-08-26

### Fixes

* Fixed failure of product export when 'with media' filter is enabled
* Fixed category export job failing due to uninitialized file buffer

## v0.3.1 - 2025-08-22

### Fixes

* Fix: escape CSV formula operators in export files [CVE-2025-55745]
* Fix: added ACL permissions for mass action routes [CVE-2025-55745]

### Chores

* Chore: update security policy for supported version

## v0.3.0 - 2025-07-28

### Features

* Dynamic management of quick product export jobs
* Product datagrid now supports dynamic columns and filters
* Improved Magic AI functionality
* Enhanced product information section UI/UX
* Improved Elasticsearch filters for products and categories
* Improved export functionality using Elasticsearch
* Introduced upgrade guide and automated upgrade script
* Added Playwright tests for end-to-end testing

### Fixes & Enhancements

* Altered `text` column to `string` (`varchar`) and added relevant indexes
* Optimized category datagrid to sort by `id` instead of `_id`
* Improved performance for pages loading bulk data
* Improved file validation rules
* Enhanced product index functionality in Elasticsearch
* Resolved issue with invalid channel-locale change [#122](https://github.com/unopim/unopim/pull/122)
* Fixed unique attribute value duplication when creating variants
* Fixed issue preventing value entry in the Price section when multiple currencies are enabled
* Fixed inconsistent WYSIWYG field behavior during data transfer and API usage
* Updated validation rule to disallow only special characters, spaces, and dashes in the `Code` field
* Fixed error when downloading sample CSV files with non-public default storage
* Updated `X-Frame-Options` header to `SAMEORIGIN` in SecureHeaders middleware [#180](https://github.com/unopim/unopim/pull/180)

### Dependency Updates

* Upgraded `laravel/framework` from `10.48.23` to `10.48.29`
* Updated Finnish translations [#161](https://github.com/unopim/unopim/pull/161)
* Optimized attribute options grid performance for large datasets
 
# v0.2.x
 
## v0.2.0 - 2025-03-26
### ✨ **Features**  
- Added disk parameter to `sanitizeSVG`. [#58](https://github.com/unopim/unopim/pull/58)  
- Introduced dynamic import job filters. [#80](https://github.com/unopim/unopim/pull/80)  
- Added in-app and email notifications. [#78](https://github.com/unopim/unopim/pull/78)  
- New API endpoints for patching and deleting products/categories. [#98](https://github.com/unopim/unopim/pull/98)  
- Implemented GUI installer for easier setup. [#55](https://github.com/unopim/unopim/pull/55)  
- Added Magic Image feature. [#100](https://github.com/unopim/unopim/pull/100)  
- "Powered by" message added to authentication screens. [#110](https://github.com/unopim/unopim/pull/110)  

### 🛠 **Fixes & Enhancements**  
- Fixed gallery image removal issue. [#90](https://github.com/unopim/unopim/pull/90)  
- Enabled product status by default. [#89](https://github.com/unopim/unopim/pull/89)  
- Quick export fix for selected products. [#116](https://github.com/unopim/unopim/pull/116)  
- Fixed JSON encoding issues with special characters. [#104](https://github.com/unopim/unopim/pull/104)  
- Prevented HTML entities from showing in flash messages. [#114](https://github.com/unopim/unopim/pull/114)  
- Improved cumulative filter conditions. [#108](https://github.com/unopim/unopim/pull/108)  
- Fixed TypeError with filterable dropdown column. [#106](https://github.com/unopim/unopim/pull/106)  
- Improved CSS styling for GUI installer and image previews. [#73](https://github.com/unopim/unopim/pull/73)  

### 🔄 **Dependency Updates**  
- Upgraded `phpoffice/phpspreadsheet` to `1.29.9`. [#101](https://github.com/unopim/unopim/pull/101)  
- Upgraded `league/commonmark` to `2.6.0`. [#74](https://github.com/unopim/unopim/pull/74)  
- Upgraded `nesbot/carbon` to `2.72.6`. [#93](https://github.com/unopim/unopim/pull/93)  


# v0.1.x

## v0.1.5 - 2024-10-25

### Enhancements
- **New Command**: Introduced the `user:create` command for streamlined user management ([#35](https://github.com/unopim/unopim/pull/35)).

### Bug Fixes
- **Database Compatibility**: Fixed an issue with import job creation due to the `longtext` column type in MariaDB, improving database compatibility and import stability ([#43](https://github.com/unopim/unopim/pull/43)).
- **Data Consistency**: Addressed an issue with merging old and new values during import to ensure accurate data synchronization ([#44](https://github.com/unopim/unopim/pull/44)).

**Full Changelog**: [v0.1.4...v0.1.5](https://github.com/unopim/unopim/compare/v0.1.4...v0.1.5)

## **v0.1.4 (17 October 2024)** - *Release*
* Security Issue #41: fixed Stored XSS 

## **v0.1.3 (14 October 2024)** - *Release*

### Bug Fixes
* Issue #21: fix db:seed command throwing error when running after installation

### Changed
* Bump phpoffice/phpspreadsheet from 1.29.1 to 1.29.2
* Docker images for installation through docker

### Added
* #23: Gallery type attribute
* Executing data transfer jobs via terminal through 'php artisan unopim:queue:work {JobId} {userEmailId}'
* Job specific log files for data transfer jobs
* Datagrid Improvement: first and last page buttons (thanks to @helgvor-stoll)
* #26 Account page Improvement: UI locale and timezone field added

## **v0.1.2 (18nd September 2024)** - *Release*

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
