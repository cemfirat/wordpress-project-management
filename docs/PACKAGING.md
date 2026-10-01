# Plugin packaging

The repository contains a reproducible local package builder:

```bash
python3 scripts/package.py
```

By default a repository state with entries under `## Unreleased` creates:

```text
dist/ambra-project-management-<version>-dev.zip
```

An official-version package is requested explicitly:

```bash
python3 scripts/package.py --release
```

A release build is refused while `CHANGELOG.md` still contains `Unreleased` entries.

The version is read from the `Version:` header in `ambra-project-management.php` and must exactly match the runtime constant `AMBRA_PM_VERSION`. Any mismatch fails the build.

## Package whitelist

The ZIP contains one WordPress plugin root folder, `ambra-project-management/`, and only:

- `ambra-project-management.php`
- `includes/`
- `assets/`
- `acf-json/`
- optional root metadata files when present: `README.md`, `CHANGELOG.md`, `LICENSE`, `readme.txt`

Development-only folders such as `.github/`, `docs/`, `tests/`, `scripts/` and `dist/` are never packaged.

## Verification

The builder fails when:

- the main plugin file or required runtime directories are missing;
- the plugin header version cannot be parsed;
- the plugin header and `AMBRA_PM_VERSION` disagree;
- `--release` is requested while the changelog still contains Unreleased entries;
- hidden/development files appear inside a runtime directory;
- a symlink appears in the package input;
- the archive contains duplicate, unsafe or out-of-root paths;
- development-only top-level paths leak into the ZIP;
- the packaged version differs from the source header;
- runtime PHP includes or ACF JSON groups are absent;
- ZIP CRC validation fails.

CI builds the development package twice and compares the resulting bytes. Fixed ZIP timestamps, sorted paths and fixed file modes make the artifact deterministic for identical repository contents.

CI also verifies that `--release` is blocked while the repository contains Unreleased changelog entries.

## Distribution boundary

This packaging command creates a local installable ZIP only. It does not publish a GitHub Release, configure a WordPress updater, or decide the software license. Those decisions remain tracked separately in issue #4.
