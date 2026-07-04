---
name: Nginx extensionless PHP routing
description: How to correctly route extensionless URLs (e.g. /login, /register) to .php files under nginx + php-fpm without breaking POST requests.
---

Legacy PHP apps often have forms/links that POST to extensionless paths (`action="register"`, `action="login"`) instead of `register.php`/`login.php`.

**Broken approach:** `try_files $uri $uri/ $uri.php /index.php?$args;`
This looks correct but fails for POST requests (returns 405). Reason: when `try_files` finds an intermediate parameter (like `$uri.php`) as an existing file, nginx serves it using the **current location's** directives without re-matching other `location` blocks — it does NOT get passed to the `location ~ \.php$` fastcgi handler. It gets served as a static file, and static file handlers reject POST with 405.

**Working approach:** use a named internal location with a `rewrite ... last` so nginx fully re-evaluates location matching against the new URI (which will then correctly hit the `~ \.php$` fastcgi block), preserving the HTTP method and body:

```nginx
location / {
    try_files $uri $uri/ @extensionless_php;
}

location @extensionless_php {
    rewrite ^/(.*)$ /$1.php last;
}
```

**Why:** Only the *last* parameter of `try_files` (or an explicit named location target) triggers a true internal redirect that re-runs location matching. Intermediate file-check parameters do not.

**How to apply:** Any nginx + php-fpm setup serving a legacy app with pretty/extensionless URLs that must support POST.
