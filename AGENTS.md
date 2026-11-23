# Repository Guidelines

Herd Focal Point Picker is a WordPress plugin that stores a focal point per image. Everything listed under "Legacy names" in README.md is public API existing themes rely on: the `focalpoint` meta shape, `fcp_get_focalpoint()`, the `fcp` WP-CLI command, and the `focal-point-image` class and `--focal-point-*` properties. Do not rename or reshape them.

## Layout

- `herd-fpp.php` boots the plugin on `plugins_loaded`, and stays paused while another focal point picker using the same field is active.
- `includes/` holds the PHP: the `Herd_FPP_Position` and `Herd_FPP_Focal_Point` value classes, public functions, the compatibility names, the attachment field, the image and content image filters, and WP-CLI.
- `assets/` holds the admin script and styles. They are plain files with no build step and no dependencies; keep it that way.

## Checks

- `composer lint` runs PHPCS with WordPress and VIP rules.
- `composer analyse` runs PHPStan.
- `composer test` runs the PHPUnit suite in `tests/` against a real WordPress install (see README for the database). Tests assert the behavior existing themes rely on; keep them passing unchanged.

UI changes need checking in the media modal (Media Library and the select-image sidebar) and on the Edit Media screen, because each renders the image preview differently.

## Style

PHP follows WordPress conventions: tabs, `snake_case` functions, the `herd_fpp_` / `HERD_FPP_` / `Herd_FPP_` prefixes, and the `herd-fpp` text domain. JavaScript uses tabs, single quotes, and semicolons. Use concise, imperative commit subjects.
