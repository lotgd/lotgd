# The Legend of the Green Dragon

Legend of the Green Dragon is a text-based RPG originally developed by Eric Stevens and JT Traub as a remake of and homage to the classic BBS Door game, Legend of the Red Dragon, by Seth Able Robinson. You can play it at numerous sites, including http://www.lotgd.net/.

After checking out the developer forums at http://dragonprime.net, it seemed that development had stalled, and specifically, any movement toward more modern technologies and decoupling the game from its web UI was non-existent.

This package aims to offer a modern implementation of the original game ideas. It incorporates elements from [Daenerys](https://github.com/lotgd/core) originally authored by AustenMC and Basilius Sauter, but reduces the very ambigious scope for now.

## Requirements

- PHP 8.4 or later
- composer and symfony binaries available
- yarn or npm available
- MariaDB or MySQL with user and database already existing

## Installation

First, make a new directory clone this repository into it:

```shell
mkdir lotgd2
cd lotgd2
git clone https://github.com/lotgd/lotgd.git .
```

Then, either create a local environment file with the basic configuration, or add the environment variables to the 
virtual environment:

```shell
touch .env.local
php -r "print 'APP_SECRET=' . bin2hex(random_bytes(26)) . \"\n\";" >> .env.local
echo APP_ENV=dev >> .env.local
```

Then, we need to configure the database connection. Adjust the URL to your needs. We recommend to use MariaDB - compatibility
with other database types is not guaranteed right now.

```shell
echo DATABASE_URL="mysql://{username}:{password}@127.0.0.1:3306/{lotgd}?serverVersion=10.6.18-MariaDB&charset=utf8mb4" >> .env.local
```

Then, install the dependencies with composer and yarn (or npm) and build the javascript files:

```shell
composer install
yarn install
yarn run build
```

While developing, you can also run `yarn run watch` to directly compile the changes.

Finally, we initialize the database by running all migrations and loading the fixtures:

```shell
php bin/console doctrine:migrations:migrate first --quiet --no-interaction
php bin/console doctrine:migrations:migrate --quiet --no-interaction
php bin/console doctrine:fixtures:load --quiet --no-interaction
```

Finally, you can run a test server with:

```shell
symfony server:start
```

And access the webserver on `localhost:8000`.

Either build
