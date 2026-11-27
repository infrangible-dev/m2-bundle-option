# Infrangible Bundle Option

Links custom options of bundle products to bundle options: the value that the shopper enters or selects is passed on
to the product selected in the linked bundle option, for example the engraving of the strap of a yoga kit.

## How it works

- Custom options of saved bundle products with bundle options get the field "Bundle Options" in the admin (Catalog >
  Products > Customizable Options): a multiple select of the bundle options of the product. A custom option can be
  linked to several bundle options; without selection it stays an option of the bundle only.
- When the bundle is added to the cart, the cart items of the products selected in the linked bundle options get the
  custom option with its value, like a custom option of their own.
- In the order, the order items of these products carry the custom option too. The order API (product option of the
  order items) shows it on the order item of the product instead of the bundle item. The order view of the admin
  lists all custom options with the bundle, as Magento shows bundle items.
- The links are saved in the column `bundle_option_ids` of `catalog_product_option`.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.1 or newer

## Installation

```bash
composer require infrangible/m2-bundle-option
bin/magento module:enable Infrangible_BundleOption
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile`.

## License

Infrangible Bundle Option is licensed under the MIT License - see the LICENSE file for details.
