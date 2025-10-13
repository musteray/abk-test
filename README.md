# ABK - Project Exercises 👋

This repo have 4 exercises.

### System requirements

1. Docker 28.4.0

### Setup

1. Clone repo

   ```bash
   git clone git@github.com:musteray/abk-test.git
   ```


2. Build docker

   ```bash
   docker compose up -d
   ```

3. Download phpunit inside docker

   ```bash
   1. docker exec -it <lamp-server> sh
   2. cd abk
   3. composer install
   ```

4. Run PhpUnit tests

   ```bash
   1. docker exec -it <lamp-server> sh
   2. cd abk
   3. ./vendor/bin/phpunit tests/CustomerFormTest.php --testdox
   ```

* Access http://localhost/index.php

### Referrences

1. [Lamp Docker](https://github.com/sprintcube/docker-compose-lamp)
2. ...