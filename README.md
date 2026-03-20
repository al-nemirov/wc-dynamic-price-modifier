# WC Dynamic Price Modifier

![WordPress 5.0+](https://img.shields.io/badge/WordPress-5.0%2B-21759b?logo=wordpress&logoColor=white)
![WooCommerce 3.0+](https://img.shields.io/badge/WooCommerce-3.0%2B-96588a?logo=woocommerce&logoColor=white)
![PHP 5.4+](https://img.shields.io/badge/PHP-5.4%2B-777bb4?logo=php&logoColor=white)
![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)
![Version](https://img.shields.io/badge/Version-1.3-blue.svg)

[English](#english) | [Русский](#русский)

---

## English

Dynamically modify displayed WooCommerce prices without changing the database.

### Use Case

Apply discounts or markups across the entire catalog instantly. Prices change only on the storefront and at checkout -- the database keeps original values. Disable the plugin -- prices revert immediately.

### Features

- Percentage discount or markup on all products
- Exclude specific products from modification
- Support for simple and variable products
- Configurable rounding (none, whole number, 10, 50, 100)
- Dual-price display in admin (database price vs. displayed price)
- Preview on 15 products directly in settings page
- Auto cache clearing on settings update
- Database remains untouched

### Requirements

| Requirement   | Version |
|---------------|---------|
| WordPress     | 5.0+    |
| WooCommerce   | 3.0+    |
| PHP           | 5.4+    |

### Installation

#### Manual Upload

1. Download the latest release as a `.zip` file.
2. In WordPress admin go to **Plugins > Add New > Upload Plugin**.
3. Upload the `.zip` file and click **Install Now**.
4. Click **Activate Plugin**.

#### Via FTP / File Manager

1. Extract the archive to get the `wc-dynamic-price-modifier` folder.
2. Upload this folder to `/wp-content/plugins/` on your server.
3. In WordPress admin go to **Plugins** and activate **WC Dynamic Price Modifier**.

#### From GitHub

```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/your-username/wc-dynamic-price-modifier.git
```

Then activate the plugin in WordPress admin.

### Configuration

After activation, go to **WooCommerce > Price Modifier** in the admin panel.

| Setting              | Description                                                              |
|----------------------|--------------------------------------------------------------------------|
| **Enable Modifier**  | Toggle the price modification on or off                                  |
| **Percentage**       | The percentage value for discount or markup (0.1 -- 99)                  |
| **Action**           | Choose between **Decrease** (discount) or **Increase** (markup)          |
| **Rounding**         | Round the final price: no rounding, whole number, nearest 10, 50, or 100 |
| **Exclude Products** | Comma-separated product IDs to exclude from modification                 |

The settings page also includes a **Preview** table showing 15 published products with their database price, display price, difference, and exclusion status.

#### Admin Features

- **Product edit screen**: A blue info box below pricing fields shows the database price and the modified display price.
- **Products list**: An extra "Display Price" column shows the actual storefront price and discount/markup percentage.
- **Excluded products** are highlighted in yellow with an "EXCLUDED" badge.

### Filter Hooks for Developers

The plugin uses standard WooCommerce filter hooks. You can interact with the modified prices by adjusting priority:

```php
// Run your filter AFTER the price modifier (priority > 99)
add_filter('woocommerce_product_get_price', 'my_custom_price_logic', 100, 2);
function my_custom_price_logic($price, $product) {
    // $price is already modified by WC Dynamic Price Modifier
    return $price;
}
```

#### Hooks Used by the Plugin

| Hook                                              | Priority | Description                          |
|---------------------------------------------------|----------|--------------------------------------|
| `woocommerce_product_get_price`                   | 99       | Modify simple product price          |
| `woocommerce_product_get_regular_price`           | 99       | Modify simple product regular price  |
| `woocommerce_product_get_sale_price`              | 99       | Modify simple product sale price     |
| `woocommerce_product_variation_get_price`         | 99       | Modify variation price               |
| `woocommerce_product_variation_get_regular_price` | 99       | Modify variation regular price       |
| `woocommerce_product_variation_get_sale_price`    | 99       | Modify variation sale price          |
| `woocommerce_variation_prices_price`              | 99       | Modify variation range price         |
| `woocommerce_variation_prices_regular_price`      | 99       | Modify variation range regular price |
| `woocommerce_variation_prices_sale_price`         | 99       | Modify variation range sale price    |
| `woocommerce_get_variation_prices_hash`           | 99       | Add modifier data to variation cache |

#### Options (wp_options)

| Option Key                  | Type   | Default    | Description                    |
|-----------------------------|--------|------------|--------------------------------|
| `wc_dpm_enabled`            | string | `'no'`     | Enable/disable (`'yes'`/`'no'`) |
| `wc_dpm_discount_percent`   | float  | `20`       | Percentage value               |
| `wc_dpm_action_type`        | string | `'decrease'` | `'decrease'` or `'increase'` |
| `wc_dpm_round_to`           | int    | `0`        | Rounding step (0, 1, 10, 50, 100) |
| `wc_dpm_excluded_products`  | string | `''`       | Comma-separated product IDs    |

### Uninstallation

Deactivate and delete the plugin via **Plugins** page. The plugin does not modify product prices in the database, so no cleanup is needed. Options stored in `wp_options` will remain; to remove them manually, delete rows with the `wc_dpm_` prefix.

---

## Русский

Динамическое изменение отображаемых цен WooCommerce без изменения базы данных.

### Для чего

Быстрое применение скидок или наценок ко всему каталогу. Цены меняются только на витрине и при оформлении заказа -- в базе остаются оригиналы. Отключил плагин -- цены вернулись.

### Возможности

- Скидка или наценка в процентах на все товары
- Исключение выбранных товаров из модификации
- Поддержка простых и вариативных товаров
- Настраиваемое округление (без, до целого, до 10, 50, 100)
- Двойная цена в админке (цена из базы + цена на витрине)
- Превью на 15 товарах прямо на странице настроек
- Автоочистка кэша при сохранении настроек
- База данных не затрагивается

### Требования

| Требование   | Версия |
|--------------|--------|
| WordPress    | 5.0+   |
| WooCommerce  | 3.0+   |
| PHP          | 5.4+   |

### Установка

#### Загрузка через админку

1. Скачайте последний релиз в виде `.zip` файла.
2. В админке WordPress: **Плагины > Добавить новый > Загрузить плагин**.
3. Загрузите `.zip` файл и нажмите **Установить**.
4. Нажмите **Активировать плагин**.

#### Через FTP / файловый менеджер

1. Распакуйте архив -- получите папку `wc-dynamic-price-modifier`.
2. Загрузите эту папку в `/wp-content/plugins/` на сервере.
3. В админке WordPress: **Плагины** -- активируйте **WC Dynamic Price Modifier**.

### Настройка

После активации: **WooCommerce > Price Modifier** в панели управления.

| Параметр             | Описание                                                                    |
|----------------------|-----------------------------------------------------------------------------|
| **Enable Modifier**  | Включить или выключить модификацию цен                                       |
| **Percentage**       | Процент скидки или наценки (0.1 -- 99)                                      |
| **Action**           | Выбор: **Decrease** (скидка) или **Increase** (наценка)                      |
| **Rounding**         | Округление итоговой цены: без, до целого, до ближайших 10, 50 или 100        |
| **Exclude Products** | ID товаров через запятую, которые не будут модифицированы                     |

На странице настроек также есть **таблица превью** -- 15 опубликованных товаров с ценой из базы, ценой на витрине, разницей и статусом исключения.

#### Возможности в админке

- **Страница редактирования товара**: информационный блок под полями цен показывает цену из базы и модифицированную цену.
- **Список товаров**: дополнительная колонка "Display Price" показывает реальную цену на витрине и процент скидки/наценки.
- **Исключённые товары** выделены жёлтым цветом с меткой "EXCLUDED".

### Хуки-фильтры для разработчиков

Плагин использует стандартные фильтры WooCommerce. Вы можете работать с модифицированными ценами, задавая приоритет выше 99:

```php
// Ваш фильтр ПОСЛЕ модификатора цен (приоритет > 99)
add_filter('woocommerce_product_get_price', 'my_custom_price_logic', 100, 2);
function my_custom_price_logic($price, $product) {
    // $price уже модифицирован плагином WC Dynamic Price Modifier
    return $price;
}
```

### Удаление

Деактивируйте и удалите плагин через страницу **Плагины**. Плагин не изменяет цены товаров в базе данных, поэтому дополнительная очистка не требуется. Настройки в `wp_options` останутся; для их удаления вручную удалите строки с префиксом `wc_dpm_`.

---

## Contributing / Участие в разработке

Contributions are welcome! / Мы рады вашему участию!

1. Fork the repository / Сделайте форк репозитория
2. Create a feature branch / Создайте ветку для новой функции:
   ```bash
   git checkout -b feature/my-new-feature
   ```
3. Make your changes / Внесите изменения
4. Test with the latest WordPress and WooCommerce / Проверьте с последними версиями WordPress и WooCommerce
5. Submit a Pull Request / Отправьте Pull Request

### Code Style

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Add PHPDoc comments to all functions and methods
- Test with `WP_DEBUG` enabled

---

## Author / Автор

**Alexander Nemirov**

## License / Лицензия

This project is licensed under the [MIT License](LICENSE).
