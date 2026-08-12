# Force MFA setup (`local_forcemfa`)

`local_forcemfa` is a Moodle local plugin that requires covered, authenticated users to configure at least one genuine Moodle MFA factor before they continue into the site.

The plugin delegates authentication and MFA challenges to Moodle's `tool_mfa`. It does not implement authentication, tenancy, account approval, course access, or permissions.

## Requirements

- Moodle 4.5 or later (`$plugin->requires = 2024100700`).
- The core `tool_mfa` plugin from Moodle 4.5 or later.
- PHP versions supported by the installed Moodle release.

CI verifies the plugin against Moodle 4.5, 5.0, 5.1, and 5.2. Later Moodle releases are compatibility candidates until they pass the same suite.

## Installation

1. Copy this directory to `local/forcemfa` under the Moodle installation.
2. Visit **Site administration > Notifications** and complete the plugin installation.
3. Configure Moodle MFA before enabling forced setup:
   - enable `tool_mfa`;
   - ensure covered authenticated users have `tool/mfa:mfaaccess`;
   - enable **No setup** with a weight of at least 100; and
   - enable at least one positive-weight factor that users can set up, such as TOTP, WebAuthn, or SMS.
4. Review **Site administration > Reports > Security checks**. The **Forced MFA setup configuration** check must pass.
5. Open **Site administration > Plugins > Local plugins > Force MFA setup** and select the policy.

The policy defaults to **Disabled**. The other choices are:

- **Enabled except site administrators**: users listed by Moodle as actual site administrators are exempt. Workplace tenant administrators, managers, and capability-based administrators are not exempt.
- **Enabled for everybody**: site administrators are covered too. If the rollout configuration becomes unusable, actual site administrators retain repair access as a break-glass measure.

The plugin never changes `tool_mfa` settings automatically.

## What counts as configured MFA

A factor qualifies when the public `tool_mfa` factor API reports that it:

- is enabled;
- supports user setup (`has_setup()`);
- has positive weight; and
- has an active, non-revoked instance for the current user.

The checker is factor-agnostic, so compatible third-party user-setup factors follow the same contract. Passive factors, zero-weight factors, missing records, and revoked records do not qualify.

## Request behaviour

Enforcement runs from Moodle 4.5's `core\hook\after_config` hook after `tool_mfa` has handled the authenticated session. Moodle and Workplace onboarding remains authoritative for incomplete profiles, forced password changes, policy acceptance, login confirmation, pending MFA authentication, and related flows.

Normal page requests are redirected to Moodle's MFA preferences page. A one-time return target contains only a validated site-local path and query in the current session; schemes, hosts, externally supplied return targets, scheme-relative URLs, and backslash forms are rejected. AJAX and web-service requests receive a localized Moodle exception rather than a redirect.

If forced setup is enabled while the supported rollout configuration is unusable, ordinary covered users receive a non-looping generic support page. The page does not reveal MFA configuration details.

## Moodle Workplace and multi-tenancy

The MVP policy is global and MFA state is user-level, so it does not need to resolve a Workplace tenant. The plugin:

- checks only the currently authenticated user;
- stores no tenant or user database state;
- performs no user search or cross-tenant query;
- does not infer tenancy from URLs, hosts, categories, roles, or profile fields;
- does not alter tenant allocation, authentication, permissions, or authorization;
- uses Moodle URL APIs and preserves only a relative return target; and
- ignores course ownership, including shared courses, leaving authorization to Workplace.

Authentication methods such as manual accounts, email self-registration, OAuth 2, and SAML remain authoritative. Enforcement begins only after Moodle has established a legitimate session.

Workplace source is licensed and is not part of the public Moodle test matrix. A release must therefore pass the [Workplace acceptance matrix](docs/workplace-acceptance.md) on the target licensed installation, including any custom tenant domains, before production deployment.

## Development and testing

The repository contains PHPUnit coverage for policy, factor qualification, rollout health, safe routes, and return-target validation, plus Behat coverage for redirect, configured, revoked, disabled-policy, administrator, and deep-link-resume flows.

Moodle Plugin CI runs PHP lint, coding style, PHPDoc, plugin validation/dependency checks, upgrade savepoints, PHPUnit, privacy tests, and Behat for each supported Moodle branch.

## Privacy

The plugin creates no database tables and stores no personal data. While setup is pending, it stores a validated site-local return path temporarily in the current user's Moodle session.

## License

Copyright © 2026 Tamas Kery.

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or any later version.
