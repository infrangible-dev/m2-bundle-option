# Changelog

## 2.0.0

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.1 or newer. Older versions of Magento and PHP 7.1 to 8.0 are no longer
  supported; use version 1.x for these.
- Requires `infrangible/m2-core` 5.0, `infrangible/m2-foundation` 3.0 and `fewedev/base` 2.4.3.
- The constant `CustomOptions::FIELD_BUNDLE_OPTION_IDS_NAME` is replaced by `Helper\Data::BUNDLE_OPTION_IDS`. The
  classes have native types and use constructor property promotion.
- The column `bundle_option_ids` is declared in `etc/db_schema.xml` instead of an install script; existing
  installations keep the column and its data.

### Fixed

- Removing all bundle options of a custom option in the admin and saving the product kept the links: the form posts no
  value for an empty selection.
- Loading a custom option without bundle options on its own (`Option::load()`, as other extensions do) failed with a
  TypeError.

### Added

- Translations into 24 languages.
- Unit tests.
