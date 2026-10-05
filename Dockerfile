FROM php:8.2-apache

# فعال کردن mod_rewrite برای URL های تمیز
RUN a2enmod rewrite

# کپی کردن همه فایل‌ها به پوشه وب سرور
COPY . /var/www/html/

# تنظیم مجوزها
RUN chown -R www-data:www-data /var/www/html

# پورت
EXPOSE 80
