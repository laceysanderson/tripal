![alt tag](https://raw.githubusercontent.com/tripal/tripal/7.x-3.x/tripal/theme/images/tripal_logo.png)

# Tripal 4 (Drupal 9 + 10)

                   _   _                 _____                 _                                  _
         /\       | | (_)               |  __ \               | |                                | |
        /  \   ___| |_ ___   _____      | |  | | _____   _____| | ___  _ __  _ __ ___   ___ _ __ | |_
       / /\ \ / __| __| \ \ / / _ \     | |  | |/ _ \ \ / / _ \ |/ _ \| '_ \| '_ ` _ \ / _ \ '_ \| __|
      / ____ \ (__| |_| |\ V /  __/     | |__| |  __/\ V /  __/ | (_) | |_) | | | | | |  __/ | | | |_
     /_/    \_\___|\__|_| \_/ \___|     |_____/ \___| \_/ \___|_|\___/| .__/|_| |_| |_|\___|_| |_|\__|
                                                                      | |
                                                                      |_|


## Compatibility

![Target Drupal Version 10.0.x-dev](https://img.shields.io/badge/Target%20Drupal%20Version-10.0.x-informational)

![PostgreSQL 13](https://img.shields.io/badge/PostreSQL-13-success)

| Drupal      | 9.4.x           | 9.5.x           | 10.0.x          | 10.1.x          |
|-------------|-----------------|-----------------|-----------------|-----------------|
| **PHP 8.0** | ![Grid1A-Badge] | ![Grid1B-Badge] |                 |                 |
| **PHP 8.1** | ![Grid2A-Badge] | ![Grid2B-Badge] | ![Grid2C-Badge] | ![Grid2D-Badge] |
| **PHP 8.2** |                 |                 | ![Grid3C-Badge] | ![Grid3D-Badge] |

[Grid1A-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.0_D9_4x.yml/badge.svg
[Grid1B-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.0_D9_5x.yml/badge.svg

[Grid2A-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.1_D9_4x.yml/badge.svg
[Grid2B-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.1_D9_5x.yml/badge.svg
[Grid2C-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.1_D10_0x.yml/badge.svg
[Grid2D-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.1_D10_1x.yml/badge.svg

[Grid3C-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.2_D10_0x.yml/badge.svg
[Grid3D-Badge]: https://github.com/tripal/tripal/actions/workflows/MAIN-phpunit-php8.2_D10_1x.yml/badge.svg

### Code Coverage

This project uses Code Climate to determine the quality of our codebase and the coverage of our test suite. Compatibility above is based on passing of this test suite.

[![Test Coverage](https://api.codeclimate.com/v1/badges/994fcd39a0eef9cff742/test_coverage)](https://codeclimate.com/github/tripal/tripal/test_coverage)

[![Maintainability](https://api.codeclimate.com/v1/badges/994fcd39a0eef9cff742/maintainability)](https://codeclimate.com/github/tripal/tripal/maintainability)

## Current Timeline

### Required for Tripal 4.alpha2 Release

- Publishing of the pages
- Finish implementing Chado Storage for fields ([#1350](https://github.com/tripal/tripal/issues/1350), [#1351](https://github.com/tripal/tripal/issues/1351))
- Drush generator/updator for fields
- Update existing core fields
- Finish Chado Vocabs/ID Spaces/Terms ([#1354](https://github.com/tripal/tripal/issues/1354))
- Legacy API
- Move data type specific functionality into extension modules ([#1357](https://github.com/tripal/tripal/issues/1357))

### Required for Tripal 4.alpha3 Release

- Display suite / page work
- Importers: Publications, Taxonomy, Newick
- Search (ElasticSearch rather then Views)

## How to get involved!

This upgrade to Drupal 9 is a community effort. As such, we NEED YOUR HELP!

  - To get involved, please join [our Tripal Slack](http://tripal.info/join/slack) and comment in the #core-dev channel.
    - Alternatively, feel free to contact Lacey-Anne Sanderson through Slack direct message.
    - We can use help both with programming, documentation, outreach and welcome all individuals from all backgrounds!
  - We prefer [automated testing](https://tripaldoc.readthedocs.io/en/latest/dev_guide/testing.html) for all Pull Requests (PRs) and are happy to guide you through the process!
    - Just comment on the issue if you need help designing tests
    - If you need help implementing tests see our [documentation](https://tripaldoc.readthedocs.io/en/latest/dev_guide/testing.html)
  - If more documentation would be helpful, [please create an issue here](https://github.com/tripal/tripal_doc/issues) and we'll add it.

## Documentation

[Documentation for Tripal 4 has begun on ReadtheDocs](https://tripaldoc.readthedocs.io/en/latest/dev_guide.html). **Please keep in mind the URL for this documentation will change once Tripal 4 is released.**

  - [Installation](https://tripaldoc.readthedocs.io/en/latest/install.html)
  - [Automated Testing](https://tripaldoc.readthedocs.io/en/latest/dev_guide/testing.html)
  - [Developer Guide](https://tripaldoc.readthedocs.io/en/latest/dev_guide.html)
  - [Contribution Guide](https://tripaldoc.readthedocs.io/en/latest/contributing.html)

## Tripal Docker

Tripal Docker is currently focused on Development and Unit Testing. There will be a production focused Tripal Docker soon and if you're interested in helping or providing tips -please join us on our Slack channel!

For more information about how to use our fully functional development docker, see [our documentation on ReadtheDocs!](https://tripaldoc.readthedocs.io/en/latest/install/docker.html)
