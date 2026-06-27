# Configuration Guide for MarkFlat CMS

This guide explains how to configure MarkFlat CMS to match your needs. MarkFlat uses environment variables and configuration files to customize its behavior.

## Environment Variables

### Site Configuration

| Variable | Description | Default |
|---|---|---|
| `MF_CMS_SITE_NAME` | Your site name | `MarkFlat CMS` |
| `MF_CMS_THEME` | Theme to use (see [Theming](./theming.md)) | `default` |
| `MF_CMS_FAVICON` | Path to your favicon (relative to `public/`) | *(none)* |
| `MF_CMS_POSTS_PER_PAGE` | Number of posts per page | `10` |
| `MF_CMS_DEFAULT_LOCALE` | Default locale | `en` |
| `MF_CMS_SUPPORTED_LOCALES` | Supported locales as JSON array | `["en","fr"]` |
| `MF_CMS_POSTS_DIR` | Posts content directory | `/posts` |
| `MF_CMS_PAGES_DIR` | Pages content directory | `/pages` |
| `MF_CMS_ELEMENTS_DIR` | Elements content directory | `/elements` |

### Setting a Custom Favicon

To add a favicon to your site:

1. Place your favicon file in the `public/` directory (e.g., `public/assets/images/favicon.ico`)
2. Set the path in your `.env` file:

```env
MF_CMS_FAVICON=assets/images/favicon.ico
```

The path is relative to the `public/` directory. Supported formats include `.ico`, `.png`, `.svg`, and `.gif`.

When `MF_CMS_FAVICON` is not set, no favicon link is rendered in the HTML, and browsers will use their default behavior (typically requesting `/favicon.ico` from the root).

## Configuration Files

### Theme Configuration

Themes are configured in PHP files under `config/themes/`:

```php
// config/themes/custom.php
return [
    'body' => 'bg-white dark:bg-gray-900',
    'nav' => 'bg-white/80 dark:bg-gray-800/80',
    // ... other theme elements
];
```


## Next Steps

- Explore [Contributing](./contributing.md)
- Learn about [Content Management](./content-management.md)
- Check out [Theming System](./theming.md)
