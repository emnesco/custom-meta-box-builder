# Architectural Decisions

## AD-001: Execution Order - Security First

- **Problem:** 14 critical fixes need ordering
- **Options:** Alphabetical, by file, by severity, by dependency
- **Decision:** Security fixes first, then crash prevention, then WP.org compliance
- **Reasoning:** Security vulnerabilities have the highest real-world impact. Crash-prevention fixes prevent data loss. WP.org compliance enables distribution. i18n infrastructure first (not full wrapping) to unblock other work.
