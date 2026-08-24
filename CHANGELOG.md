# Changelog

All notable changes to Minecraft Toolkit are documented in this file.

Released versions are immutable. Every new change is documented under a new version section.

This project is source-available, not open source. See [`LICENSE`](./LICENSE) for usage rights.

## [1.3.8] - 2026-08-24

### Added

- Added a persistent setup-operation workflow that resumes through the Laravel queue and scheduler after Panel or Wings interruptions.
- Added mandatory locked Pelican/Wings full-server safety backups before setup changes any server that already contains files.
- Added explicit setup safety confirmation, live setup-stage reporting, backup identifiers, database notifications, and a configurable two-hour backup timeout.

### Changed

- Changed setup uploads to be staged privately on the Panel host so icons and custom Modpacks remain available to the background worker.
- Changed setup JAR, ZIP, EULA, properties, icon, and restore writes to use verified atomic replacement with automatic targeted restore attempts on failure.
- Changed existing-server setup to stop safely when no backup slot, backup host, successful backup result, or verified checksum is available; successful safety backups remain locked.

### Fixed

- Fixed partial setup state after simultaneous node outages by persisting every operation stage and recovering queued, backup-pending, and interrupted installation work.
- Fixed the setup review step silently accepting overwrite risk through a hidden always-true field.
- Fixed Modrinth, CurseForge, search, selection, and pagination actions jumping from the Plugins/Mods step back to Server Software by persisting the active Filament wizard step.
- Fixed package-browser Livewire actions rebuilding the wizard from the first step by removing form-state mutations during rendering and assigning stable wizard and step keys.
- Fixed fresh `/plugins` and `/mods` directories being treated as fatal Wings 404 errors before selected Modrinth or CurseForge packages could be downloaded.
- Fixed failed selected Mods/Plugins being reported as a completed setup; package failures now fail the operation and retries reuse packages that were already installed successfully.
- Fixed valid platform-qualified source versions such as Modrinth's `v5.5.71-bukkit` being rejected when the verified JAR declares the equivalent base version `5.5.71`.
- Fixed the updater and manual package verification still using an older version comparison than the setup installer, which incorrectly kept verified packages such as LuckPerms in a failed-health state.
- Fixed rapid setup package selections allowing the custom browser state and Filament's hidden form state to diverge between Livewire requests; the complete selection is now synchronized before it is queued.
- Fixed interrupted setup retries getting stuck on a JAR that was atomically written before its managed database row was committed; checksum-matching source files are now verified and safely adopted, while unknown or mismatching collisions remain untouched.
- Fixed backup-pending setup operations relying solely on a backup event or the scheduler to continue; the unique worker now schedules its own verified continuation while the scheduler remains the outage recovery path.
- Fixed setup and operation records becoming visible as completed at different times, which could redirect to an unavailable page and display Filament's generic page-loading error.
- Fixed the completed setup page denying the worker-status poll before it could redirect to the Toolkit overview, which displayed Filament's generic page-loading error after completion.
- Fixed interrupted Modpack retries rerunning the completed core setup stage.
- Fixed one setup attempt scattering replaced target files across multiple timestamp backup directories; replacements now share one deterministic directory and retries never overwrite the original copies.
- Fixed successful zero-byte safety backups being accepted as verified.
- Documented that the Panel queue worker, scheduler, and Artisan maintenance commands must use the PHP-FPM operating-system user to prevent shared Laravel file-cache ownership from causing Panel-wide HTTP 500 responses.
- Removed Minecraft Modpacks from the sidebar and denied the page on plugin, Vanilla, and Bedrock servers; it is now shown only for completed Fabric, Forge, and NeoForge setups.
- Fixed healthy server software being unable to reach 100/100 because official Minecraft sources and stored SHA-256 metadata were not included in the score and a later update check hid an earlier successful file verification.
- Changed health scores to prominent green, yellow, and red badges; pinning a package is now treated as an update policy instead of a health penalty.
- Changed new server artifacts and successful legacy verification runs to persist their calculated SHA-1/SHA-512 integrity baseline, including non-JAR server archives.

## [1.3.6] - 2026-08-18

### Added

