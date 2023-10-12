[![Minimum PHP Version](https://img.shields.io/badge/php-^8.1-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/costs-to-expect/yatzy/blob/main/LICENSE)
[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2F13270108-d2e9-459a-9491-8cd34a122001&style=flat-square)](https://forge.laravel.com/servers/581137/sites/2028561)

# Yatzy Game Scoring

## Overview

Game scoring for Yatzy, powered by the Costs to Expect API.

![Score sheet](/resources/art/score-sheet.png)

## Other Apps

[Yahtzee](https://github.com/costs-to-expect/yahtzee)

We plan to create Apps for each of the Board and dice games we play, the Apps will all be Open Source, you 
are free to create your own and then submit a PR to the Costs to Expect [API](https://github.com/costs-to-expect/api) 
to add the new game type.

## Set up

I'm going to assume you are using Docker, if not, you should be able to work out what you need to run for your 
development setup.

Go to the project root directory and run the below.

### Environment

* $ `docker network create costs.network` *
* $ `docker compose build`
* $ `docker compose up`
* $ `docker exec yatzy.app php artisan key:generate`

After generating the key, you need to restart your containers, so run down and up again to force the new key to be used.

* $ `docker exec yatzy.app php artisan migrate:install`
* $ `docker exec yatzy.app php artisan migrate`

*We include a network for local development purposes, I need to connect to a local version of the Costs to Expect
API, You probably don't need this so remove the network section from your docker compose file and don't create the
network.
