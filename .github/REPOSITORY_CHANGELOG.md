# Repository Changelog

Repository, CI, contributor, and GitHub platform changes are tracked here. These entries are for maintainers and should not be promoted into plugin version notes.

Plugin-facing release notes belong in `CHANGELOG.md`.

## Unreleased

### Changed

- Updated README and WordPress.org readme documentation to include plugin management abilities and administrator role requirements.
- Clarified E2E manifest coverage documentation for the current 37 registered abilities and 57 manifest test cases.
- Updated pull request and contribution guidance so external contributors can apply WordPress.org guideline checks when relevant and keep docs, changelogs, and E2E coverage in sync.
- Added repository agent instructions for repeatable ability, documentation, changelog, contribution, and validation workflows.
- Clarified that local agent validation is a preflight check and GitHub Actions remains the authoritative PR validation gate.

### Fixed

- Avoided Docker Compose project-name collisions between local and GitHub Actions E2E runs.
