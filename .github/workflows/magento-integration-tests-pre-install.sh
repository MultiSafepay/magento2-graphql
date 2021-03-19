#!/bin/bash
composer config github-oauth.github.com $GLOBAL_GITHUB_TOKEN

REPO_SUFFIX=""
if [[ $GITHUB_REPOSITORY == *"internal"* ]] ; then
    REPO_SUFFIX="-internal"
fi

composer config repositories.multisafepay-php-sdk vcs git@github.com:MultiSafepay/php-sdk${REPO_SUFFIX}.git

composer config minimum-stability dev
composer config prefer-stable false

composer require yireo/magento2-replace-bundled:^4.1 --no-update
composer require yireo/magento2-replace-test:@dev --no-update