- Added verified atomic server-file writes for downloaded JAR/ZIP artifacts using same-directory temporary files, remote read-back verification, safe replacement backups, and failed-temporary-file cleanup.
- Added scheduled metadata cache warming and update checks with manual approval for file-changing updates.
- Added post-update startup verification, runtime failure detection, rollback recommendations, package health scoring, and Resource Usage Alerts crash correlation.
- Added reusable private/shared package profiles with JSON import/export and setup-state transfer.
- Added optional dependency selection and expanded package details, filters, aliases, notes, pinning, verification, disable/enable, reinstall, removal, and bulk actions.
- Added public Modrinth/CurseForge modpack version selection and corrected `.mrpack`/CurseForge manifest dependency processing.
- Added safe Paper, Purpur, and Folia conversion workflows with backups and conflict diagnostics.
- Added Java and Bedrock access-list editors, world rename/info/backup/restore tools, datapack and resource-pack management, icon crop/resize, performance presets, MOTD preview, and Geyser/Floodgate diagnostics.
- Added read-only managed-state API endpoints and a translated admin/source readiness checklist.
- Added fake Wings storage, source fixtures, translation QA command, Larastan/PHPStan/Pint configuration, and CI quality checks.

### Changed

- Changed generated configuration writes and managed package operations to use validated backups or atomic writes where replacement safety is required.
- Changed the updater to retain disabled managed packages in its inventory and allow safe reactivation.
- Changed scheduled features to require a running Laravel queue worker and scheduler.
- Changed the default JAR compatibility ceiling from Java 21/class version 65 to Java 25/class version 69 and exposed the ceiling in the plugin settings.

### Fixed

- Fixed a panel-wide HTTP 500 on Pelican `1.0.0-beta36` and newer by implementing the new plugin settings data contract while preserving compatibility with beta34 and beta35.
- Fixed Modrinth modpack installation referencing manifest variables before the archive was parsed.
- Fixed conditional Filament notifications calling `persistent()` with an unsupported Boolean argument.
- Fixed newer Minecraft server JARs being rejected solely because the previous global default still targeted Java 21.
- Fixed CurseForge disappearing from setup and installer source selection when the service was enabled and configured but a separate runtime permission check was evaluated while building the options.
- Fixed empty legacy CurseForge proxy URL or secret environment entries overriding the built-in BlueIT defaults, which disabled CurseForge in Setup and Modpacks and showed `Proxy/API-Key fehlt` in Installer.
- Fixed public and uploaded Modpacks being rejected by the JAR-only package filename validator; `.mrpack` and `.zip` archives now use explicit extension allowlists while retaining traversal and control-character protection.
- Fixed CurseForge Modpack dependencies being written with synthetic numeric names such as `curseforge-238222-3043174.jar`; the real CurseForge file metadata and filename are now resolved before installation.
- Fixed Modpack files being written before the database could accept the Modpack record; the inactive record is now validated first and activated transactionally only after all files were installed.
- Changed public Modpack installations to run as unique one-hour queue jobs per server, preventing Livewire request timeouts and reporting completion or failure through Pelican's standard notification inbox.
- Added an in-progress state for queued Modpack installations and automatic backup/removal of synthetic numeric CurseForge filenames left by older failed attempts once the correctly named file is installed.
- Fixed CurseForge Modpacks using the client archive when a linked `serverPackFileId` exists; server packs are now preferred and safe server directories are extracted even when the archive has no CurseForge manifest.
- Changed failed background Modpack notifications to include the concrete exception message instead of only a generic failure title.

## [1.3.5] - 2026-07-22

### Added

- Added configurable security gates for startup edits, risky version changes, package removal, CurseForge usage, Crossplay setup, and raw `server.properties` editing.
- Added security audit log entries for denied or explicitly gated risky Toolkit actions.
- Added an admin checklist on Minecraft Overview for backups, audit logging, CurseForge proxy trust, strict hashes, and risk gates.
- Added backup restore actions on Minecraft Overview for known Toolkit backup files, with current-target backup before restore.
- Added updater support for managed server artifacts, so Vanilla Java, Vanilla Bedrock, Paper, Purpur, Folia, Fabric, Forge, and NeoForge server files can be checked, backed up, and updated from Minecraft Updater.
- Added a version-change compatibility cache keyed by server, target software/version/loader, and installed package fingerprint.
- Added setup templates for common server presets: Vanilla Survival, Paper Performance, Purpur Crossplay, Fabric Technical, Forge Modded, and Bedrock Survival.
- Added setup package profiles for curated starter sets such as Paper Basics, Paper Voice Chat, Fabric Performance, and Modded Voice Chat.
- Added Minecraft Modpacks page with public Modrinth/CurseForge modpack search, custom `.mrpack`/`.zip` uploads, combine/replace installation modes, and switching between installed modpacks with archived files.
- Added setup-time custom modpack uploads so `.mrpack`/`.zip` packs can be installed immediately after the server setup finishes.
- Added signed and localized BlueIT announcements with Pelican inbox delivery, centered image popups, CTA buttons, permissions, and plugin-version targeting.

