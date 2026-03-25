# Stack Research: Community Master WordPress Plugin

**Project:** Community Master
**Researched:** 2026-03-24
**Overall confidence:** HIGH

## Recommended Stack

### Core Platform

| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| PHP | 8.2+ (target 8.3) | Plugin runtime | WordPress 6.7+ recommends 8.3. PHP 8.2 as floor gives broad hosting compat while allowing typed properties, enums, readonly classes. WordPress 7.0 (Apr 2026) drops PHP 7.2/7.3 entirely. | HIGH |
| WordPress | 6.6+ | Host platform | Current stable line. 6.7 added PHP 8.4 beta support. CPT + REST API are mature and stable. | HIGH |

### Plugin Architecture

| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| Native CPT (`register_post_type`) | WP Core | Community Project post type | Zero dependencies. WordPress CPT API is mature, well-documented, and gives you admin UI, REST API, and query integration for free. No plugin needed. | HIGH |
| Native Meta (`register_post_meta`) | WP Core | Custom fields (GitHub URL, installer cmd, etc.) | For 5-6 simple text/URL fields, ACF or Meta Box are overkill. `register_post_meta` with `show_in_rest: true` exposes fields to both the block editor sidebar and REST API natively. No external dependency. | HIGH |
| Native `add_meta_box` | WP Core | Admin edit screen UI | Renders custom field inputs in the classic editor sidebar. Combined with `register_post_meta` for REST exposure. Simple, dependency-free. | HIGH |
| Shortcode (`add_shortcode`) | WP Core | Frontend grid rendering | Maximum theme compatibility. Works in any editor (Classic, Gutenberg, page builders). The project explicitly targets "integration with existing theme" -- a shortcode is the safest path. A Gutenberg block can be added later as enhancement. | HIGH |
| `register_rest_route` | WP Core | Custom API endpoints | For programmatic CRUD (Claude creating projects). Namespace: `community-master/v1`. Application Passwords for auth (already set up per PROJECT.md). | HIGH |
| WordPress Featured Image | WP Core | Project logo/icon | Use `post_thumbnail` support on the CPT instead of a custom media field. WordPress handles all the upload, crop, and responsive image logic. | HIGH |

### Code Quality

| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| WPCS (WordPress Coding Standards) | 3.x | PHP linting | Industry standard for WP plugins. Enforces security patterns (escaping, sanitization, nonces). Install via Composer. | HIGH |
| PHP_CodeSniffer | 3.9+ | Code sniffer engine | Required by WPCS 3.x. | HIGH |
| PHPStan or Psalm | Latest | Static analysis | Catches type errors, null reference bugs. Optional but recommended for PHP 8.2+ strict typing. | MEDIUM |

### Build Tools

| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| None (initially) | - | - | This plugin has no JavaScript build step needed. The frontend is a PHP-rendered shortcode with CSS that inherits theme styles. No React, no Gutenberg block, no webpack. Adding `@wordpress/scripts` only makes sense if/when a Gutenberg block is added later. | HIGH |

### Development Tools

| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| Composer | 2.x | PHP dependency management | For WPCS, autoloading (PSR-4), and any future PHP deps. | HIGH |
| WP-CLI | 2.x | Local testing & deployment | Activate/deactivate plugin, flush rewrite rules, scaffold tests. | MEDIUM |
| PHPUnit + WP Test Suite | Latest | Unit/integration tests | WordPress ships a test bootstrap. Use for testing CPT registration, REST endpoints, meta field validation. | MEDIUM |

### Deployment

| Technology | Version | Purpose | Why | Confidence |
|------------|---------|---------|-----|------------|
| Git + SSH deploy | - | Plugin delivery | Plugin repo on GitHub. Deploy via `git pull` on server or symlink from cloned repo into `wp-content/plugins/`. No build step means no CI/CD pipeline needed initially. | HIGH |

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Custom Fields | Native `register_post_meta` | ACF (Advanced Custom Fields) | Only 5-6 simple fields. ACF adds a plugin dependency for no benefit. ACF shines with 20+ fields, repeaters, flexible content. Overkill here. |
| Custom Fields | Native `register_post_meta` | Meta Box plugin | Same reasoning as ACF. More performant than ACF (1 DB row vs 2 per field) but still unnecessary for simple fields. |
| Custom Fields | Native `register_post_meta` | CMB2 | Abandoned/slow development. Last meaningful update was years ago. Not recommended for new projects. |
| Frontend | Shortcode | Gutenberg Block | Block requires React/JSX build toolchain (`@wordpress/scripts`, webpack). Adds complexity for a simple tile grid. Shortcode works everywhere. Can add block later. |
| Frontend | Shortcode | Elementor Widget | Creates dependency on specific page builder. Against the "integrate with existing theme" constraint. |
| Data Storage | CPT + post_meta | Custom DB tables | WordPress-native approach gives free admin UI, REST API, revision history, trash, search. Custom tables only needed for 10K+ records or complex queries. ~50 projects? CPT is perfect. |
| REST Auth | Application Passwords | JWT plugin | Application Passwords are built into WordPress 5.6+. Already configured (per PROJECT.md). JWT adds a plugin dependency and token refresh complexity. |
| PHP Version | 8.2+ | 7.4 | 7.4 is EOL. No security patches since Nov 2022. meintechblog.de should be on 8.x already. Target 8.2+ to use modern features (readonly, enums, named args). |

