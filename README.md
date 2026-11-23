# Herd Focal Point Picker

Pick a focal point for each image in the WordPress Media Library, so themes can crop images without cutting off what matters.

A drop-in replacement for [hirasso/focal-point-picker](https://github.com/hirasso/focal-point-picker): it reads and writes the same data and keeps the same theme API, so switching needs no migration and no theme changes.

## Switching from Focal Point Picker

1. Install and activate Herd Focal Point Picker.
2. Deactivate Focal Point Picker. Until you do, this plugin stays paused and shows a notice, so you never get two pickers.

Existing focal points carry over: both plugins store them in the `focalpoint` post meta as `[ 'left' => 0.0–1.0, 'top' => 0.0–1.0 ]`.

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

## Changing the default position

The default is the center. Return a `Herd_FPP_Position` from the `herd_fpp_default_position` filter to change it:

```php
// Top center, which keeps faces in most portraits.
add_filter( 'herd_fpp_default_position', fn() => new Herd_FPP_Position( left: 0.5, top: 0 ) );
```

The original `hirasso/fcp/default-position` filter and `Hirasso\FocalPointPicker\Position` class also still work.

Changing the default affects images without a stored focal point. To apply it to images that already have one:

```shell
wp herd-fpp apply-default-position 23 42 999   # specific images
wp herd-fpp apply-default-position --all       # every image (asks first; --yes to skip)
```

`wp fcp apply-default-position` still works too.

## Compatibility with hirasso/focal-point-picker

Kept as-is: the `focalpoint` meta, `fcp_get_focalpoint()`, the `Hirasso\FocalPointPicker\FocalPoint` and `Position` class names, the `hirasso/fcp/default-position` filter, the `wp fcp apply-default-position` command, the `focal-point-image` class and `--focal-point-*` properties, and the `focalpoint-input` attachment field.

Differences, all fixes:

- An image's existing inline `style` is kept intact; the original appended the custom properties without a `;`, breaking the last existing declaration.
- `isDefaultPosition()` compares left with left and top with top; the original swapped them.
- Stored values are clamped to 0–1.
- No jQuery or jQuery UI in the picker; it also works by keyboard.

## Development

```shell
composer install
composer lint      # PHPCS with WordPress and VIP rules
composer analyse   # PHPStan
```

Requires WordPress 6.5+ and PHP 8.2+. There is no build step: `assets/` ships as written.
