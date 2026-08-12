# Moodle Workplace acceptance matrix

This matrix is a mandatory release gate for every target Moodle Workplace version. Run it on a licensed, representative staging installation with multiple tenants. Record the Workplace build, authentication plugins, domain configuration, tester, date, and evidence for each result.

Do not add tenant fixtures by inferring tenancy from course categories, URLs, hostnames, roles, or profile fields. Create and allocate users through supported Workplace administration and APIs.

## Preconditions

- Install the candidate `local_forcemfa` build in the single Workplace installation.
- Enable `tool_mfa`, a 100-point **No setup** factor, at least one positive-weight user-setup factor, and a passive authentication factor only when needed by the test authentication method.
- Set the global plugin policy to **Enabled except site administrators**, except where a row says otherwise.
- Create Tenant A and Tenant B through Workplace, with an ordinary user in each tenant.
- Create a Tenant A tenant administrator who is not a Moodle site administrator.
- Prepare an actual Moodle site administrator.
- Configure a shared course and tenant custom domains if those features are used in production.
- Confirm logs and database tracing can demonstrate that the plugin inspects only the authenticated user's `tool_mfa` records.

## Required scenarios

| ID | Scenario | Expected result |
|---|---|---|
| WP-01 | Tenant A ordinary user, no active genuine factor | Authentication completes, then the user is redirected to MFA preferences. No Tenant B information is displayed or queried. |
| WP-02 | Tenant A ordinary user, active genuine factor | Normal Tenant A access proceeds under Workplace authorization. |
| WP-03 | Tenant B ordinary user, no active genuine factor | The user is redirected independently of Tenant A state. |
| WP-04 | Tenant B ordinary user, active genuine factor | Normal Tenant B access proceeds under Workplace authorization. |
| WP-05 | Tenant administrator, no active genuine factor | The tenant administrator is enforced as an ordinary user. Tenant-administrator capabilities do not create an exemption. |
| WP-06 | Site administrator, except-administrators policy | Normal access proceeds without factor setup. |
| WP-07 | Site administrator, everybody policy, usable rollout | The site administrator is redirected to setup. |
| WP-08 | Site administrator, everybody policy, unusable rollout | Break-glass repair access remains available. Ordinary users receive only the generic support page. |
| WP-09 | Manual authentication | Authentication completes normally first; forced setup then applies. |
| WP-10 | Email self-registration | Confirmation and required profile/onboarding steps remain authoritative. Enforcement begins only after a legitimate usable session exists. |
| WP-11 | OAuth 2 authentication configured for a tenant | The tenant-specific authentication completes without interference; forced setup then applies. |
| WP-12 | SAML authentication configured for a tenant | The tenant-specific authentication completes without interference; forced setup then applies. |
| WP-13 | Pending registration or account approval | Existing Workplace approval/onboarding behavior remains authoritative; the plugin neither approves nor replaces the flow. |
| WP-14 | Shared-course deep link, no active genuine factor | The user is redirected to setup. The original relative deep link is retained once. |
| WP-15 | Shared-course deep link, active genuine factor | Workplace alone determines whether course access is allowed. |
| WP-16 | Tenant custom domain, no active genuine factor | Setup uses a valid URL for the current domain context and does not force the default tenant hostname unnecessarily. No redirect loop occurs. |
| WP-17 | Tenant custom domain, setup completion | The validated relative return target resumes on the same effective tenant domain, once, without accepting an external origin. |
| WP-18 | User moved from Tenant A to Tenant B | Enforcement immediately reflects the current user's supported Moodle MFA state. No stale plugin tenant state exists because none is stored. |
| WP-19 | Active factor revoked between requests | The next covered request redirects to setup, independently of tenant or course context. |
| WP-20 | AJAX and web service request without a genuine factor | A localized setup-required exception is returned; no cross-domain redirect or tenant data disclosure occurs. |

## Evidence checklist

For every release candidate, retain:

- screenshots or browser traces of setup and return redirects through each supported domain;
- relevant Moodle/Workplace and authentication-plugin versions;
- evidence that pending approval and policy/password flows precede enforcement;
- query/log evidence that no cross-tenant user search occurs;
- results for each production authentication method; and
- confirmation that tenant allocation, authentication settings, permissions, and course authorization are unchanged.

Any failed or unavailable row blocks the production compatibility claim until it is resolved or explicitly excluded from the deployment scope.
