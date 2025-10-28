# GitHub Actions Workflows

This directory contains automated workflows for testing and releasing the ACF Service Management Suite plugin.

## Workflows

### 🧪 Test Workflow (`test.yml`)

**Triggers:** 
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

**Jobs:**

1. **PHP Lint** - Validates PHP syntax across multiple versions
   - Tests on: PHP 7.4, 8.0, 8.1, 8.2, 8.3
   - Ensures compatibility across PHP versions

2. **PHPCS** - WordPress Coding Standards validation
   - Installs WP Coding Standards via Composer
   - Checks all PHP files against WordPress standards
   - Reports issues in PR annotations

3. **Plugin Check** - Validates plugin structure
   - Verifies plugin headers exist
   - Confirms version consistency between header and constant
   - Checks all required files are present
   - Scans for development artifacts (.old, .bak files)

4. **Assets Check** - Validates CSS and JavaScript
   - Basic CSS syntax validation
   - JavaScript syntax check with Node.js

5. **Security Scan** - Looks for common security issues
   - Checks for hardcoded credentials
   - Looks for SQL injection vulnerabilities
   - Validates proper output escaping

6. **Test Summary** - Reports overall test status
   - Fails if any job fails
   - Shows consolidated test results

**Status Badge:**
```markdown
[![Tests](https://github.com/ReclaimerGold/acf-service-management-suite/actions/workflows/test.yml/badge.svg)](https://github.com/ReclaimerGold/acf-service-management-suite/actions/workflows/test.yml)
```

---

### 🚀 Release Workflow (`release.yml`)

**Triggers:**
- Push of version tags (format: `v*.*.*`, e.g., `v2.0.0`)

**Jobs:**

1. **Validate Tag** - Ensures proper versioning
   - Validates semantic versioning format (X.Y.Z)
   - Checks version consistency across:
     - Plugin file header
     - `ACF_LS_VERSION` constant
     - CHANGELOG.md
   - Fails if versions don't match

2. **Build Release** - Creates distribution package
   - Copies plugin files to clean build directory
   - Converts README.md to WordPress.org `readme.txt` format
   - Removes development files:
     - `.github/` directory
     - `workspace/` directory
     - `DEVELOP.md`
     - `copilot-instructions.md`
     - `.gitignore`, `.gitattributes`
     - Any backup files (*.old, *.bak, *.tmp)
   - Creates ZIP archive
   - Generates checksums (SHA256, MD5)

3. **Create Release** - Publishes to GitHub
   - Extracts changelog for this version
   - Creates GitHub Release with:
     - Release notes from CHANGELOG.md
     - ZIP package
     - SHA256 checksum
     - MD5 checksum
   - Generates additional release notes

**Release Package Contents:**
```
acf-service-management-suite-2.0.0.zip
├── acf-service-management-suite/
│   ├── acf-location-shortcodes.php
│   ├── includes/
│   ├── assets/
│   ├── acf-export-2025-10-28.json
│   ├── README.md
│   ├── readme.txt (WordPress.org format)
│   ├── CHANGELOG.md
│   └── LICENSE (if exists)
```

---

## Usage

### Running Tests

Tests run automatically on every push and PR. To manually trigger:

1. **Via Git Push:**
   ```bash
   git push origin main
   ```

2. **View Results:**
   - Go to: https://github.com/ReclaimerGold/acf-service-management-suite/actions
   - Click on the test run to see details
   - Check each job for pass/fail status

### Creating a Release

1. **Ensure Version Consistency:**
   ```bash
   # Update version in 4 files:
   # - acf-location-shortcodes.php (header + constant)
   # - README.md (badge)
   # - copilot-instructions.md
   # - CHANGELOG.md
   ```

2. **Commit Version Bump:**
   ```bash
   git add .
   git commit -m "chore(release): bump version to 2.1.0"
   git push origin main
   ```

3. **Create and Push Tag:**
   ```bash
   git tag -a v2.1.0 -m "chore(release): version 2.1.0"
   git push origin v2.1.0
   ```

4. **Monitor Workflow:**
   - Go to: https://github.com/ReclaimerGold/acf-service-management-suite/actions
   - Watch release workflow progress
   - Typically completes in 2-5 minutes

5. **Verify Release:**
   - Check: https://github.com/ReclaimerGold/acf-service-management-suite/releases
   - Download ZIP and verify contents
   - Verify checksums match

### Local Testing

Use [act](https://github.com/nektos/act) to test workflows locally:

```bash
# Install act (macOS)
brew install act

# Test the test workflow
act push -W .github/workflows/test.yml

# Dry-run release workflow
act push -W .github/workflows/release.yml --input tag=v2.0.0 --dry-run
```

---

## Troubleshooting

### Test Failures

**PHPCS Errors:**
- Run locally: `composer require --dev wp-coding-standards/wpcs`
- Fix: `vendor/bin/phpcbf --standard=WordPress .`

**Version Mismatch:**
- Ensure all 4 files have the same version number
- Use conventional commits for version bumps

**Development Artifacts:**
- Remove any .old, .bak, .tmp files
- Use workspace/ directory for temporary files

### Release Failures

**Tag Format Error:**
- Tags must be `vX.Y.Z` format (e.g., `v2.1.0`)
- Delete bad tag: `git tag -d vX.Y.Z && git push origin :refs/tags/vX.Y.Z`

**Version Consistency Error:**
- Check all 4 files have matching versions
- Update and re-tag

**Missing Changelog:**
- Add entry to CHANGELOG.md for the version
- Format: `## [X.Y.Z] - YYYY-MM-DD`

---

## Best Practices

1. **Always Run Tests Before Tagging**
   - Push to main and wait for tests to pass
   - Then create and push tag

2. **Use Semantic Versioning**
   - MAJOR: Breaking changes (`feat!:`)
   - MINOR: New features (`feat:`)
   - PATCH: Bug fixes (`fix:`)

3. **Keep CHANGELOG Updated**
   - Add changes under `[Unreleased]` as you develop
   - Move to version section when releasing

4. **Test Release Packages**
   - Download ZIP from GitHub Release
   - Install on fresh WordPress site
   - Verify all features work

5. **Monitor Actions Tab**
   - Check for any warning or errors
   - Review security scan results
   - Address issues promptly

---

## Permissions

The release workflow requires the `GITHUB_TOKEN` with `contents: write` permission to:
- Create GitHub Releases
- Upload release assets
- Generate release notes

This permission is automatically granted by GitHub Actions when the workflow runs.
