FROM php:8.5-fpm

RUN echo deb http://ftp.pl.debian.org/debian bookworm main non-free-firmware >> /etc/apt/sources.list
RUN echo deb-src http://ftp.pl.debian.org/debian bookworm main non-free-firmware >> /etc/apt/sources.list
RUN echo deb http://security.debian.org/debian-security bookworm-security main non-free-firmware >> /etc/apt/sources.list
RUN echo deb-src http://security.debian.org/debian-security bookworm-security main non-free-firmware >> /etc/apt/sources.list
RUN echo deb http://ftp.pl.debian.org/debian bookworm-updates main non-free-firmware >> /etc/apt/sources.list
RUN echo deb-src http://ftp.pl.debian.org/debian bookworm-updates main non-free-firmware >> /etc/apt/sources.list

RUN apt-get update -y
RUN apt-get install -y \
    git \
    vim \
    wget \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    zip

RUN pecl install apcu

RUN docker-php-ext-enable apcu

RUN docker-php-ext-install calendar
RUN docker-php-ext-install intl
RUN #docker-php-ext-install opcache
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install soap
RUN docker-php-ext-install sockets
RUN docker-php-ext-install zip
