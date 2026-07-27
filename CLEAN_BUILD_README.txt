DURA CABS CLEAN SOURCE BUILD

Removed from this package:
- Git history (.git)
- Composer dependencies (vendor)
- Node dependencies (node_modules)
- Runtime caches, logs, sessions and compiled views
- Environment secrets and backup .env files
- Database dump/backup files and nested ZIP backups
- Exact duplicate public/storage files
- Obvious accidental zero-byte shell-command fragment files

After extracting on the server:
1. Copy .env.example to .env and configure production values.
2. Run: composer install --no-dev --optimize-autoloader
3. Run: npm ci && npm run build   (only when rebuilding frontend assets)
4. Run: php artisan storage:link
5. Run: php artisan config:cache
6. Run: php artisan route:cache
7. Run: php artisan view:cache

The uploaded media remains in storage/app/public.
