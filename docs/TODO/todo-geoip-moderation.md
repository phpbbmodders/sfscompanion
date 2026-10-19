# GeoModerate — potential features for SFS Companion

These are proposed improvements transferred from `worthlookingat.md`. Historical source descriptions have not been reverified during this move; review current implementation, compatibility, and overlap before implementation.

Original repository:&#x20;

github.com

&#x20;Author: Matt Gibson Version: 0.3.0 Target: phpBB 3.3.x



I inspected the source, including the event listener, country rules, ACP controller, and dependencies.

GeoModerate is a strong feature candidate for SFS Companion, particularly if you want SFS Companion to handle additional spam-risk signals beyond Stop Forum Spam database matches.

Its central idea is straightforward: administrators select countries, and posts originating from those countries are automatically sent to phpBB's moderation queue.

### What GeoModerate actually does

- Uses the MaxMind GeoLite2 Country database to determine the poster's country from their IP address.
- Provides an ACP list of countries that administrators can select for moderation.
- Intercepts posting through `core.posting_modify_submit_post_before`.
- Sets `force_approved_state` to `ITEM_UNAPPROVED` when a country's moderation rule matches.
- Automatically exempts administrators and moderators.
- Records moderation actions in phpBB's moderation log.
- Allows posts through if geolocation fails, while logging the error.

Crucially, it does not ban users, reject registrations, or delete posts.

That distinction is worth preserving. Geographic origin alone is a weak indicator of spam, and sending a post for human review is considerably more reasonable than outright blocking it.

## Features worth incorporating

| Proposed feature                     | Priority | Recommendation                         |
| ------------------------------------ | -------- | -------------------------------------- |
| Country-based post moderation        | High     | Adopt                                  |
| ACP country selection                | High     | Adopt                                  |
| Moderator/administrator exemptions   | High     | Adopt                                  |
| Moderation logging                   | High     | Adopt                                  |
| Local GeoIP database                 | High     | Adopt with updated database management |
| Per-forum country rules              | Medium   | Consider                               |
| Newly registered user restriction    | Medium   | Consider                               |
| Country combined with SFS results    | Medium   | Consider                               |
| Country-based registration rejection | Low      | Do not add initially                   |

### 1. Country-based moderation

I'd make this an optional GeoIP Moderation feature within SFS Companion.

Administrators could select countries whose posts require approval. Each matching post would enter the existing phpBB moderation queue, without changing the member's normal posting permissions.

I would keep it disabled by default.

### 2. Modern GeoIP database handling

The original repository includes a `GeoLite2-Country.mmdb` database file. That is not an ideal modern distribution approach.

SFS Companion should instead support a locally stored MaxMind-compatible database, with a way to update it.

Possible enhancements include automatic updates using administrator-provided credentials, reporting the database's age in the ACP, and gracefully handling missing or outdated data.

GeoIP lookups should remain local rather than creating an external API request for every post.

### 3. Configurable exemptions

GeoModerate exempts everyone with moderator or administrator permissions.

I'd preserve that default but consider an additional exemption for trusted groups.

For example, an administrator could apply geographic moderation only to newly registered users, while established members remain unaffected.

That would reduce unnecessary moderation of legitimate members.

### 4. Combining geographic and SFS signals

This is where integrating the feature into SFS Companion could become particularly useful.

Rather than treating geographic origin as proof of spam, it could be an additional factor in deciding whether a post requires approval.

For example:

| Country rule | SFS result | Proposed action                                        |
| ------------ | ---------- | ------------------------------------------------------ |
| No match     | No match   | Normal posting                                         |
| Match        | No match   | Queue for approval                                     |
| No match     | Match      | Existing configured SFS action                         |
| Match        | Match      | Existing SFS action, with country information recorded |

For the first implementation, I would not introduce a numerical spam-risk scoring system. That would add considerably more complexity without being necessary to deliver the original feature.

### 5. Per-forum rules

This could be useful eventually, especially for boards with public discussion areas alongside trusted or restricted forums.

However, I would make it a second-phase enhancement. A global country list is a reasonable starting point.

## What I would avoid

I would not directly copy GeoModerate's implementation into SFS Companion without checking compatibility, licensing obligations, and the existing architecture.

Its event usage and moderation-state handling are useful references, but the code is from an older phpBB/PHP environment. The MaxMind dependency and database handling also need modern review.

GeoIP results can be wrong, and VPNs, proxies, and mobile networks complicate country detection. That is another reason to keep moderation rather than outright rejection as the primary action.

## Recommended scope

SFS Companion — GeoIP Moderation

Suggested initial feature set

Optional country-based moderation

Country selection in ACP

Local GeoIP database with update support

Exempt administrators and moderators

Use existing phpBB moderation queue

Log geographic moderation decisions

Graceful handling of lookup failures

Write proposal for selected features&#x20;

My recommendation: Add GeoModerate's core functionality as a standalone, optional feature module inside SFS Companion. Keep country rules, GeoIP lookup, moderation decisions, and database updating separate from the existing SFS checks.

That provides a useful new capability without turning SFS Companion into an unnecessarily complicated spam-scoring framework.

I haven't verified SFS Companion's current repository implementation, so the integration details and potential overlap still need a source-level review.
