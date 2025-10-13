# ABK - Project Exercises 👋

This repo have 4 exercises.

### System requirements

1. Docker latest

### Get started

1. Build docker

   ```bash
   docker compose up -d
   ```

2. Download phpUnit inside docker

   ```bash
   1. docker exec -it <lamp-server> sh
   2. cd abk
   3. composer install
   ```

3. Run PhpUnit tests

   ```bash
   1. docker exec -it <lamp-server> sh
   2. cd abk
   3. ./vendor/bin/phpunit tests/CustomerFormTest.php --testdox
   ```

### Referrences

1. [Lamp Docker](https://github.com/sprintcube/docker-compose-lamp)
2. ...