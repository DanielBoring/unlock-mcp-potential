# WP MCP Abilities

A WordPress plugin that registers core content management abilities for the [MCP Adapter](https://github.com/WordPress/mcp-adapter) plugin, making them accessible to AI agents via the Model Context Protocol (MCP).

## What it is

The MCP Adapter plugin (by WordPress.org) provides the *framework* for exposing WordPress functionality over MCP — but it ships with zero content management abilities out of the box. Only plugins that explicitly register abilities show up as MCP tools.

This plugin fills that gap. It registers 24 abilities covering the operations an editor needs day-to-day: reading and writing posts and pages, managing taxonomy, moderating comments, running health checks, auditing security, and analyzing SEO.

## Why you'd use it

If you're connecting an AI assistant (like Claude) to a WordPress site via the `@automattic/mcp-wordpress-remote` MCP server, without this plugin the AI can only see whatever other plugins have registered — typically nothing beyond Yoast SEO scores. This plugin gives the AI a full working vocabulary for your site.

## When it makes sense

- You want an AI agent to draft, update, or publish posts and pages
- You want to ask an AI to audit your site's security or health posture
- You want SEO analysis integrated into your content workflow
- You're running `@automattic/mcp-wordpress-remote` and `mcp-adapter-discover-abilities` returns almost nothing

---

## Architecture

```
AI Agent (e.g. Claude Code)
        │
        │  MCP Protocol (JSON-RPC over HTTP)
        ▼
@automattic/mcp-wordpress-remote          ← MCP server (npm process, runs locally)
        │
        │  WordPress REST API
        │  POST /wp-json/mcp/mcp-adapter-default-server
        ▼
WordPress Site
  ├── MCP Adapter plugin                  ← framework: registers the REST endpoint,
  │     (WordPress/mcp-adapter)               handles sessions, routes MCP calls
  │
  └── WP MCP Abilities plugin             ← this plugin: registers the actual
        (this repo)                           abilities the AI can call
```

The MCP Adapter handles the transport layer. This plugin handles the *content* — it registers abilities using `wp_register_ability()` that the adapter then exposes as MCP tools.

---

## Abilities

### Posts
| Ability | Description | Required Capability |
|---|---|---|
| `core/list-posts` | List posts with filters (status, search, author, category, pagination) | `edit_posts` |
| `core/get-post` | Get a single post by ID | `edit_posts` |
| `core/create-post` | Create a new post with title, content, status, categories, tags | `edit_posts` |
| `core/update-post` | Update an existing post | `edit_posts` |
| `core/delete-post` | Move a post to trash | `delete_posts` |

### Pages
| Ability | Description | Required Capability |
|---|---|---|
| `core/list-pages` | List pages with filters | `edit_pages` |
| `core/get-page` | Get a single page by ID | `edit_pages` |
| `core/create-page` | Create a new page | `edit_pages` |
| `core/update-page` | Update an existing page | `edit_pages` |
| `core/delete-page` | Move a page to trash | `delete_pages` |

### Taxonomy
| Ability | Description | Required Capability |
|---|---|---|
| `core/list-categories` | List all categories | `read` |
| `core/list-tags` | List all tags | `read` |
| `core/create-category` | Create a new category | `manage_categories` |
| `core/create-tag` | Create a new tag | `manage_categories` |
| `core/delete-category` | Permanently delete a category by ID | `manage_categories` |
| `core/delete-tag` | Permanently delete a tag by ID | `manage_categories` |

### Comments
| Ability | Description | Required Capability |
|---|---|---|
| `core/list-comments` | List comments with filters (post, status, search) | `edit_posts` |
| `core/approve-comment` | Approve a comment | `moderate_comments` |
| `core/trash-comment` | Move a comment to trash | `moderate_comments` |
| `core/spam-comment` | Mark a comment as spam | `moderate_comments` |

### Site Health
| Ability | Description | Required Capability |
|---|---|---|
| `core/site-health-check` | Run WordPress's built-in health tests; returns results grouped by severity (critical / recommended / good) | `read` |

### Security Audit
| Ability | Description | Required Capability |
|---|---|---|
| `core/security-audit` | Check for common security issues: debug mode, file editor, SSL, admin username, WP/plugin version currency, XMLRPC, and auth key strength | `read` |

Returns findings in `fail` / `warn` / `pass` buckets with actionable descriptions.

### SEO Analysis
| Ability | Description | Required Capability |
|---|---|---|
| `core/seo-analyze-post` | Analyze a single post or page: title length, word count, meta description, focus keyword placement, image alt text, internal links, slug length | `edit_posts` |
| `core/seo-site-overview` | Site-wide SEO snapshot: sitemap and robots.txt accessibility, count of published posts missing Yoast focus keyword or meta description | `read` |

SEO abilities complement Yoast SEO — they read Yoast meta fields (`_yoast_wpseo_focuskw`, `_yoast_wpseo_metadesc`) where available and add structural analysis on top.

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.9+ |
| PHP | 7.4+ |
| [MCP Adapter plugin](https://github.com/WordPress/mcp-adapter) | Latest |
| [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) | Optional — SEO abilities degrade gracefully without it |

---

## Installation

### 1. Install the MCP Adapter plugin

This plugin depends on MCP Adapter being installed and active. Install it first via WP Admin or by cloning the repo into `wp-content/plugins/`.

### 2. Install WP MCP Abilities

**Option A — Upload zip (recommended for most sites)**

1. Download or build the zip:
   ```bash
   git clone https://github.com/your-org/wp-mcp-abilities.git
   cd wp-mcp-abilities
   zip -r wp-mcp-abilities.zip . --exclude='.git/*'
   ```
2. In WP Admin, go to **Plugins → Add New → Upload Plugin**
3. Upload `wp-mcp-abilities.zip` and click **Install Now**
4. Click **Activate Plugin**

**Option B — Direct file copy (server access)**

```bash
cp -r wp-mcp-abilities /var/www/html/wp-content/plugins/
wp plugin activate wp-mcp-abilities
```

### 3. Connect your MCP client

Configure `@automattic/mcp-wordpress-remote` to point at your WordPress site. In Claude Code (`~/.claude/settings.json`):

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_SITE_URL": "https://your-site.com",
        "WP_USERNAME": "your-editor-username",
        "WP_APP_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

Generate an application password in WP Admin under **Users → Profile → Application Passwords**.

---

## Verification

After activation, call the discover ability from your MCP client:

```
mcp-adapter-discover-abilities
```

You should see 27 abilities: 3 from MCP Adapter itself + 24 from this plugin.

Test a few to confirm they're working:

```
# List the 5 most recent published posts
core/list-posts  { "status": "publish", "per_page": 5 }

# Run a security audit
core/security-audit  {}

# Check site health
core/site-health-check  {}
```

---

## Security notes

- All abilities enforce WordPress capability checks via `permission_callback`. The ability list reflects what the authenticated user is actually allowed to do — an editor cannot call abilities that require admin caps.
- `delete-post` and `delete-page` move content to trash, not permanent deletion.
- Content is sanitized on write: `sanitize_text_field()` for strings, `wp_kses_post()` for HTML content, `absint()` for IDs, and enum validation for status fields.
- No direct database queries — all reads and writes go through the WordPress API.
