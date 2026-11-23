# Repository Guidelines

Herd Focal Point Picker is a WordPress plugin that stores a focal point per image. It is a drop-in replacement for hirasso/focal-point-picker, so everything listed under "Compatibility" in README.md is public API: the `focalpoint` meta shape, `fcp_get_focalpoint()`, the `Hirasso\FocalPointPicker\*` aliases, the `hirasso/fcp/default-position` filter, the `fcp` WP-CLI command, and the `focal-point-image` class and `--focal-point-*` properties. Do not rename or reshape them.

## Layout

- `herd-fpp.php` boots the plugin on `plugins_loaded`, and stays paused while the original plugin is active.
- `includes/` holds the PHP: the `Herd_FPP_Position` and `Herd_FPP_Focal_Point` value classes, public functions, the compatibility names, the attachment field, the image attributes filter, and WP-CLI.
- `assets/` holds the admin script and styles. They are plain files with no build step and no dependencies; keep it that way.

## Checks

- `composer lint` runs PHPCS with WordPress and VIP rules.
- `composer analyse` runs PHPStan.

UI changes need checking in the media modal (Media Library and the select-image sidebar) and on the Edit Media screen, because each renders the image preview differently.

## Style

PHP follows WordPress conventions: tabs, `snake_case` functions, the `herd_fpp_` / `HERD_FPP_` / `Herd_FPP_` prefixes, and the `herd-fpp` text domain. JavaScript uses tabs, single quotes, and semicolons. Use concise, imperative commit subjects.
