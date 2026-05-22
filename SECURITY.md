# Security Policy

## Vulnerability Triage

`composer security:check` scans `composer.lock` against the Security Advisories database. When CI reports a CVE, follow this decision tree:

```
CVE found in security:check
├─ Is a patched version available?
│  ├─ Yes → upgrade the dependency
│  └─ No  → check the CVE title/link
│           └─ Does the vulnerability affect how this app uses the package?
│              ├─ No → add to allow-list with a comment explaining why
│              └─ Yes → check severity
│                       ├─ Medium / Low → allow-list, document reasoning
│                       └─ High / Critical → escalate (replace, fork, remove feature, or apply a patch)
```

### Allow-listing a CVE

```bash
composer security:check -- --allow-list CVE-XXXX-XXXXX
```

Add the `--allow-list` flags plus the CVE IDs (or titles) after `--` to pass them through to the security checker. In CI, update the `.github/workflows/ci.yml` command to include the allow-list.

### When a CVE doesn't apply

Example: Twig CVE-2026-46627 says "sandbox does not protect against resource exhaustion." If the app doesn't use Twig's sandbox feature, the vulnerability path doesn't exist. Allow-list it and move on.

### When a CVE needs immediate action

- **Replace the dependency** if a secure alternative exists
- **Apply a patch** using `cweagans/composer-patches` (install first: `composer require --dev cweagans/composer-patches`) if a fix commit is available but not yet released
- **Remove the vulnerable feature** if the package is only used for one thing

## Reporting a Vulnerability

If you discover a security issue in this project itself (not a dependency), please open a private issue on GitHub or contact the maintainer directly. Do not file a public issue.