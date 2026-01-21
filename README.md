# The Legend of the Green Dragon

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
