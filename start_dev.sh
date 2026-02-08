
pm2 start "php artisan serve --host=0.0.0.0 --port=8009" --name tool-library-api
pm2 start npm --name "tool-library" -- run dev