## Plugin File Structure

```
community-master/
  community-master.php          # Main plugin file (header, activation hooks)
  includes/
    class-cpt-community-project.php    # CPT registration
    class-meta-fields.php              # Meta field registration + meta boxes
    class-rest-api.php                 # Custom REST endpoints
    class-shortcode.php                # [community_master] shortcode
    class-frontend-renderer.php        # HTML rendering for tile grid
  assets/
    css/
      community-master.css             # Minimal frontend styles
  languages/                           # i18n .pot/.po/.mo files (future)
  composer.json                        # WPCS dev dependency
  README.md                            # Plugin readme
```

## Installation / Setup

```bash
# No npm/node needed. Pure PHP plugin.

# Dev dependencies (for code quality)
composer require --dev wp-coding-standards/wpcs:"^3.0" squizlabs/php_codesniffer:"^3.9"

# Run linter
vendor/bin/phpcs --standard=WordPress community-master.php includes/

# Deploy to WordPress
# Option A: Symlink
ln -s /path/to/repo/community-master /path/to/wordpress/wp-content/plugins/community-master

# Option B: Git clone directly into plugins dir
cd /path/to/wordpress/wp-content/plugins/
git clone git@github.com:meintechblog/meintechblog-community-master.git community-master

# Activate
wp plugin activate community-master
```

## Key Implementation Patterns

### CPT Registration (PHP 8.2+)

```php
add_action('init', function(): void {
    register_post_type('community_project', [
        'labels' => [
            'name'          => 'Community Projects',
            'singular_name' => 'Community Project',
        ],
        'public'       => true,
        'show_in_rest' => true,  // Enables REST API + Gutenberg
        'supports'     => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'menu_icon'    => 'dashicons-groups',
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'community-project'],
    ]);
});
```

### Meta Field Registration

```php
add_action('init', function(): void {
    $fields = [
        'github_url'        => ['type' => 'string', 'description' => 'GitHub repository URL'],
        'one_line_installer' => ['type' => 'string', 'description' => 'One-line install command'],
    ];

    foreach ($fields as $key => $args) {
        register_post_meta('community_project', $key, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $args['type'],
            'description'   => $args['description'],
            'auth_callback' => fn() => current_user_can('edit_posts'),
        ]);
    }
});
```

### REST API Endpoint

```php
add_action('rest_api_init', function(): void {
    register_rest_route('community-master/v1', '/projects', [
        'methods'             => 'POST',
        'callback'            => 'cm_create_project',
        'permission_callback' => fn() => current_user_can('publish_posts'),
        'args'                => [
            'title'       => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
            'github_url'  => ['required' => true, 'type' => 'string', 'validate_callback' => 'wp_http_validate_url'],
        ],
    ]);
});
```

## What NOT to Use

| Technology | Why Not |
|------------|---------|
| ACF / Meta Box / CMB2 | Unnecessary dependency for 5-6 simple fields. Native WordPress APIs handle this cleanly. |
| Custom database tables | CPT + post_meta is the right abstraction for ~50 projects. Custom tables lose admin UI, REST API, revisions. |
| webpack / @wordpress/scripts | No JavaScript build needed. Plugin is server-rendered PHP. Add only if a Gutenberg block is built later. |
| Elementor / page builder widgets | Creates hard dependency on a specific builder. Shortcodes work everywhere. |
| JWT authentication plugins | Application Passwords are built-in since WP 5.6. Already configured. |
| CMB2 specifically | Development has stagnated. Meta Box or ACF are better if you need a fields framework (but you don't). |
| PHP 7.x | EOL. No security support. Target 8.2+ for modern language features and safety. |
| Composer autoloading in production | For 4-5 class files, explicit `require_once` is simpler and has zero overhead. Composer autoload makes sense at 20+ files. |

## Sources

- [WordPress PHP Compatibility and Versions](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/) - Official PHP version support matrix
- [WordPress Requirements](https://wordpress.org/about/requirements/) - Official minimum requirements
- [Dropping PHP 7.2/7.3 support](https://make.wordpress.org/core/2026/01/09/dropping-support-for-php-7-2-and-7-3/) - WordPress 7.0 PHP floor change
- [Registering Custom Post Types](https://developer.wordpress.org/plugins/post-types/registering-custom-post-types/) - Official CPT handbook
- [register_post_type()](https://developer.wordpress.org/reference/functions/register_post_type/) - Function reference
- [Custom Meta Boxes](https://developer.wordpress.org/plugins/metadata/custom-meta-boxes/) - Official meta box handbook
- [Adding Custom REST Endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/) - Official REST API handbook
- [Application Passwords](https://developer.wordpress.org/advanced-administration/security/application-passwords/) - Built-in REST auth
- [WPCS GitHub](https://github.com/WordPress/WordPress-Coding-Standards) - WordPress Coding Standards v3.x
- [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/) - Build tooling (not needed initially)
- [Meta Box vs ACF Comparison](https://isotropic.co/acf-vs-metabox/) - Fields plugin comparison (concluded neither needed)
