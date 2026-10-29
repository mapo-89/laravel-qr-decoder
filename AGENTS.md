# Package development

## Package-specific checks

Run relevant PHP and Laravel integration tests with `composer test`.
The release workflow additionally requires successful `run-tests` push CI for the
exact release commit. Composer derives the package version from Git tags; do not
add a version field to `composer.json`.

Heavy dependency resolution must follow the inherited memory limits and execution
rules. Keep machine-specific paths and wrapper configuration out of package CI.

## Shared commit and release policy

Use the repository-local [.release/README.md](.release/README.md) and
`.release/config.json` as the shared Core-Panel/OneHub release standard. New commits
use `<emoji> <Type>: <imperative English summary>`. Reserve `✨ Feature:` for an
independent new capability; use Fix, Chore, Refactoring, Docs, Test or Deps as
appropriate. Normal commits and pushes never authorize a production release.
A user-requested release uses `🚀 Release: patch|minor|major|X.Y.Z` (bare means
patch), or an explicit manual workflow dispatch with `bump`/`version`. Never add
a release-trigger commit while merely implementing release tooling. Generated
version commits use `⚙️ Chore: set version to ...`. Preserve historical tags and
project-specific builds, publication/deployment approvals, languages and assets.
Preview notes and run `.release/release.test.mjs` before release-tooling changes
are committed. Do not commit, push or publish unless requested by the user.

New CHANGELOG entries use the same published release notes through
`.release/changelog.mjs`; do not maintain a second manually categorized summary.
After a successful authorized release, `.release/publish-changelog.mjs` records
and fast-forward-pushes the changelog documentation commit. Preserve old version
entries, release tags and any newer Unreleased notes. Never run this publication
helper merely while implementing or testing release tooling.