### Changed

- Changed updater bulk actions and verification labels to cover managed server files as well as plugins and mods.
- Changed updater and installer package cards to show more operational details, including target file paths, version IDs, hashes, Minecraft/loader targets, dependency counts, and install age where available.
- Changed version-change reports to show when compatibility results came from cache.
- Changed German UI strings to use normal umlauts instead of ASCII replacements such as `ue`, `ae`, or `oe`.
- Changed user-facing notifications to prefer the active user locale, with localized generic error bodies for non-German users when low-level service details are not translated yet.
- Changed the plugin version to `1.3.5`.

### Fixed

- Fixed BlueIT announcements using Pelican's top-right toast instead of the centered image popup, and suppressed legacy duplicate toasts.
- Fixed BlueIT announcement popups being hidden behind the server console or missing from the general server overview.
- Fixed BlueIT announcement close buttons not immediately hiding and persisting dismissal of the popup.
- Fixed BlueIT announcement rendering breaking Alpine and Livewire navigation with `_x_teleportBack` errors by mounting the listener directly at Filament's body hook.
- Fixed deleted or no-longer-applicable BlueIT announcements remaining visible after the remote announcement was removed.
- Fixed CurseForge settings saving so the enable toggle no longer stores inverted boolean values and defaults to the signed BlueIT proxy flow.
- Fixed NeoForge version discovery for modern version lines such as `26.2.x` while preserving legacy `1.20.1` NeoForge/Forge compatibility.
- Fixed mixed German/English setup, updater, version-change, settings, and modpack notifications by moving hardcoded page text into language files.

## [1.2.1] - Previous release

### Added

- Added BlueIT CurseForge proxy support using signed Toolkit requests with client id, timestamp, nonce, Toolkit marker header, user-agent binding, and HMAC signatures.
- Added CurseForge proxy secret rotation support and clearer 401 troubleshooting documentation for the BlueIT backend flow.
- Added hidden/default CurseForge configuration behavior so proxy URL, shared secret, and direct API key fields stay empty in the panel unless an administrator intentionally overrides them.
- Added broader Forge Minecraft version discovery by merging Maven metadata with Forge promotion metadata, so older Forge Minecraft versions are available in the setup wizard.
- Added optional Vanilla Bedrock download override configuration for cases where the official Minecraft download page cannot be parsed.

### Changed

- Re-enabled CurseForge through the BlueIT Toolkit proxy while keeping the real CurseForge API key outside the public plugin source.
- Changed CurseForge settings text to show only the BlueIT service host/status instead of exposing the internal proxy path in the panel UI.
- Changed the default Toolkit user agent to the BlueIT Toolkit identifier required by the hardened backend.
- Changed setup package selection to support mixed providers: Modrinth and CurseForge selections now remain selected when switching sources or changing search text, and setup installs the combined selection.
- Changed Vanilla Bedrock version loading so the setup wizard always offers a `Latest official Bedrock server` option when the Minecraft download page cannot be parsed.
- Changed Vanilla Bedrock setup downloads to download the official Bedrock ZIP through the panel first instead of using the Wings pull endpoint directly.
- Updated CurseForge proxy documentation for the official REST API flow, allowlisted endpoints, and server-side `x-api-key` handling.

### Fixed

- Fixed Minecraft setup failing with `Call to undefined method MinecraftServerFileService::extractMaxClassMajorVersionFromJar()` by restoring the JAR class-version scanner and Java compatibility guard.
- Fixed Forge loader version discovery for older Minecraft versions by merging Maven loader builds with Forge promotion metadata and refreshing the loader-version cache key.
- Fixed CurseForge setup browsing showing a generic empty result when the proxy request fails; backend failures are now logged and surfaced more clearly during setup package loading.
- Fixed Vanilla Bedrock setup showing `No options available` when the official Minecraft download page cannot be parsed.
- Fixed Vanilla Bedrock `latest` setup by using the configured official Bedrock Linux ZIP fallback when the Minecraft download page cannot be parsed.
- Fixed Vanilla Bedrock setup failing when Wings returns HTTP 500 for `minecraft.net` pull requests by downloading the ZIP through the panel and writing it to the server files afterward.

## [1.2.0] - Previous release

### Note

