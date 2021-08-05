FROM php:7.4-fpm

LABEL maintainer="Andreas Kempe <andreas.kempe@byte-artist.de>"

ARG DEBIAN_FRONTEND=noninteractive

#RUN locale-gen de_DE.UTF-8

#ENV LANG de_DE.UTF-8
#ENV LANGUAGE de
#ENV LC_ALL de_DE.UTF-8

RUN apt-get update \
	&& apt-get install -y \
		zip \
		unzip \
		vim \
		wget \
		curl \
		git \
#		mysql-client \
		moreutils \
		dnsutils \
		zlib1g-dev \
		libicu-dev \
		libmemcached-dev \
    libzip-dev \
    libxslt-dev \
    locales \
		g++ \
    && rm -rf /var/lib/apt/lists/*

RUN sed -i 's/# de_DE.UTF-8 UTF-8/de_DE.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set your timezone here
#RUN rm /etc/localtime
#RUN ln -s /usr/share/zoneinfo/Asia/Dubai /etc/localtime
#RUN "date"

# Run docker-php-ext-install for available extensions
RUN docker-php-ext-configure intl && \
    docker-php-ext-install pdo pdo_mysql opcache intl zip xsl

# install xdebug
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug && \
#    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.default_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.remote_autostart = 0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.collect_return=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.collect_assignments=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.max_nesting_level = 250" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.remote_handler = \"dbgp\"" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.remote_connect_back = 1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.idekey = \"VSCODE\"" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.remote_port = 9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.var_display_max_depth = 5" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.var_display_max_children = 256" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.var_display_max_data = 1024" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.remote_host={{ hostvars[inventory_hostname]['ohai_network']['default_gateway'] }}" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.mode = develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host = 172.17.0.1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request = yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
#    echo "xdebug.file_link_format = 'vscode://file/media/Austausch/Projekte/PHP/translation-tool/%f:%l:%c>'" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
    echo "xdebug.file_link_format = 'vscode://file/media/Austausch/Projekte/PHP/translation-tool/%f:%l:%c>'" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
#    echo "xdebug.file_link_format = 'vscode://file%f:%l&/app/>/media/Austausch/Projekte/PHP/translation-tool/'" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN pecl install memcached && \
  docker-php-ext-enable memcached

RUN pecl install redis && \
  docker-php-ext-enable redis

RUN apt-get clean && \
	rm -rfv /tmp/* /var/lib/apt/lists/* /var/tmp/*

RUN usermod -u 1000 www-data

ADD additional_parameters.ini /usr/local/etc/php/conf.d/

WORKDIR /app

# USER www-data

CMD php-fpm -F
