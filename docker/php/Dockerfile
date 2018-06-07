ARG PHP_VERSION=7.1-fpm

FROM php:${PHP_VERSION}

ARG TZ=UTC
ENV TZ ${TZ}
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

ENV COMPOSER_HOME /root/composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
ENV PATH $COMPOSER_HOME/vendor/bin:$PATH

RUN apt-get update \
    && apt-get upgrade -y

RUN apt-get install -ym --no-install-recommends \
        git \
        apt-utils \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libmagickwand-dev \
        libmcrypt-dev \
        openssl \
        libssh-dev \
        libnghttp2-dev

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install zip \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install opcache \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install intl

RUN pecl install redis \
    && pecl install imagick \
    && pecl install apcu \
    && pecl install mongodb \
    && docker-php-ext-enable redis imagick mongodb apcu

RUN rm -r /var/lib/apt/lists/*
