FROM composer:1.8
LABEL authors="Diana Kovalenko <dkhunter10@gmail.com>,\
 Dmitry Pavlov <dsp25no@gmail.com>"

WORKDIR /php-chain
COPY composer.json .
RUN composer install --no-dev

COPY lib lib
COPY analyze.php .
COPY config.php .

ENTRYPOINT ["php",  "/php-chain/analyze.php"]
