# Releasing

Maintainer notes for publishing `praxicraft/assess` to Packagist.

## How publish works

[`.github/workflows/publish.yml`](.github/workflows/publish.yml) runs on pushes to **`main`** when:

- `src/**`
- `composer.json`
- `CHANGELOG.md`
- `.github/workflows/publish.yml`

Flow:

1. Run tests on PHP 8.1 / 8.2 / 8.3.
2. Read version from `src/Version.php` (`Version::STRING`).
3. If git tag `v{version}` already exists → skip.
4. Otherwise create + push `v{version}`.
5. Optionally call the Packagist update API when `PACKAGIST_TOKEN` + `PACKAGIST_USERNAME` are set.

Packagist primarily syncs from the GitHub repo / tags once the package is submitted.

## Cut a release

1. Bump `Version::STRING` in `src/Version.php`.
2. Update `CHANGELOG.md`.
3. Merge to `main`.

## One-time Packagist setup

1. Submit `https://github.com/praxicraft-platform/praxicraft-php` on [Packagist](https://packagist.org).
2. Enable the GitHub Service / webhook (or Auto-Update).
3. Create GitHub Environment **`packagist`** on this repo.
4. Optional: set repository secrets `PACKAGIST_USERNAME` and `PACKAGIST_TOKEN` for the notify step.

## GitHub Release

The Publish workflow also creates a **GitHub Release** for tag `v{version}` (with generated notes and package assets where applicable).

You can run **Actions → Publish → Run workflow** manually (`workflow_dispatch`) after bumping the version on `main`.
