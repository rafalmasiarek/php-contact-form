FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends msmtp ca-certificates && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    { \
      echo "defaults"; \
      echo "tls off"; \
      echo "logfile /dev/stderr"; \
      echo "account mailhog"; \
      echo "host mailhog"; \
      echo "port 1025"; \
      echo "from no-reply@example.test"; \
      echo "account default : mailhog"; \
    } > /etc/msmtprc && chmod 600 /etc/msmtprc

RUN echo 'sendmail_path = "/usr/bin/msmtp -t"' > /usr/local/etc/php/conf.d/sendmail.ini

WORKDIR /app/examples
CMD ["php","-S","0.0.0.0:8080","-t","public"]