- CurseForge now uses the public Toolkit-compatible proxy by default, so normal installs can use Modrinth and CurseForge without shipping a CurseForge API key.
- Administrators can still disable CurseForge, override the proxy URL, set a proxy secret for private proxies, or use a private direct API key.

### Added

- Added download hardening for direct package downloads, including redirect target validation and private/reserved IP blocking.
- Added optional strict package hash mode with `MINECRAFT_TOOLKIT_HASH_REQUIRED`.
- Added JAR magic-byte and archive-entry validation before downloaded JAR files are written to a server.
- Added plugin-local Pint and PHPStan configuration files for release checks.
- Added package pinning so managed plugins/mods can be excluded from update checks, update-all, and automatic version-change updates.
- Added a Minecraft Overview backup inventory for recent Toolkit backup folders.
- Added source status cards to Minecraft Overview for Modrinth, CurseForge, and Crossplay configuration.
- Added updater package verification for installed managed JARs, including stored hash checks, JAR structure validation, metadata extraction, and Java class-version checks.
- Added lightweight package health scores to the updater.
- Added richer installer review metadata, including upstream project links, categories, publish/update dates, file size, source hashes, and compatibility details.
- Added installer search filters for category, author, server-side metadata, minimum downloads, and result sorting.
- Added a lightweight language QA script to compare English/German translation keys and flag common mojibake artifacts.
- Added updater bulk actions to pin all, unpin all, and verify all managed packages.
- Added plugin update feed metadata through `plugin.json` and `update.json`.

- Added paged `server.properties` editing in Minecraft Settings, including standard Java properties plus a full raw editor for unknown or newer values.
- Added a Java class-version safety check for downloaded JAR files. Public builds default to Java 21 / class version 65 and reject newer JARs before writing them to the server.
- Added updater action to install missing required dependencies after the server has already been set up.
- Added updater action to remove managed plugins/mods safely by backing up the file and disabling package management.
- Added initial Pelican plugin structure for Minecraft Toolkit.
- Added server navigation pages for setup, overview, installer, updater, settings, and version changes.
- Added setup wizard for supported Minecraft server software.
- Added support for Vanilla Java setup with official Mojang server JAR handling.
- Added support for Vanilla Bedrock setup with Bedrock Dedicated Server handling.
- Added support for Paper setup.
- Added support for Purpur setup.
- Added support for Folia setup as a plugin-based server type using `/plugins`.
- Added support for Fabric setup and loader version handling.
- Added support for Forge setup and installer-first-start handling.
- Added support for NeoForge setup and installer-first-start handling.
- Added `eula.txt` generation.
- Added Java `server.properties` generation.
- Added Bedrock server properties generation.
- Added automatic server port handling from the primary Pelican allocation.
- Added server icon upload and `server-icon.png` writing for Java servers.
- Added MOTD configuration during setup.
- Added a basic MOTD formatter helper for Minecraft color and style codes.
- Added optional plugin/mod selection directly inside the setup wizard.
- Added automatic installation of selected packages immediately after setup completion.
- Added Modrinth package search and install support.
- Added CurseForge package source support through the BlueIT/Vercel proxy flow.
- Added optional direct CurseForge API key fallback for private/self-hosted installations.
- Added CurseForge disabled mode when neither proxy nor private key is available.
- Added the public Toolkit-compatible CurseForge proxy as the default proxy URL for public builds.
- Added default popular package listing in the installer, so users do not need to search before seeing compatible packages.
- Added pagination for package browsing.
- Added package source, project ID, version ID, file name, target path, dependency, and managed-state tracking.
- Added plugin installation for Paper, Purpur, and Folia into `/plugins`.
- Added mod installation for Fabric, Forge, and NeoForge into `/mods`.
- Added crossplay setup for Paper and Purpur.
- Added GeyserMC and Floodgate installation as managed system packages.
- Added Geyser crossplay configuration patching for Bedrock port, MOTD values, and `auth-type: floodgate`.
- Added update checking for managed plugins/mods.
- Added safe update flow with old file backup before replacement.
- Added Minecraft version change workflow with compatibility checks.
- Added compatibility states for compatible, update required, incompatible, unknown, system package, and manual package cases.
- Added support for removing incompatible packages or accepting risk during version changes.
- Added source-available license file.
- Added license section to the README.
- Added German and English language files for plugin-owned UI strings.
- Added locale fallback logic: German is used when the active locale starts with `de`; every other locale falls back to English.
- Added translated labels for navigation, setup, installer, updater, settings, package sources, server software, status messages, and common actions where the text is controlled by this plugin.
- Added this `CHANGELOG.md` file.

