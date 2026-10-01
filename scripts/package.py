#!/usr/bin/env python3
from __future__ import annotations

import argparse
import hashlib
import re
import sys
import zipfile
from pathlib import Path

PLUGIN_SLUG = "ambra-project-management"
MAIN_FILE = "ambra-project-management.php"
RUNTIME_DIRS = ("includes", "assets", "acf-json")
OPTIONAL_ROOT_FILES = ("README.md", "CHANGELOG.md", "LICENSE", "readme.txt")
FORBIDDEN_TOP_LEVEL = {".github", "docs", "tests", "scripts", "dist"}
FIXED_TIMESTAMP = (1980, 1, 1, 0, 0, 0)


def parse_version(source_root: Path) -> str:
    main_path = source_root / MAIN_FILE
    if not main_path.is_file():
        raise SystemExit(f"Missing plugin main file: {MAIN_FILE}")

    text = main_path.read_text(encoding="utf-8")
    match = re.search(r"^\s*\*\s*Version:\s*([^\s]+)\s*$", text, re.MULTILINE)
    if not match:
        raise SystemExit("Could not read Version header from plugin main file.")

    version = match.group(1).strip()
    if not re.fullmatch(r"[0-9]+(?:\.[0-9]+){1,3}(?:[-+][0-9A-Za-z.-]+)?", version):
        raise SystemExit(f"Unexpected plugin version format: {version}")

    runtime = re.search(
        r"define\(\s*['\"]AMBRA_PM_VERSION['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);",
        text,
    )
    if not runtime:
        raise SystemExit("Could not read AMBRA_PM_VERSION from plugin main file.")

    runtime_version = runtime.group(1).strip()
    if runtime_version != version:
        raise SystemExit(
            "Plugin version mismatch: "
            f"header={version}, AMBRA_PM_VERSION={runtime_version}"
        )

    return version


def has_unreleased_changes(source_root: Path) -> bool:
    changelog = source_root / "CHANGELOG.md"
    if not changelog.is_file():
        return False

    text = changelog.read_text(encoding="utf-8")
    match = re.search(
        r"^## Unreleased\s*$([\s\S]*?)(?=^##\s|\Z)",
        text,
        re.MULTILINE,
    )
    if not match:
        return False

    return any(line.strip().startswith("- ") for line in match.group(1).splitlines())


def collect_files(source_root: Path) -> list[Path]:
    files: list[Path] = [source_root / MAIN_FILE]

    for name in OPTIONAL_ROOT_FILES:
        path = source_root / name
        if path.is_file():
            files.append(path)

    for directory in RUNTIME_DIRS:
        root = source_root / directory
        if not root.is_dir():
            raise SystemExit(f"Missing runtime directory: {directory}")

        for path in sorted(root.rglob("*")):
            if path.is_symlink():
                raise SystemExit(f"Symlinks are not allowed in the plugin package: {path}")
            if path.is_file():
                relative = path.relative_to(source_root)
                if any(part.startswith(".") or part == "__pycache__" for part in relative.parts):
                    raise SystemExit(f"Unexpected hidden/development file in runtime tree: {relative}")
                files.append(path)

    unique = sorted(set(files), key=lambda path: path.as_posix())

    if not any(path.relative_to(source_root).parts[0] == "includes" for path in unique):
        raise SystemExit("No runtime PHP include files found.")
    if not any(path.relative_to(source_root).parts[0] == "acf-json" for path in unique):
        raise SystemExit("No ACF JSON files found.")

    return unique


def archive_name(source_root: Path, path: Path) -> str:
    relative = path.relative_to(source_root).as_posix()
    return f"{PLUGIN_SLUG}/{relative}"


def write_deterministic_zip(source_root: Path, output: Path, files: list[Path]) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)

    with zipfile.ZipFile(
        output,
        "w",
        compression=zipfile.ZIP_DEFLATED,
        compresslevel=9,
    ) as archive:
        for path in files:
            info = zipfile.ZipInfo(archive_name(source_root, path), FIXED_TIMESTAMP)
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            info.create_system = 3
            archive.writestr(info, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)


def verify_zip(output: Path, version: str) -> None:
    if not output.is_file():
        raise SystemExit(f"Package was not created: {output}")

    expected_main = f"{PLUGIN_SLUG}/{MAIN_FILE}"

    with zipfile.ZipFile(output, "r") as archive:
        names = archive.namelist()

        if not names:
            raise SystemExit("Package is empty.")
        if len(names) != len(set(names)):
            raise SystemExit("Package contains duplicate paths.")
        if names != sorted(names):
            raise SystemExit("Package entries are not sorted deterministically.")
        if expected_main not in names:
            raise SystemExit(f"Package is missing {expected_main}.")

        for name in names:
            if not name.startswith(f"{PLUGIN_SLUG}/"):
                raise SystemExit(f"Package entry is outside the plugin root: {name}")

            relative = name[len(PLUGIN_SLUG) + 1 :]
            if not relative or relative.startswith("/") or ".." in Path(relative).parts:
                raise SystemExit(f"Unsafe package path: {name}")

            top_level = Path(relative).parts[0]
            if top_level in FORBIDDEN_TOP_LEVEL:
                raise SystemExit(f"Development-only path leaked into package: {name}")

        packaged_main = archive.read(expected_main).decode("utf-8")
        header = re.search(r"^\s*\*\s*Version:\s*([^\s]+)\s*$", packaged_main, re.MULTILINE)
        if not header or header.group(1).strip() != version:
            raise SystemExit("Packaged plugin version does not match the source header.")

        if not any(name.startswith(f"{PLUGIN_SLUG}/includes/") and name.endswith(".php") for name in names):
            raise SystemExit("Package contains no PHP include files.")
        if not any(name.startswith(f"{PLUGIN_SLUG}/acf-json/") and name.endswith(".json") for name in names):
            raise SystemExit("Package contains no ACF JSON field groups.")

        bad = archive.testzip()
        if bad is not None:
            raise SystemExit(f"ZIP CRC validation failed for: {bad}")


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def main() -> int:
    parser = argparse.ArgumentParser(description="Build and verify the WordPress plugin ZIP.")
    parser.add_argument(
        "--output",
        type=Path,
        help="Output ZIP path. Defaults to a versioned dev/release filename under dist/.",
    )
    parser.add_argument(
        "--release",
        action="store_true",
        help="Build an official-version package. Refuses while CHANGELOG.md has Unreleased entries.",
    )
    args = parser.parse_args()

    source_root = Path(__file__).resolve().parent.parent
    version = parse_version(source_root)
    unreleased = has_unreleased_changes(source_root)

    if args.release and unreleased:
        raise SystemExit(
            "Release build blocked: CHANGELOG.md still contains Unreleased changes. "
            "Choose the release version, move those entries under it, and update both "
            "the plugin header and AMBRA_PM_VERSION first."
        )

    default_suffix = "" if args.release or not unreleased else "-dev"
    output = args.output or source_root / "dist" / f"{PLUGIN_SLUG}-{version}{default_suffix}.zip"

    files = collect_files(source_root)
    write_deterministic_zip(source_root, output, files)
    verify_zip(output, version)

    print(f"Built {output}")
    print(f"Version: {version}")
    print(f"Mode: {'release' if args.release else 'development'}")
    print(f"Unreleased changes: {'yes' if unreleased else 'no'}")
    print(f"Files: {len(files)}")
    print(f"SHA256: {sha256(output)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
