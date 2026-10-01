# Generalization strategy

## Purpose

This repository originated as the AMBRA project-management application. The repository history explicitly records the intention to evolve it into a universal plugin.

That evolution must preserve existing AMBRA installations.

## Compatibility rule

Existing persisted identifiers are compatibility surfaces, not branding to be cleaned up casually.

The default rule is therefore:

- keep current `ambra_*` custom post type names, options, post meta, ACF field names/keys and capabilities unless a tested migration has a concrete benefit;
- keep existing shortcodes and REST/capability identifiers working when neutral aliases are introduced;
- preserve stored page IDs and existing YOOtheme content;
- do not rename the main plugin file or text domain until the long-term plugin identity and upgrade implications are explicitly decided;
- separate user-facing branding from storage identity.

## Verified compatibility surfaces

### Data model

Current private CPT identifiers:

- `ambra_customer`
- `ambra_project`
- `ambra_visit`
- `ambra_position`
- `ambra_blueprint`
- `ambra_manufacturer`
- `ambra_supplier`
- `ambra_team`

### Persistent options/meta

Known persistent identifiers include:

- `ambra_pm_pages`
- `ambra_pm_version`
- `ambra_pm_pending_setup`
- `_ambra_pm_page`

### Permissions

The role slug `project_team_member` is already neutral. Existing capabilities include AMBRA-prefixed operational, catalog, team and application permissions and therefore require backward compatibility.

### Public/plugin-facing API

Compatibility also includes:

- `ambra-project-management.php`
- text domain `ambra-project-management`
- namespace/constants under `AMBRA_PM`
- `ambra_*` shortcodes
- `ambra_*` admin-post actions
- current REST bases

### Field schema

ACF exports and runtime code contain persisted `ambra_*` field names and `field_ambra_*` keys. These are business-data identifiers and must not be mass-renamed.

## Recommended migration sequence

1. Decide the neutral product/plugin identity.
2. Keep legacy storage identifiers unchanged by default.
3. Move AMBRA-specific labels/defaults/demo data behind configuration where practical.
4. Add neutral user-facing wording.
5. Add neutral aliases only where a public developer-facing API benefits from them.
6. Add compatibility tests before any identifier migration.
7. Only then evaluate main-file/text-domain/plugin-slug changes as a major-version concern.

## Governance

- License/distribution decision: issue #4.
- Generalization roadmap: issue #5.
- Branch CI must be green before any pull request is opened.
- Shared WordPress blueprint enrollment remains paused until license/identity metadata are explicit.


## Product identity boundary

User-facing product naming is separated from persisted storage identity.

`Identity::info()` keeps the current AMBRA labels as defaults and exposes the `ambra_pm_product_identity` filter for a future neutral product name. Changing those labels does not rename custom post types, options, ACF keys, capabilities, shortcodes or other persisted compatibility identifiers.

The plugin header, main filename and text domain remain compatibility decisions for a later major-version boundary.