### Changed

- Changed the setup Mods/Plugins step back to the same card-style package browser used by the installer, without rendering it outside the Mods/Plugins wizard step.
- Changed Geyser configuration patching to update the modern `java.auth-type` and `motd` sections instead of only adding legacy/unused keys.
- Changed the setup wizard defaults so no server software is preselected before the user chooses one.
- Marked the plugin as source-available, not open source.
- Clarified that redistribution, rebranding, public forks, modified public releases, and resale are not allowed without permission.
- Moved the intended public CurseForge flow to a backend proxy so the real API key is not exposed in the plugin source.
- Removed the requirement that every normal plugin user must request their own CurseForge API key.
- Added Folia to the supported software table in the README.
- Updated README with setup package installation, popular package browsing, MOTD formatter, crossplay behavior, language behavior, installation notes, and changelog reference.
- Kept the plugin language handling isolated to Minecraft Toolkit and did not change the global Pelican locale.
- Set the plugin version to `1.2.0` as requested for the previous package build.

### Fixed

- Fixed setup package selection showing as an old dropdown instead of the installer-style package browser.
- Fixed setup installs accepting plugin JARs that require a newer Java runtime than the configured server Java version can load.
- Fixed Geyser MOTD/auth patching leaving `motd.primary-motd`, `motd.secondary-motd`, `motd.passthrough-motd`, and `java.auth-type` unchanged.
- Fixed NeoForge version and loader discovery so legacy `1.20.1` loaders from `net/neoforged/forge` and modern NeoForge loaders from `net/neoforged/neoforge` are both selectable, while modern `26.x` Minecraft version labels stay unchanged.

- Fixed setup package selection by replacing the embedded Livewire package-browser partial inside the wizard with a stable Filament multi-select field, preventing `AnonymousComponent::setupPackageSelected()` 500 errors during Modrinth loading.
- Fixed setup package selection rendering before software selection by removing the custom package-browser view from the setup wizard render path.
- Fixed the setup package browser appearing before a server software was selected by starting the setup wizard with no preselected software.
- Reduced setup wizard Livewire side effects by no longer auto-loading package browser results during software/version/loader field updates.
- Fixed updater/install candidate handling so the exact candidate from the last update check is stored and reused during the actual update instead of resolving a possibly different file later.
- Fixed Modrinth update candidate ordering by sorting compatible versions by publish date before selecting the latest release.
- Fixed CurseForge update candidate ordering by sorting compatible files by file date and file ID before selecting the latest release.
- Added downloaded-JAR metadata verification so updates/installations abort if the JAR itself reports an older or different plugin version than the selected candidate.
- Added update candidate metadata storage to update checks so the UI check and the update action use the same version ID, filename, download URL, hashes, and dependency data.
- Fixed package updates that appeared successful but were detected again afterwards because stale `logs/latest.log` data overwrote the freshly updated database version.
- Fixed Geyser/Floodgate update checks so runtime versions like `2.10.0` no longer downgrade stored build versions like `2.10.0+1162`.
- Added post-download verification so an update only succeeds when the new JAR exists and the old target file was removed or backed up.
- Fixed Crossplay configuration patching so Geyser `auth-type` is set to `floodgate` more reliably.
- Fixed Crossplay configuration patching so Geyser Bedrock port and MOTD values are written together.
- Fixed Installer behavior so compatible packages can be shown before a search query is entered.
- Fixed setup package browsing so the setup page uses the same card-style popular/search list behavior as the installer instead of only a compact multi-select dropdown.
- Fixed package result card image sizing by applying hard width/height limits so large external icons cannot stretch the package list layout.
- Fixed package installation so required dependencies reported by Modrinth or CurseForge are installed automatically before the selected package.
- Added a known dependency rule for ViaRewind so ViaBackwards is treated as required even when source metadata is incomplete.
- Fixed updater checks so package records are synchronized with the actually loaded plugin version from `logs/latest.log` before checking for updates.
- Fixed updater checks so a database version mismatch against the real installed plugin version no longer reports a package as current.
- Added detection of recent plugin load failures from `logs/latest.log`, including missing plugin dependencies and Java class-version incompatibilities.
- Fixed updater status handling so runtime-incompatible packages are reported as errors instead of `up to date`.
- Fixed setup package browser placement so the package browser only appears inside the Mods/Plugins wizard step instead of below every setup step.
