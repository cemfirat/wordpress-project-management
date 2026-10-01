# Release discipline

This repository distinguishes **development packages** from **official-version packages**.

## Main branch

Normal development may continue with the current plugin version while changes are collected under `## Unreleased` in `CHANGELOG.md`.

A package built from that state is a development artifact and uses a `-dev` filename by default.

The same semantic version must not be intentionally published as multiple different official artifacts.

## Version invariant

Two version declarations currently exist in `ambra-project-management.php`:

- plugin header `Version:`
- runtime constant `AMBRA_PM_VERSION`

They must always be identical. `scripts/package.py` enforces this and CI therefore detects drift.

## Preparing an official version

Before an official package is built:

1. decide the next version number;
2. move all applicable `## Unreleased` entries under the new version heading;
3. update both `Version:` and `AMBRA_PM_VERSION` to the same value;
4. run the full feature-branch CI and obtain a fully green result;
5. only then open the pull request;
6. require the PR CI to be fully green;
7. merge and require the final `main` CI to be green;
8. build the exact release commit with:
   `python3 scripts/package.py --release`;
9. record the resulting SHA-256 when the artifact is distributed.

## Distribution boundary

An official-version ZIP is not automatically a public release.

Until issue #4 resolves licensing and distribution metadata:

- do not publish a GitHub Release as the official update channel;
- do not add an automatic WordPress updater;
- do not invent an `Update URI`;
- do not infer a software license.

The package builder validates artifacts; it does not make product/legal distribution decisions.
