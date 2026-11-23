# Herd Focal Point Picker

Pick a focal point for each image in the WordPress Media Library, so themes can crop images without cutting off what matters.

Focal points are stored in the `focalpoint` post meta as `[ 'left' => 0.0–1.0, 'top' => 0.0–1.0 ]`. If another focal point picker plugin using the same field is active, this plugin stays paused and shows a notice until it is deactivated, so you never get two pickers; existing focal points carry over.

## Picking a focal point

Open an image in the Media Library (or its Edit Media screen). Click or drag on the image to move the point; while dragging, a preview shows how wide and tall crops will look. With the handle focused, the arrow keys nudge it by 1% (10% with Shift). **Reset**, or double-clicking the handle, returns it to the default position.

In the media modal the point saves as soon as you let go; on the Edit Media screen it saves with **Update**.

## Using focal points in templates

```php
$focal_point = herd_fpp_get_focal_point( $image_id ); // or fcp_get_focalpoint( $image_id )
```

The returned object has:

| Property | Range | |
| --- | --- | --- |
| `left`, `top` | 0–1 | Also available as `x`, `y` |
| `leftPercent`, `topPercent` | 0–100 | Also available as `xPercent`, `yPercent` |

and `isDefaultPosition()`. Images without a stored focal point get the default position. Passing anything other than an image throws `InvalidArgumentException`.

```php
<img
	src="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'large' ) ); ?>"
	style="object-fit: cover; object-position: <?php echo esc_attr( $focal_point->leftPercent . '% ' . $focal_point->topPercent . '%' ); ?>;"
>
```

### With `wp_get_attachment_image()`

Images rendered by `wp_get_attachment_image()` get a `focal-point-image` class and two custom properties, so CSS alone can crop them:

```html
<img class="attachment-large size-large focal-point-image" style="--focal-point-left: 0.46; --focal-point-top: 0.2;" …>
```

```css
.focal-point-image {
	aspect-ratio: 16 / 7;
	height: auto;
	object-fit: cover;
	object-position:
		calc(var(--focal-point-left, 0.5) * 100%)
		calc(var(--focal-point-top, 0.5) * 100%);
}
```

### Images in post content

Images in post content, such as Image blocks and Classic editor images, are saved as HTML and never pass through `wp_get_attachment_image()`. They get the same two custom properties, but not the `focal-point-image` class, so existing theme CSS aimed at that class doesn't start cropping inline images:

```css
.wp-block-image img {
	object-position:
		calc(var(--focal-point-left, 0.5) * 100%)
		calc(var(--focal-point-top, 0.5) * 100%);
}
```

## Changing the default position

The default is the center. Return a `Herd_FPP_Position` from the `herd_fpp_default_position` filter to change it:

```php
// Top center, which keeps faces in most portraits.
add_filter( 'herd_fpp_default_position', fn() => new Herd_FPP_Position( left: 0.5, top: 0 ) );
```

Changing the default affects images without a stored focal point. To apply it to images that already have one:

```shell
wp herd-fpp apply-default-position 23 42 999   # specific images
wp herd-fpp apply-default-position --all       # every image (asks first; --yes to skip)
```

`--all` works through the library 100 images at a time and clears in-memory caches between batches, so it is safe on large VIP media libraries. `wp fcp apply-default-position` still works too.

## Legacy names

Existing themes and scripts rely on these, so they are kept as-is: the `focalpoint` meta, `fcp_get_focalpoint()`, the `wp fcp apply-default-position` command, the `focal-point-image` class and `--focal-point-*` properties, and the `focalpoint-input` attachment field.

## Development

```shell
composer install
composer lint      # PHPCS with WordPress and VIP rules
composer analyse   # PHPStan
composer test      # PHPUnit against a real WordPress install
```

The tests need a MySQL or MariaDB database; `tests/wp-tests-config.php` reads `WP_TESTS_DB_NAME`, `WP_TESTS_DB_USER`, `WP_TESTS_DB_PASSWORD` and `WP_TESTS_DB_HOST`, defaulting to `wp_tests`, `wp`, `wp` and `127.0.0.1`. GitHub Actions runs all three checks on every push and pull request.

Requires WordPress 6.5+ and PHP 8.2+. There is no build step: `assets/` ships as written.
